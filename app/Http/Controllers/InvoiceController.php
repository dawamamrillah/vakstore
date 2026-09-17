<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\Transaction\TransactionService;

class InvoiceController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function show(string $invoiceNumber)
    {
        $transaction = Transaction::with(['product.game', 'product.category', 'payment', 'user'])
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail();

        return view('invoice.show', compact('transaction'));
    }

    /**
     * Simulate Payment Verification for Mock Gateway
     */
    public function simulatePay(string $invoiceNumber)
    {
        $transaction = Transaction::where('invoice_number', $invoiceNumber)->firstOrFail();

        $this->transactionService->processSuccessfulPayment($transaction);

        return redirect()->route('invoice.show', $invoiceNumber)
            ->with('success', 'Simulasi pembayaran sukses! Layanan otomatis diproses.');
    }
}
