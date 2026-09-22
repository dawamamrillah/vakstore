<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Models\PpobServiceOption;
use App\Models\Product;
use App\Services\Provider\PpobService;
use App\Services\Transaction\TransactionService;
use App\Services\Voucher\VoucherService;
use App\Support\ErrorSanitizer;
use Exception;
use Illuminate\Http\Request;

class PpobController extends Controller
{
    public function __construct(
        protected PpobService $ppobService,
        protected TransactionService $transactionService,
        protected VoucherService $voucherService
    ) {}

    public function index()
    {
        $allowedPulsaSlugs = ['telkomsel', 'axis', 'xl', 'tri', 'smartfren', 'indosat', 'byu'];

        $services = Game::whereHas('category', fn ($q) => $q->where('slug', 'pulsa-all-operator'))
            ->where('status', 'active')
            ->whereIn('slug', $allowedPulsaSlugs)
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->sortBy(fn ($g) => array_search($g->slug, $allowedPulsaSlugs))
            ->values();

        $category = Category::where('slug', 'pulsa-all-operator')->first();

        return view('ppob.index', compact('category', 'services'));
    }

    public function show($slug)
    {
        $service = Game::with([
            'products' => fn ($q) => $q->where('status', 'active')
                ->with(['serviceOptions' => fn ($sq) => $sq->where('status', 'active')->orderBy('name')])
                ->orderBy('cost_price'),
        ])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $groupedProducts = $service->products->groupBy('sub_category');

        return view('ppob.show', compact('service', 'groupedProducts'));
    }

    /**
     * Real-time Inquiry for Postpaid Bills (PDAM, Telkom, PLN Pascabayar)
     * SKU strictly resolved from database mapping (ppob_service_options).
     */
    public function inquiry(Request $request)
    {
        $rules = [
            'customer_number' => 'required|string|min:3|max:100',
            'product_id' => 'nullable|integer|exists:products,id',
            'service_option_id' => 'nullable|integer',
            'service_type' => 'nullable|string',
            'region' => 'nullable|string',
        ];

        // PLN Pascabayar tidak menggunakan region/provider selection.
        // SKU ditentukan dari mapping layanan resmi di database.
        $isPlnPascabayar = $request->input('service_type') === 'pln-pascabayar';

        if (
            ! $isPlnPascabayar
            && ! $request->filled('service_option_id')
            && ! $request->filled('region')
        ) {
            $rules['region'] = 'required|string';
        }

        $messages = [
            'customer_number.required' => 'Nomor / ID Pelanggan wajib diisi.',
            'customer_number.min' => 'Nomor / ID Pelanggan minimal 3 karakter.',
            'region.required' => 'Silakan pilih wilayah / provider terlebih dahulu sebelum memeriksa tagihan.',
        ];

        $request->validate($rules, $messages);

        $customerNumber = trim((string) $request->customer_number);

        try {
            $buyerSkuCode = null;
            $product = null;
            $option = null;

            // PLN Pascabayar tidak memiliki region atau pilihan provider.
            // Gunakan mapping Product -> Game secara internal untuk mendapatkan SKU plnpas1.
            if ($isPlnPascabayar) {
                $product = Product::where('status', 'active')
                    ->where('provider_sku', 'plnpas1')
                    ->whereHas('game', fn ($q) => $q->where('slug', 'pln-pascabayar'))
                    ->first();

                if (! $product) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Produk PLN Pascabayar belum aktif atau mapping provider tidak ditemukan.',
                    ], 422);
                }

                $buyerSkuCode = $product->provider_sku;
            }
            // Priority 1: Direct resolution via product_id and service_option_id (Standard New Architecture)
            elseif ($request->filled('product_id') && $request->filled('service_option_id')) {
                $product = Product::findOrFail($request->product_id);
                $option = PpobServiceOption::where('id', $request->service_option_id)
                    ->where('product_id', $product->id)
                    ->where('status', 'active')
                    ->first();

                if (! $option) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Opsi layanan tidak ditemukan atau tidak sesuai dengan produk yang dipilih.',
                    ], 422);
                }

                $buyerSkuCode = $option->buyer_sku_code;
            } elseif ($request->filled('service_option_id')) {
                // Resolution via service_option_id alone
                $option = PpobServiceOption::where('id', $request->service_option_id)
                    ->where('status', 'active')
                    ->first();

                if (! $option) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Opsi layanan tidak ditemukan atau sedang tidak aktif.',
                    ], 422);
                }

                // If product_id was also provided, enforce strict relation integrity
                if ($request->filled('product_id') && $option->product_id != $request->product_id) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Opsi layanan tidak sesuai dengan produk tagihan yang dipilih.',
                    ], 422);
                }

                $buyerSkuCode = $option->buyer_sku_code;
            } elseif ($request->filled('region')) {
                // Resolution via region SKU (e.g. pd32, in7, plnpas1) from verified database mapping
                $regionInput = trim((string) $request->region);
                $option = PpobServiceOption::where('buyer_sku_code', $regionInput)
                    ->where('status', 'active')
                    ->first();

                if ($option) {
                    if ($request->filled('product_id') && $option->product_id != $request->product_id) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Opsi wilayah/provider tidak sesuai dengan produk yang dipilih.',
                        ], 422);
                    }
                    $buyerSkuCode = $option->buyer_sku_code;
                } else {
                    // Fallback only to an explicitly active product (e.g. PLN-POSTPAID).
                    // Never use inactive products as a provider/SKU mapping.
                    $directProduct = Product::where('status', 'active')
                        ->where(function ($query) use ($regionInput) {
                            $query->where('provider_sku', $regionInput)
                                ->orWhere('buyer_sku_code', $regionInput);
                        })
                        ->first();

                    if ($directProduct) {
			$product = $directProduct;
			$buyerSkuCode = $directProduct->provider_sku ?? $directProduct->buyer_sku_code;
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Kode wilayah / provider tidak ditemukan di database resmi.',
                        ], 422);
                    }
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Silakan pilih wilayah / provider terlebih dahulu sebelum memeriksa tagihan.',
                ], 422);
            }

            if (empty($buyerSkuCode)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kode SKU provider tidak ditemukan untuk opsi yang dipilih.',
                ], 422);
            }

            // Pastikan product dan service option yang disimpan ke session
// berasal dari mapping database server-side.
$resolvedProductId = $product->id ?? ($option->product_id ?? null);
$resolvedOptionId = $option->id ?? null;

if (! $resolvedProductId) {
    $resolvedProductId = Product::where('status', 'active')
        ->where(function ($query) use ($buyerSkuCode) {
            $query->where('provider_sku', $buyerSkuCode)
                ->orWhere('buyer_sku_code', $buyerSkuCode);
        })
        ->value('id');
}

if (! $resolvedProductId) {
    return response()->json([
        'status' => 'error',
        'message' => 'Produk provider tidak ditemukan dalam mapping database.',
    ], 422);
}

$res = $this->ppobService->inquiryDirect($buyerSkuCode, $customerNumber);

if (($res['status'] ?? '') === 'error') {
    $res['message'] = ErrorSanitizer::sanitize(
        $res['message'] ?? 'Tagihan tidak ditemukan atau ID pelanggan tidak valid.'
    );

    return response()->json($res, 422);
}
            // Simpan hasil inquiry yang sudah berhasil di server-side session.
            // Nilai ini menjadi sumber kebenaran untuk checkout dan tidak boleh
            // digantikan oleh bill_amount dari hidden input/frontend.
            $request->session()->put('ppob_verified_inquiry', [
                'product_id' => $resolvedProductId,
                'service_option_id' => $resolvedOptionId,
                'buyer_sku_code' => $buyerSkuCode,
                'customer_number' => $customerNumber,
                'bill_amount' => (float) ($res['bill_amount'] ?? 0),
                'admin_fee' => (float) ($res['admin_fee'] ?? 0),
                'customer_name' => $res['customer_name'] ?? null,
                'nickname' => $res['nickname'] ?? null,
                'period' => $res['period'] ?? null,
                'ref_id' => $res['ref_id'] ?? null,
                'verified_at' => now()->timestamp,
            ]);

            return response()->json($res);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => ErrorSanitizer::sanitize($e->getMessage()),
            ], 422);
        }
    }

    /**
     * Postpaid Checkout & Order Creation
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'service_option_id' => 'nullable|integer',
            'target' => 'required|string',
            'target_secondary' => 'nullable|string',
            'nickname' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'voucher_code' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'bill_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $product = Product::with('game')->findOrFail($request->product_id);
            $isBillService = in_array($product->game?->slug ?? '', [
                'pdam-nusantara',
                'telkom-indihome',
                'pln-pascabayar',
            ]) || str_contains(strtolower($product->name), 'pascabayar');

            $orderData = $request->all();

            // Strict Postpaid Validation:
            // bill_amount and provider SKU must come from a successful server-side
            // inquiry, never from values submitted by the browser.
            if ($isBillService) {
                $verifiedInquiry = $request->session()->get('ppob_verified_inquiry');

                if (! is_array($verifiedInquiry)) {
                    throw new Exception('Silakan lakukan Cek Tagihan terlebih dahulu sebelum melanjutkan pembayaran.');
                }

                // Inquiry verification is intentionally short-lived.
                $verifiedAt = (int) ($verifiedInquiry['verified_at'] ?? 0);
                if ($verifiedAt <= 0 || now()->timestamp - $verifiedAt > (15 * 60)) {
                    $request->session()->forget('ppob_verified_inquiry');

                    throw new Exception('Hasil Cek Tagihan sudah kedaluwarsa. Silakan lakukan Cek Tagihan kembali.');
                }

                $verifiedProductId = (int) ($verifiedInquiry['product_id'] ?? 0);
                $verifiedCustomerNumber = trim((string) ($verifiedInquiry['customer_number'] ?? ''));
                $verifiedSku = trim((string) ($verifiedInquiry['buyer_sku_code'] ?? ''));
                $verifiedBillAmount = (float) ($verifiedInquiry['bill_amount'] ?? 0);

                if ($verifiedProductId !== (int) $product->id) {
                    throw new Exception('Produk tidak sesuai dengan hasil Cek Tagihan. Silakan lakukan Cek Tagihan kembali.');
                }

                if ($verifiedCustomerNumber !== trim((string) $request->target)) {
                    throw new Exception('ID Pelanggan tidak sesuai dengan hasil Cek Tagihan. Silakan lakukan Cek Tagihan kembali.');
                }

                if ($verifiedSku === '') {
                    throw new Exception('SKU provider pada hasil Cek Tagihan tidak valid.');
                }

		// Pastikan SKU hasil inquiry benar-benar merupakan SKU provider
		// milik produk yang sedang di-checkout.
		$productProviderSku = trim((string) ($product->provider_sku ?? ''));

		if ($productProviderSku === '' || strcasecmp($productProviderSku, $verifiedSku) !== 0) {
                    throw new Exception('SKU provider tidak sesuai dengan produk. Silakan lakukan Cek Tagihan kembali.');
		}

                if ($verifiedBillAmount <= 0) {
                    throw new Exception('Nominal tagihan dari provider tidak valid.');
                }

                // For services using a provider option, the option must also match
                // the exact option used during inquiry.
                $verifiedOptionId = $verifiedInquiry['service_option_id'] ?? null;

                if ($verifiedOptionId !== null) {
                    $verifiedOptionId = (int) $verifiedOptionId;

                    $option = PpobServiceOption::where('id', $verifiedOptionId)
                        ->where('product_id', $product->id)
                        ->where('status', 'active')
                        ->first();

                    if (! $option || $option->buyer_sku_code !== $verifiedSku) {
                        throw new Exception('Opsi layanan pada hasil Cek Tagihan tidak lagi valid.');
                    }

                    if ($request->filled('service_option_id')
                        && (int) $request->service_option_id !== $verifiedOptionId) {
                        throw new Exception('Opsi layanan tidak sesuai dengan hasil Cek Tagihan.');
                    }

                    $orderData['ppob_service_option_id'] = $verifiedOptionId;
                } else {
                    // PLN Pascabayar currently has no region/provider selection.
                    $orderData['ppob_service_option_id'] = null;
                }

                // SECURITY: overwrite all provider-sensitive values with the
                // server-side verified inquiry result. Do not trust hidden inputs.
                $orderData['bill_amount'] = $verifiedBillAmount;
                $orderData['target'] = $verifiedCustomerNumber;
                $orderData['target_secondary'] = null;

                if (! empty($verifiedInquiry['customer_name'])) {
                    $orderData['customer_name'] = $verifiedInquiry['customer_name'];
                }

                if (! empty($verifiedInquiry['nickname'])) {
                    $orderData['nickname'] = $verifiedInquiry['nickname'];
                }
            }

            $user = auth()->user();
            $transaction = $this->transactionService->createOrder($orderData, $user);

            // Inquiry yang sudah digunakan tidak boleh dipakai kembali
            // untuk membuat order berikutnya.
            if ($isBillService) {
                $request->session()->forget('ppob_verified_inquiry');
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Layanan PPOB berhasil diproses.',
                    'invoice_number' => $transaction->invoice_number,
                    'redirect_url' => route('invoice.show', $transaction->invoice_number),
                ]);
            }

            return redirect()->route('invoice.show', $transaction->invoice_number)
                ->with('success', 'Transaksi PPOB berhasil diproses!');
        } catch (Exception $e) {
            $cleanError = ErrorSanitizer::sanitize($e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $cleanError,
                ], 422);
            }

            return back()->withInput()->with('error', $cleanError);
        }
    }
}
