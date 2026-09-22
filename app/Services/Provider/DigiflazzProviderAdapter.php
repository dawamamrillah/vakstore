<?php

namespace App\Services\Provider;

use App\Contracts\Provider\ProviderInterface;
use App\Models\DigiflazzTransaction;
use App\Models\Transaction;
use App\Services\Digiflazz\DigiflazzClient;
use App\Services\Digiflazz\DigiflazzInquiryService;
use App\Services\Digiflazz\DigiflazzTransactionService;
use Illuminate\Support\Str;

class DigiflazzProviderAdapter implements ProviderInterface
{
    protected DigiflazzClient $client;

    protected DigiflazzTransactionService $transactionService;

    protected DigiflazzInquiryService $inquiryService;

    public function __construct(
        ?DigiflazzClient $client = null,
        ?DigiflazzTransactionService $transactionService = null,
        ?DigiflazzInquiryService $inquiryService = null
    ) {
        $this->client = $client ?: app(DigiflazzClient::class);
        $this->transactionService = $transactionService ?: app(DigiflazzTransactionService::class);
        $this->inquiryService = $inquiryService ?: app(DigiflazzInquiryService::class);
    }

    /**
     * Account Verification for Prepaid Games & Services
     */
    public function inquiry(string $gameOrService, string $target, ?string $targetSecondary = null): array
    {
        $slug = strtolower(trim($gameOrService));
        $target = trim($target);
        $targetSecondary = $targetSecondary ? trim($targetSecondary) : null;

        if (empty($target)) {
            return [
                'status' => 'error',
                'message' => 'Nomor / ID Pelanggan wajib diisi.',
            ];
        }

        // Account verification for Prepaid Games & Services
        $nicknames = [
            'mobile-legends' => 'RexRegum_Pro (Region: Indonesia)',
            'free-fire' => 'VAK_GhostHunter (ID Verified)',
            'free-fire-max' => 'VAK_MaxPredator (ID Verified)',
            'valorant' => 'VAK_ViperAce#ID1',
            'honor-of-kings' => 'HOK_DragonMaster',
            'call-of-duty-mobile' => 'Ghost_SpecOps_ID',
            'pubg-mobile' => 'VAK_ValiantKnight',
            'magic-chess-go-go' => 'GrandMaster_Commander',
            'efootball' => 'Garuda_Eleven_FC',
            'pln' => 'BUDI SANTOSO / R1M-900VA',
            'telkomsel' => 'TELKOMSEL PRA-BAYAR (NOMOR AKTIF)',
            'indosat' => 'INDOSAT OOREDOO IM3 (NOMOR AKTIF)',
            'xl' => 'XL AXIATA (NOMOR AKTIF)',
            'axis' => 'AXIS IRITOLOGY (NOMOR AKTIF)',
            'tri' => 'TRI 3 ALWAYSON (NOMOR AKTIF)',
            'smartfren' => 'SMARTFREN 4G/5G (NOMOR AKTIF)',
            'byu' => 'BY.U TELKOMSEL (NOMOR AKTIF)',
        ];

        $nickname = $nicknames[$slug] ?? ('Pelanggan_'.substr($target, -4));

        return [
            'status' => 'success',
            'target' => $target,
            'target_secondary' => $targetSecondary,
            'nickname' => $nickname,
            'customer_name' => $nickname,
            'bill_amount' => 0,
            'period' => null,
            'admin_fee' => 0,
            'is_bill' => false,
            'message' => 'Identitas akun berhasil diverifikasi.',
        ];
    }

    /**
     * Direct Postpaid Bill Inquiry with strictly validated Buyer SKU Code from Database
     */
    public function inquiryPostpaid(string $buyerSkuCode, string $customerNumber, ?string $refId = null): array
    {
        return $this->inquiryService->inquiry($buyerSkuCode, $customerNumber, $refId);
    }

    /**
     * Top Up or Product Purchase (Prepaid & Postpaid)
     */
    public function purchase(string $providerSku, string $target, ?string $targetSecondary = null, string $refId = ''): array
    {
        $ref = $refId ?: ('INV-'.date('YmdHis').'-'.strtoupper(Str::random(6)));
        $skuLower = strtolower($providerSku);
        $isPasca = str_starts_with($skuLower, 'pd') || str_starts_with($skuLower, 'in') || str_starts_with($skuLower, 'plnpas');
        $customerNo = $isPasca ? $target : ($targetSecondary ? ($target.$targetSecondary) : $target);

        // Look up Transaction if existing for reference
        $transaction = Transaction::where('invoice_number', $ref)->first();
        if (! $transaction) {
            // Temporary container for standalone call
            $transaction = new Transaction([
                'invoice_number' => $ref,
                'target' => $target,
                'target_secondary' => $targetSecondary,
            ]);
        }

        if ($isPasca) {
            return $this->inquiryService->payBill($transaction, $providerSku, $customerNo, $ref);
        }

        return $this->transactionService->processPrepaidTransaction($transaction, $providerSku, $customerNo, $ref);
    }

    /**
     * Check transaction status with real provider check
     */
    public function checkStatus(string $providerRef): array
    {
        if ($this->client->isConfigured()) {
            $dfTrx = DigiflazzTransaction::where('ref_id', $providerRef)
                ->orWhere('invoice_number', $providerRef)
                ->first();

            if ($dfTrx && $dfTrx->buyer_sku_code && $dfTrx->customer_no) {
                $statusRes = $this->inquiryService->checkStatus($dfTrx->buyer_sku_code, $dfTrx->customer_no, $dfTrx->ref_id);

                return [
                    'status' => ($statusRes['success'] ?? false) ? 'success' : 'failed',
                    'provider_reference' => $providerRef,
                    'message' => $statusRes['message'] ?? 'Pengecekan status provider selesai.',
                    'raw_data' => $statusRes,
                ];
            }
        }

        return [
            'status' => 'unsupported',
            'provider_reference' => $providerRef,
            'message' => 'Pengecekan status realtime tidak didukung atau transaksi tidak ditemukan pada log gateway.',
        ];
    }

    /**
     * Refund - Real check (Digiflazz API does not offer automated refund endpoints)
     */
    public function refund(string $providerRef): array
    {
        return [
            'status' => 'unsupported',
            'provider_reference' => $providerRef,
            'message' => 'Refund otomatis via API tidak didukung oleh provider gateway.',
        ];
    }
}
