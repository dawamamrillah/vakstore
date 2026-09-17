<?php

namespace App\Services\Provider;

use App\Contracts\Provider\ProviderInterface;
use Illuminate\Support\Str;

class MockProviderAdapter implements ProviderInterface
{
    /**
     * Mock Inquiry for Account Checking (MLBB, FF, PLN, Pulsa, PDAM, Telkom, etc.)
     */
    public function inquiry(string $gameOrService, string $target, ?string $targetSecondary = null): array
    {
        $slug = strtolower(trim($gameOrService));

        $mockData = [
            'mobile-legends' => ['nickname' => 'RexRegum_Pro (Region: Indonesia)', 'is_bill' => false],
            'free-fire' => ['nickname' => 'VAK_GhostHunter (ID Verified)', 'is_bill' => false],
            'free-fire-max' => ['nickname' => 'VAK_MaxPredator (ID Verified)', 'is_bill' => false],
            'valorant' => ['nickname' => 'VAK_ViperAce#ID1', 'is_bill' => false],
            'honor-of-kings' => ['nickname' => 'HOK_DragonMaster', 'is_bill' => false],
            'call-of-duty-mobile' => ['nickname' => 'Ghost_SpecOps_ID', 'is_bill' => false],
            'efootball' => ['nickname' => 'Garuda_Eleven_FC', 'is_bill' => false],
            'pubg-mobile' => ['nickname' => 'VAK_ValiantKnight', 'is_bill' => false],
            'roblox' => ['nickname' => 'RobloxArchitect_99', 'is_bill' => false],
            'magic-chess-go-go' => ['nickname' => 'GrandMaster_Commander', 'is_bill' => false],
            'pln' => ['nickname' => 'BUDI SANTOSO / R1M-900VA', 'customer_name' => 'BUDI SANTOSO', 'bill_amount' => 245000, 'period' => 'September 2026', 'is_bill' => true, 'admin_fee' => 2500],
            'pln-pascabayar' => ['nickname' => 'BUDI SANTOSO / R1M-900VA', 'customer_name' => 'BUDI SANTOSO', 'bill_amount' => 245000, 'period' => 'September 2026', 'is_bill' => true, 'admin_fee' => 2500],
            'pulsa-all-operator' => ['nickname' => 'TELKOMSEL PRA-BAYAR (NOMOR AKTIF)', 'customer_name' => 'PELANGGAN TELKOMSEL', 'is_bill' => false],
            'pdam-nusantara' => ['nickname' => 'HENDRA WIJAYA - PDAM TIRTA MOEDAL', 'customer_name' => 'HENDRA WIJAYA', 'bill_amount' => 148500, 'period' => 'September 2026', 'is_bill' => true, 'admin_fee' => 2500],
            'telkom-indihome' => ['nickname' => 'BAMBANG HERMANTO - INDIHOME 50MBPS', 'customer_name' => 'BAMBANG HERMANTO', 'bill_amount' => 385000, 'period' => 'September 2026', 'is_bill' => true, 'admin_fee' => 2500],
        ];

        $matched = $mockData[$slug] ?? null;
        $nickname = $matched['nickname'] ?? ('User_'.substr($target, -4));
        $customerName = $matched['customer_name'] ?? $nickname;
        $isBill = $matched['is_bill'] ?? (str_contains($slug, 'pdam') || str_contains($slug, 'telkom') || str_contains($slug, 'pascabayar'));
        $billAmount = $matched['bill_amount'] ?? ($isBill ? 150000 : 0);
        $period = $matched['period'] ?? ($isBill ? 'September 2026' : null);
        $adminFee = $matched['admin_fee'] ?? ($isBill ? 2500 : 0);

        return [
            'status' => 'success',
            'target' => $target,
            'target_secondary' => $targetSecondary,
            'nickname' => $nickname,
            'customer_name' => $customerName,
            'bill_amount' => $billAmount,
            'period' => $period,
            'admin_fee' => $adminFee,
            'is_bill' => $isBill,
            'message' => 'Identitas akun & tagihan berhasil diverifikasi oleh server biller gateway.',
        ];
    }

    /**
     * Mock Purchase execution
     */
    public function purchase(string $providerSku, string $target, ?string $targetSecondary = null, string $refId = ''): array
    {
        $providerRef = 'MOCK-PRV-'.date('YmdHis').'-'.Str::upper(Str::random(6));
        $skuLower = strtolower($providerSku);

        // Generate realistic SN / Token Listrik if PLN
        if (str_contains($skuLower, 'pln') && ! str_contains($skuLower, 'postpaid')) {
            $serialNumber = sprintf(
                '%04d-%04d-%04d-%04d-%04d',
                rand(1000, 9999),
                rand(1000, 9999),
                rand(1000, 9999),
                rand(1000, 9999),
                rand(1000, 9999)
            );
        } elseif (str_contains($skuLower, 'postpaid') || str_contains($skuLower, 'bill') || str_contains($skuLower, 'pdam') || str_contains($skuLower, 'telkom')) {
            $serialNumber = 'LUNAS-'.strtoupper(Str::random(6)).'-REF'.rand(100000, 999999);
        } else {
            $serialNumber = 'SN-'.strtoupper(Str::random(12)).'-'.rand(1000, 9999);
        }

        return [
            'status' => 'success',
            'provider_reference' => $providerRef,
            'serial_number' => $serialNumber,
            'message' => 'Transaksi provider berhasil dikirim & diproses secara instan.',
            'raw_response' => [
                'provider' => 'VAK_MOCK_PROVIDER',
                'sku' => $providerSku,
                'target' => $target,
                'target_secondary' => $targetSecondary,
                'reference' => $refId,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Check status with provider
     */
    public function checkStatus(string $providerRef): array
    {
        return [
            'status' => 'success',
            'provider_reference' => $providerRef,
            'message' => 'Transaksi confirmed success on provider gateway.',
        ];
    }

    /**
     * Mock refund
     */
    public function refund(string $providerRef): array
    {
        return [
            'status' => 'success',
            'provider_reference' => $providerRef,
            'message' => 'Refund berhasil diproses pada provider.',
        ];
    }
}
