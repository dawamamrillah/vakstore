<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Models\Transaction;
use Illuminate\Support\Str;

class MockPaymentGatewayAdapter implements PaymentGatewayInterface
{
    /**
     * Create payment payload
     */
    public function createPayment(Transaction $transaction, string $paymentMethod): array
    {
        $ref = 'PAY-'.date('Ymd').'-'.Str::upper(Str::random(8));
        $method = strtolower($paymentMethod);

        if ($method === 'wallet') {
            return [
                'status' => 'paid',
                'payment_reference' => $ref,
                'payment_method' => 'wallet',
                'amount' => $transaction->total,
                'qr_string' => null,
                'pay_code' => null,
                'instructions' => 'Pembayaran otomatis dipotong dari Saldo Vault.',
            ];
        }

        if ($method === 'qris') {
            return [
                'status' => 'pending',
                'payment_reference' => $ref,
                'payment_method' => 'qris',
                'amount' => $transaction->total,
                'qr_string' => '00020101021226590014ID.LINKAJA.WWW01189360091100210082725204581253033605802ID5908VAKSTORE6007JAKARTA61051219062070703A016304'.rand(1000, 9999),
                'pay_code' => null,
                'instructions' => 'Scan QRIS menggunakan BCA, GoPay, OVO, DANA, atau mobile banking apapun.',
            ];
        }

        if (str_contains($method, 'va')) {
            $bankCode = match ($method) {
                'bca_va' => '8271',
                'bri_va' => '1092',
                'mandiri_va' => '8890',
                'bni_va' => '9881',
                default => '8800',
            };
            $vaNumber = $bankCode.rand(10000000, 99999999);

            return [
                'status' => 'pending',
                'payment_reference' => $ref,
                'payment_method' => $method,
                'amount' => $transaction->total,
                'qr_string' => null,
                'pay_code' => $vaNumber,
                'instructions' => 'Transfer nominal tepat ke nomor Virtual Account di atas.',
            ];
        }

        // E-Wallets
        return [
            'status' => 'pending',
            'payment_reference' => $ref,
            'payment_method' => $method,
            'amount' => $transaction->total,
            'qr_string' => null,
            'pay_code' => '08'.rand(1111111111, 9999999999),
            'instructions' => 'Buka aplikasi e-wallet Anda dan setujui permintaan pembayaran.',
        ];
    }

    public function verifyCallback(array $payload, string $signature): bool
    {
        return true;
    }

    public function handleCallback(array $payload): array
    {
        return [
            'status' => 'success',
            'payment_reference' => $payload['payment_reference'] ?? '',
            'amount' => $payload['amount'] ?? 0,
        ];
    }
}
