<?php

namespace App\Services\Provider;

use App\Contracts\Provider\ProviderInterface;

class GameTopupService
{
    protected ProviderInterface $provider;

    public function __construct(?ProviderInterface $provider = null)
    {
        $this->provider = $provider ?? new DigiflazzProviderAdapter;
    }

    public function checkAccount(string $gameSlug, string $userId, ?string $zoneId = null): array
    {
        return $this->provider->inquiry($gameSlug, $userId, $zoneId);
    }

    public function deliverGameProduct(string $sku, string $userId, ?string $zoneId = null, string $refId = ''): array
    {
        return $this->provider->purchase($sku, $userId, $zoneId, $refId);
    }
}
