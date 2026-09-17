<?php

namespace App\Contracts\Payment;

use App\Models\Transaction;

interface PaymentGatewayInterface
{
    public function createPayment(Transaction $transaction, string $paymentMethod): array;

    public function verifyCallback(array $payload, string $signature): bool;

    public function handleCallback(array $payload): array;
}
