<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\DigiflazzTransaction;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DigiflazzWebhookController extends Controller
{
    /**
     * Handle incoming Digiflazz Webhook / Callback
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Support both wrapped in "data" or top-level payload
        $data = $payload['data'] ?? $payload;
        $refId = trim((string) ($data['ref_id'] ?? ''));

        if (empty($refId)) {
            Log::warning('Digiflazz webhook received without ref_id', ['payload' => $payload]);

            return response()->json([
                'status' => 'error',
                'message' => 'Missing ref_id in webhook payload',
            ], 400);
        }

        $supplierStatus = trim((string) ($data['status'] ?? ''));
        $rc = trim((string) ($data['rc'] ?? ''));
        $sn = trim((string) ($data['sn'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        $statusLower = strtolower($supplierStatus);

        // Find or create supplier audit record
        $dfTrx = DigiflazzTransaction::where('ref_id', $refId)->first();

        // Find main Transaction
        $transaction = null;
        if ($dfTrx && $dfTrx->transaction_id) {
            $transaction = Transaction::find($dfTrx->transaction_id);
        }
        if (! $transaction) {
            $transaction = Transaction::where('invoice_number', $refId)
                ->orWhere('provider_reference', $refId)
                ->first();
        }

        // Idempotency Check: if already completed (success or failed), avoid duplicate state transition
        $isAlreadyFinal = $transaction && in_array($transaction->transaction_status, ['success', 'failed', 'refunded']);

        if ($dfTrx) {
            $dfTrx->update([
                'callback_payload' => $payload,
                'supplier_status' => $supplierStatus ?: $dfTrx->supplier_status,
                'rc' => $rc ?: $dfTrx->rc,
                'message' => $message ?: $dfTrx->message,
                'serial_number' => $sn ?: $dfTrx->serial_number,
                'callback_received_at' => now(),
            ]);
        } else {
            $dfTrx = DigiflazzTransaction::create([
                'transaction_id' => $transaction?->id,
                'invoice_number' => $transaction?->invoice_number ?: $refId,
                'buyer_sku_code' => $data['buyer_sku_code'] ?? 'UNKNOWN',
                'customer_no' => $data['customer_no'] ?? 'UNKNOWN',
                'ref_id' => $refId,
                'callback_payload' => $payload,
                'supplier_status' => $supplierStatus,
                'rc' => $rc,
                'message' => $message,
                'serial_number' => $sn,
                'callback_received_at' => now(),
            ]);
        }

        if ($isAlreadyFinal) {
            Log::info('Digiflazz duplicate webhook ignored for final transaction: '.$refId);

            return response()->json([
                'status' => 'success',
                'message' => 'Duplicate callback acknowledged without modification.',
            ]);
        }

        if ($transaction) {
            if ($rc === '00' || $statusLower === 'sukses') {
                $transaction->update([
                    'transaction_status' => 'success',
                    'serial_number' => $sn ?: ($transaction->serial_number ?: 'SN-VERIFIED-'.date('YmdHis')),
                    'failure_reason' => null,
                ]);
            } elseif ($rc === '03' || $statusLower === 'pending') {
                $transaction->update([
                    'transaction_status' => 'processing',
                ]);
            } else {
                $transaction->update([
                    'transaction_status' => 'failed',
                    'failure_reason' => $message ?: 'Transaksi gagal di gateway provider',
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Digiflazz webhook processed successfully.',
            'ref_id' => $refId,
            'supplier_status' => $supplierStatus,
        ]);
    }
}
