<?php

namespace App\Services\Provider;

use App\Contracts\Provider\ProviderInterface;
use App\Models\PpobServiceOption;
use App\Models\Product;
use App\Services\Digiflazz\DigiflazzInquiryService;

class PpobService
{
    protected ProviderInterface $provider;

    protected DigiflazzInquiryService $inquiryService;

    public function __construct(
        ?ProviderInterface $provider = null,
        ?DigiflazzInquiryService $inquiryService = null
    ) {
        $this->provider = $provider ?? app(DigiflazzProviderAdapter::class);
        $this->inquiryService = $inquiryService ?? app(DigiflazzInquiryService::class);
    }

    /**
     * Inquiry postpaid bill via validated service option and product relation
     */
    public function inquiryByOption(Product $product, PpobServiceOption $option, string $customerNumber): array
    {
        if ($option->product_id !== $product->id) {
            return [
                'status' => 'error',
                'message' => 'Opsi layanan tidak sesuai dengan produk tagihan yang dipilih.',
            ];
        }

        if ($option->status !== 'active') {
            return [
                'status' => 'error',
                'message' => 'Opsi layanan yang dipilih saat ini sedang tidak aktif.',
            ];
        }

        return $this->inquiryService->inquiry($option->buyer_sku_code, $customerNumber);
    }

    /**
     * Direct inquiry by SKU (when SKU is verified from database)
     */
    public function inquiryDirect(string $buyerSkuCode, string $customerNumber): array
    {
        return $this->inquiryService->inquiry($buyerSkuCode, $customerNumber);
    }

    /**
     * Compatibility signature for backward-compatible calls
     */
    public function inquiryBill(string $buyerSkuCode, string $customerNumber, ?string $region = null): array
    {
        return $this->inquiryService->inquiry($buyerSkuCode, $customerNumber);
    }

    public function processPayment(string $sku, string $customerNumber, ?string $region = null, string $refId = ''): array
    {
        return $this->provider->purchase($sku, $customerNumber, $region, $refId);
    }
}
