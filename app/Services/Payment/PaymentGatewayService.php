<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentGatewayInterface;
use App\Models\Payment;
use App\Models\Transaction;

class PaymentGatewayService
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(?PaymentGatewayInterface $gateway = null)
    {
        $this->gateway = $gateway ?? new MockPaymentGatewayAdapter;
    }

    public function initiatePayment(Transaction $transaction, string $paymentMethod): Payment
    {
        $paymentData = $this->gateway->createPayment($transaction, $paymentMethod);

        return Payment::create([
            'transaction_id' => $transaction->id,
            'payment_method' => $paymentMethod,
            'payment_reference' => $paymentData['payment_reference'],
            'amount' => $transaction->total,
            'qr_string' => $paymentData['qr_string'] ?? null,
            'pay_code' => $paymentData['pay_code'] ?? null,
            'status' => $paymentData['status'],
            'paid_at' => $paymentData['status'] === 'paid' ? now() : null,
            'expired_at' => now()->addMinutes(60),
            'callback_payload' => $paymentData,
        ]);
    }
}
