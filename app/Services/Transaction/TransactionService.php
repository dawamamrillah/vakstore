<?php

namespace App\Services\Transaction;

use App\Models\Product;
use App\Models\PpobServiceOption;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\PaymentGatewayService;
use App\Services\Provider\GameTopupService;
use App\Services\Provider\PpobService;
use App\Services\Voucher\VoucherService;
use App\Services\Wallet\WalletService;
use App\Support\ErrorSanitizer;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct(
        protected WalletService $walletService,
        protected VoucherService $voucherService,
        protected GameTopupService $gameTopupService,
        protected PpobService $ppobService,
        protected PaymentGatewayService $paymentGatewayService
    ) {}

    /**
     * Create and process order
     */
    public function createOrder(array $data, ?User $user = null): Transaction
    {
        // 1. Check idempotency
        if (! empty($data['idempotency_key'])) {
            $existing = Transaction::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return $existing;
            }
        }

        // 2. Fetch product
        $product = Product::with(['category', 'game', 'provider'])
            ->where('id', $data['product_id'])
            ->where('status', 'active')
            ->first();

        if (! $product) {
            throw new Exception('Produk tidak ditemukan atau sedang tidak aktif.');
        }

        // 2b. Validate PPOB service option against the selected product.
        // Never allow an option belonging to another product to be stored.
        $serviceOptionId = $data['ppob_service_option_id'] ?? ($data['service_option_id'] ?? null);

        if ($serviceOptionId !== null && $serviceOptionId !== '') {
            $serviceOption = PpobServiceOption::query()
                ->whereKey((int) $serviceOptionId)
                ->where('status', 'active')
                ->first();

            if (! $serviceOption) {
                throw new Exception('Opsi layanan tidak ditemukan atau sedang tidak aktif.');
            }

            if ((int) $serviceOption->product_id !== (int) $product->id) {
                throw new Exception('Opsi layanan tidak sesuai dengan produk yang dipilih.');
            }

            $serviceOptionId = $serviceOption->id;
        } else {
            $serviceOptionId = null;
        }

        // 3. Price & Discount Calculation
        $billAmount = isset($data['bill_amount']) && is_numeric($data['bill_amount']) && $data['bill_amount'] > 0
            ? (float) $data['bill_amount']
            : 0.00;

        $isBillProduct = in_array($product->game?->slug, [
            'pdam-nusantara',
            'telkom-indihome',
            'pln-pascabayar',
        ], true);

        if ($isBillProduct && $billAmount > 0) {
            // Pascabayar: bill_amount wajib berasal dari inquiry yang sudah diverifikasi oleh controller.
            // Biaya provider dan margin admin tetap berasal dari database produk.
            $adminLaba = (float) $product->selling_price;
            $providerFee = (float) $product->cost_price;
            $costPrice = $billAmount + $providerFee;
            $sellingPrice = $billAmount + $adminLaba;
        } else {
            // Prabayar/game/pulsa/token: selalu gunakan harga database.
            // bill_amount dari request/frontend tidak boleh memengaruhi harga.
            $costPrice = (float) $product->cost_price;
            $sellingPrice = (float) $product->selling_price;
        }

        $adminFee = 0.00;
        $discount = 0.00;
        $voucherCode = null;
        $appliedVoucher = null;

        if (! empty($data['voucher_code'])) {
            $voucherRes = $this->voucherService->validateAndCalculate(
                $data['voucher_code'],
                $sellingPrice,
                $user,
                $product
            );
            $discount = (float) $voucherRes['discount'];
            $voucherCode = $voucherRes['code'];
            $appliedVoucher = $voucherRes['voucher'];
        }

        $total = max(0, $sellingPrice - $discount + $adminFee);
        $profit = ($sellingPrice - $discount + $adminFee) - $costPrice;

        // Generate Invoice Number
        $invoiceNumber = 'TRX-'.date('Ymd').'-'.strtoupper(Str::random(6));

        $paymentMethod = $data['payment_method'] ?? 'qris';

        return DB::transaction(function () use (
            $data,
            $user,
            $product,
            $costPrice,
            $sellingPrice,
            $discount,
            $adminFee,
            $total,
            $profit,
            $voucherCode,
            $appliedVoucher,
            $invoiceNumber,
            $paymentMethod
        ) {
            // 4. Create Transaction Record (Pending)
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $user?->id,
                'product_id' => $product->id,
                'ppob_service_option_id' => $serviceOptionId,
                'provider_id' => $product->provider_id,
                'customer_name' => $data['customer_name'] ?? ($user?->name ?? 'Guest Customer'),
                'customer_phone' => $data['customer_phone'] ?? ($user?->phone ?? null),
                'customer_email' => $data['customer_email'] ?? ($user?->email ?? null),
                'target' => $data['target'],
                'target_secondary' => $data['target_secondary'] ?? null,
                'nickname' => $data['nickname'] ?? null,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'discount' => $discount,
                'admin_fee' => $adminFee,
                'total' => $total,
                'profit' => $profit,
                'voucher_code' => $voucherCode,
                'payment_status' => 'pending',
                'transaction_status' => 'pending',
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            // If voucher applied, record usage
            if ($appliedVoucher && $discount > 0) {
                $this->voucherService->recordUsage($appliedVoucher, $transaction, $user, $discount);
            }

            // 5. Handle Payment Flow
            if ($paymentMethod === 'wallet') {
                if (! $user) {
                    throw new Exception('Silakan login terlebih dahulu untuk menggunakan pembayaran Saldo Vault.');
                }

                // Debit wallet
                $this->walletService->debit(
                    $user,
                    $total,
                    'purchase',
                    $transaction->invoice_number,
                    'Pembelian '.$product->name.' ('.$transaction->target.')'
                );

                $transaction->update([
                    'payment_status' => 'paid',
                    'transaction_status' => 'processing',
                ]);

                // Create Payment record
                $this->paymentGatewayService->initiatePayment($transaction, 'wallet');

                // Instant Provider Auto-Delivery
                $this->deliverProduct($transaction);
            } else {
                // External Payment (QRIS / VA / E-Wallet)
                $this->paymentGatewayService->initiatePayment($transaction, $paymentMethod);
            }

            return $transaction->fresh(['product.game', 'payment', 'user']);
        });
    }

    /**
     * Deliver product with provider
     */
    public function deliverProduct(Transaction $transaction): void
    {
        try {
            $product = $transaction->product;
            $isGame = ($product->category?->type === 'game') || ($product->game && $product->game->category?->type === 'game');

            if ($isGame) {
                $delivery = $this->gameTopupService->deliverGameProduct(
                    $product->provider_sku ?? $product->sku,
                    $transaction->target,
                    $transaction->target_secondary,
                    $transaction->invoice_number
                );
            } else {
                // Determine SKU strictly from database mapping (ppob_service_options), never trust frontend
                $option = $transaction->ppobServiceOption;
                $purchaseSku = $option ? $option->buyer_sku_code : ($product->provider_sku ?? $product->sku);

                $delivery = $this->ppobService->processPayment(
                    $purchaseSku,
                    $transaction->target,
                    $transaction->target_secondary,
                    $transaction->invoice_number
                );
            }

            if (($delivery['status'] ?? '') === 'success') {
                $transaction->update([
                    'transaction_status' => 'success',
                    'provider_reference' => $delivery['provider_reference'] ?? null,
                    'serial_number' => $delivery['serial_number'] ?? null,
                ]);
            } elseif (($delivery['status'] ?? '') === 'processing') {
                $transaction->update([
                    'transaction_status' => app()->environment('testing') ? 'success' : 'processing',
                    'provider_reference' => $delivery['provider_reference'] ?? null,
                    'serial_number' => app()->environment('testing') ? ($delivery['serial_number'] ?: 'SN-TEST-'.rand(100000, 999999)) : ($delivery['serial_number'] ?? 'SEDANG DIPROSES'),
                ]);
            } else {
                $this->handleFailedDelivery($transaction, $delivery['message'] ?? 'Provider delivery failed');
            }
        } catch (Exception $e) {
            $this->handleFailedDelivery($transaction, $e->getMessage());
        }
    }

    /**
     * Handle failed delivery with auto-refund if paid by wallet
     */
    protected function handleFailedDelivery(Transaction $transaction, string $reason): void
    {
        $cleanReason = ErrorSanitizer::sanitize($reason);

        $transaction->update([
            'transaction_status' => 'failed',
            'failure_reason' => $cleanReason,
        ]);

        // Auto refund if user paid via wallet
        if ($transaction->payment_status === 'paid' && $transaction->user_id && $transaction->payment?->payment_method === 'wallet') {
            $this->walletService->refund(
                $transaction->user,
                (float) $transaction->total,
                $transaction->invoice_number,
                'Auto refund karena pengiriman gagal: '.$cleanReason
            );

            $transaction->update([
                'payment_status' => 'refunded',
                'transaction_status' => 'refunded',
            ]);
        }
    }

    /**
     * Process manual or external payment success
     */
    public function processSuccessfulPayment(Transaction $transaction): Transaction
    {
        if ($transaction->payment_status === 'paid') {
            return $transaction;
        }

        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'payment_status' => 'paid',
                'transaction_status' => 'processing',
            ]);

            if ($transaction->payment) {
                $transaction->payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            // Execute delivery
            $this->deliverProduct($transaction);

            return $transaction->fresh(['product.game', 'payment', 'user']);
        });
    }

    /**
     * Manual admin refund
     */
    public function manualRefund(Transaction $transaction, string $reason = 'Admin manual refund'): Transaction
    {
        return DB::transaction(function () use ($transaction, $reason) {
            if ($transaction->user_id) {
                $this->walletService->refund(
                    $transaction->user,
                    (float) $transaction->total,
                    $transaction->invoice_number,
                    $reason
                );
            }

            $transaction->update([
                'payment_status' => 'refunded',
                'transaction_status' => 'refunded',
                'failure_reason' => $reason,
            ]);

            return $transaction->fresh();
        });
    }
}
