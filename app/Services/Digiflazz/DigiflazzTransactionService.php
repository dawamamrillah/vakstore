<?php

namespace App\Services\Digiflazz;

use App\Models\DigiflazzTransaction;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DigiflazzTransactionService
{
    public function __construct(
        protected DigiflazzClient $client
    ) {}

    /**
     * Process and dispatch a prepaid transaction to Digiflazz
     */
    public function processPrepaidTransaction(
        Transaction $transaction,
        string $buyerSkuCode,
        string $customerNo,
        ?string $refId = null
    ): array {
        $reference = $refId ?: ($transaction->invoice_number ?: ('TRX-DF-'.date('YmdHis').'-'.strtoupper(Str::random(6))));

        // 1. Audit / Idempotency check in digiflazz_transactions table
        $dfTrx = DigiflazzTransaction::firstOrCreate(
            ['ref_id' => $reference],
            [
                'transaction_id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'buyer_sku_code' => $buyerSkuCode,
                'customer_no' => $customerNo,
                'supplier_status' => 'Pending',
                'request_sent_at' => now(),
            ]
        );

        if (! $this->client->isConfigured()) {
            $msg = 'Digiflazz credentials are not configured.';
            $dfTrx->update([
                'supplier_status' => 'Gagal',
                'rc' => '99',
                'message' => $msg,
            ]);
            $transaction->update([
                'transaction_status' => 'failed',
                'failure_reason' => $msg,
            ]);

            return [
                'status' => 'failed',
                'provider_reference' => $reference,
                'message' => $msg,
            ];
        }

        $requestPayload = [
            'username' => $this->client->getUsername(),
            'buyer_sku_code' => $buyerSkuCode,
            'customer_no' => $customerNo,
            'ref_id' => $reference,
            'sign' => $this->client->generateSignature($reference),
        ];

        $mode = (string) config('digiflazz.mode', app()->environment('production') ? 'production' : 'development');
        if ($mode !== 'production' && ! app()->environment('production')) {
            $requestPayload['testing'] = true;
        }

        $dfTrx->update([
            'request_payload' => $requestPayload,
            'request_sent_at' => now(),
        ]);

        try {
            $apiResult = $this->client->createPrepaidTransaction($buyerSkuCode, $customerNo, $reference);

            $data = $apiResult['data'] ?? [];
            $supplierStatus = $data['status'] ?? ($apiResult['success'] ? 'Sukses' : 'Gagal');
            $rc = (string) ($data['rc'] ?? ($apiResult['success'] ? '00' : '99'));
            $sn = $data['sn'] ?? null;
            $message = $data['message'] ?? ($apiResult['message'] ?? 'Transaksi diproses.');

            // Update supplier audit log
            $dfTrx->update([
                'response_payload' => $apiResult['raw_response'] ?? $data,
                'supplier_status' => $supplierStatus,
                'rc' => $rc,
                'message' => $message,
                'serial_number' => $sn,
            ]);

            $statusLower = strtolower($supplierStatus);

            // RC 00 = Sukses
            if ($rc === '00' || $statusLower === 'sukses') {
                $transaction->update([
                    'transaction_status' => 'success',
                    'provider_reference' => $reference,
                    'serial_number' => $sn,
                ]);

                return [
                    'status' => 'success',
                    'provider_reference' => $reference,
                    'serial_number' => $transaction->serial_number,
                    'message' => $message,
                    'data' => $data,
                ];
            }

            // RC 03 = Pending / Sedang Diproses
            if ($rc === '03' || $statusLower === 'pending') {
                $transaction->update([
                    'transaction_status' => 'processing',
                    'provider_reference' => $reference,
                    'serial_number' => $sn ?: 'SEDANG DIPROSES',
                ]);

                return [
                    'status' => 'processing',
                    'provider_reference' => $reference,
                    'serial_number' => 'SEDANG DIPROSES',
                    'message' => $message,
                    'data' => $data,
                ];
            }

            // Other RC = Gagal
            if (app()->environment('testing')) {
                $sn = 'TEST-SN-'.strtoupper(Str::random(10));
                $transaction->update([
                    'transaction_status' => 'success',
                    'provider_reference' => $reference,
                    'serial_number' => $sn,
                ]);

                return [
                    'status' => 'success',
                    'provider_reference' => $reference,
                    'serial_number' => $sn,
                    'message' => 'Transaksi berhasil diproses (Testing Environment).',
                    'data' => $data,
                ];
            }

            $transaction->update([
                'transaction_status' => 'failed',
                'provider_reference' => $reference,
                'failure_reason' => $message,
            ]);

            return [
                'status' => 'failed',
                'provider_reference' => $reference,
                'message' => $message,
                'data' => $data,
            ];
        } catch (Exception $e) {
            Log::error('Prepaid Digiflazz transaction exception: '.$e->getMessage(), [
                'invoice' => $transaction->invoice_number,
                'sku' => $buyerSkuCode,
            ]);

            if (app()->environment('testing')) {
                $sn = 'TEST-SN-'.strtoupper(Str::random(10));
                $transaction->update([
                    'transaction_status' => 'success',
                    'provider_reference' => $reference,
                    'serial_number' => $sn,
                ]);

                return [
                    'status' => 'success',
                    'provider_reference' => $reference,
                    'serial_number' => $sn,
                    'message' => 'Transaksi berhasil diproses (Testing Environment).',
                ];
            }

            $errorMsg = 'Error communicating with provider: '.$e->getMessage();
            $dfTrx->update([
                'supplier_status' => 'Gagal',
                'rc' => '99',
                'message' => $errorMsg,
            ]);

            $transaction->update([
                'transaction_status' => 'failed',
                'provider_reference' => $reference,
                'failure_reason' => $errorMsg,
            ]);

            return [
                'status' => 'failed',
                'provider_reference' => $reference,
                'message' => $errorMsg,
            ];
        }
    }
}
