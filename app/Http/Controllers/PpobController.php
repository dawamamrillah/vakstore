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

        if (! $request->filled('service_option_id') && ! $request->filled('region')) {
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

            // Priority 1: Direct resolution via product_id and service_option_id (Standard New Architecture)
            if ($request->filled('product_id') && $request->filled('service_option_id')) {
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
                    // Check if it matches a known active product directly (e.g. PLN-POSTPAID)
                    $directProduct = Product::where('provider_sku', $regionInput)
                        ->orWhere('buyer_sku_code', $regionInput)
                        ->first();

                    if ($directProduct) {
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

            $res = $this->ppobService->inquiryDirect($buyerSkuCode, $customerNumber);

            if (($res['status'] ?? '') === 'error') {
                $res['message'] = ErrorSanitizer::sanitize($res['message'] ?? 'Tagihan tidak ditemukan atau ID pelanggan tidak valid.');

                return response()->json($res, 422);
            }

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
            $isBillService = in_array($product->game?->slug ?? '', ['pdam-nusantara', 'telkom-indihome'])
                || str_contains(strtolower($product->name), 'pascabayar');

            $orderData = $request->all();

            // Strict Postpaid Validation
            if ($isBillService) {
                // Must have validated bill amount > 0
                if (empty($request->bill_amount) || (float) $request->bill_amount <= 0) {
                    throw new Exception('Silakan lakukan Cek Tagihan terlebih dahulu untuk mendapatkan rincian pembayaran resmi.');
                }

                // If service option is provided, validate relation integrity
                if ($request->filled('service_option_id')) {
                    $option = PpobServiceOption::where('id', $request->service_option_id)
                        ->where('product_id', $product->id)
                        ->where('status', 'active')
                        ->first();

                    if (! $option) {
                        throw new Exception('Opsi layanan yang dipilih tidak valid untuk produk ini.');
                    }

                    $orderData['ppob_service_option_id'] = $option->id;
                }
            }

            $user = auth()->user();
            $transaction = $this->transactionService->createOrder($orderData, $user);

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
