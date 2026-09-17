<?php

namespace App\Contracts\Provider;

interface ProviderInterface
{
    public function inquiry(string $gameOrService, string $target, ?string $targetSecondary = null): array;

    public function purchase(string $providerSku, string $target, ?string $targetSecondary = null, string $refId = ''): array;

    public function checkStatus(string $providerRef): array;

    public function refund(string $providerRef): array;
}
