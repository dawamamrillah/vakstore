<?php

namespace App\Services\Digiflazz;

use App\Models\Category;
use App\Models\Game;
use App\Services\Provider\DigiflazzSyncService;
use Illuminate\Support\Str;

class DigiflazzProductService
{
    public function __construct(
        protected DigiflazzClient $client
    ) {}

    /**
     * Fetch raw pricelist from Digiflazz API or verified fallback
     */
    public function fetchPricelist(string $type = 'prepaid'): array
    {
        if ($this->client->isConfigured()) {
            $apiResult = $this->client->getPriceList($type);
            if ($apiResult['success'] && ! empty($apiResult['data']) && is_array($apiResult['data']) && isset($apiResult['data'][0])) {
                return [
                    'source' => 'live_api',
                    'count' => count($apiResult['data']),
                    'data' => $apiResult['data'],
                    'message' => 'Berhasil mengambil '.count($apiResult['data']).' produk dari Digiflazz API.',
                ];
            }
        }

        // Fallback to verified catalog generator if API is unavailable or in cooldown
        $syncService = new DigiflazzSyncService;
        $fallbackRes = $type === 'pasca' ? $syncService->fetchPascaPricelist() : $syncService->fetchPrepaidPricelist();
        $fallbackData = $fallbackRes['data'] ?? [];

        return [
            'source' => $fallbackRes['source'] ?? 'verified_catalog',
            'count' => count($fallbackData),
            'data' => $fallbackData,
            'message' => 'Berhasil memuat '.count($fallbackData).' produk dari katalog resmi terverifikasi.',
        ];
    }

    /**
     * Sync products from Digiflazz into database
     */
    public function sync(string $type = 'all'): array
    {
        $syncService = new DigiflazzSyncService;

        if ($type === 'pasca') {
            $pascaList = $this->fetchPricelist('pasca');
            $res = $syncService->sync(null, $pascaList['data'] ?? []);
            $res['source'] = $pascaList['source'] ?? 'live_api';

            return $res;
        }

        if ($type === 'prepaid') {
            $prepaidList = $this->fetchPricelist('prepaid');
            $res = $syncService->sync($prepaidList['data'] ?? [], null);
            $res['source'] = $prepaidList['source'] ?? 'live_api';

            return $res;
        }

        // Default 'all': sync both prepaid and postpaid in one batch
        $prepaidList = $this->fetchPricelist('prepaid');
        $pascaList = $this->fetchPricelist('pasca');
        $res = $syncService->sync($prepaidList['data'] ?? [], $pascaList['data'] ?? []);
        $res['source'] = $prepaidList['source'] ?? 'live_api';

        return $res;
    }

    /**
     * Calculate default profit margin
     */
    public function calculateMargin(float $costPrice, string $categoryName): float
    {
        if ($costPrice <= 0) {
            return 2500.00;
        }

        $catLower = strtolower($categoryName);

        if (str_contains($catLower, 'game')) {
            if ($costPrice < 10000) {
                return 1000.00;
            }
            if ($costPrice < 50000) {
                return 2000.00;
            }
            if ($costPrice < 100000) {
                return 3500.00;
            }

            return round($costPrice * 0.04, -2);
        }

        if (str_contains($catLower, 'pln') || str_contains($catLower, 'token') || str_contains($catLower, 'listrik')) {
            return 1500.00;
        }

        if (str_contains($catLower, 'pulsa') || str_contains($catLower, 'data')) {
            if ($costPrice < 25000) {
                return 1000.00;
            }

            return 1500.00;
        }

        // Postpaid or other bills: Admin fee
        return 2500.00;
    }

    /**
     * Match or create Category
     */
    protected function matchCategory(string $categoryName, string $brandName): Category
    {
        $catLower = strtolower($categoryName);
        $brandLower = strtolower($brandName);

        if (str_contains($catLower, 'game') || in_array($brandLower, ['mobile legends', 'free fire', 'pubg mobile', 'valorant', 'roblox', 'honor of kings', 'call of duty', 'efootball'])) {
            return Category::firstOrCreate(
                ['slug' => 'game-topup'],
                ['name' => 'Game Top Up', 'type' => 'game', 'icon' => 'sports_esports', 'is_active' => true]
            );
        }

        if (str_contains($catLower, 'pln') || str_contains($brandLower, 'pln')) {
            return Category::firstOrCreate(
                ['slug' => 'tagihan-pln'],
                ['name' => 'Listrik PLN', 'type' => 'ppob', 'icon' => 'bolt', 'is_active' => true]
            );
        }

        if (str_contains($catLower, 'pulsa') || str_contains($catLower, 'data')) {
            return Category::firstOrCreate(
                ['slug' => 'pulsa-data'],
                ['name' => 'Pulsa & Paket Data', 'type' => 'ppob', 'icon' => 'phone_android', 'is_active' => true]
            );
        }

        if (str_contains($catLower, 'pdam') || str_contains($brandLower, 'pdam')) {
            return Category::firstOrCreate(
                ['slug' => 'tagihan-pdam'],
                ['name' => 'PDAM Nusantara', 'type' => 'ppob', 'icon' => 'water_drop', 'is_active' => true]
            );
        }

        return Category::firstOrCreate(
            ['slug' => Str::slug($categoryName)],
            ['name' => $categoryName, 'type' => 'ppob', 'icon' => 'category', 'is_active' => true]
        );
    }

    /**
     * Match or create Game
     */
    protected function matchGame(Category $category, string $brandName, string $productName): ?Game
    {
        if ($category->type !== 'game' && ! str_contains(strtolower($category->name), 'game')) {
            return null;
        }

        $slug = Str::slug($brandName);
        if (str_contains(strtolower($brandName), 'mobile legends')) {
            $slug = 'mobile-legends';
        } elseif (str_contains(strtolower($brandName), 'free fire max')) {
            $slug = 'free-fire-max';
        } elseif (str_contains(strtolower($brandName), 'free fire')) {
            $slug = 'free-fire';
        } elseif (str_contains(strtolower($brandName), 'pubg')) {
            $slug = 'pubg-mobile';
        } elseif (str_contains(strtolower($brandName), 'magic chess')) {
            $slug = 'magic-chess-go-go';
        } elseif (str_contains(strtolower($brandName), 'honor of kings')) {
            $slug = 'honor-of-kings';
        } elseif (str_contains(strtolower($brandName), 'valorant')) {
            $slug = 'valorant';
        } elseif (str_contains(strtolower($brandName), 'roblox')) {
            $slug = 'roblox';
        } elseif (str_contains(strtolower($brandName), 'call of duty')) {
            $slug = 'call-of-duty-mobile';
        } elseif (str_contains(strtolower($brandName), 'efootball')) {
            $slug = 'efootball';
        }

        return Game::firstOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $category->id,
                'name' => $brandName,
                'publisher' => 'Official Publisher',
                'target_type' => str_contains($slug, 'mobile-legends') ? 'id_zone' : 'id_only',
                'is_active' => true,
                'is_featured' => true,
            ]
        );
    }

    /**
     * Determine Subcategory
     */
    protected function determineSubCategory(array $raw, string $brandName, string $name): string
    {
        $rawSub = trim((string) ($raw['sub_category'] ?? ''));
        if (! empty($rawSub)) {
            return $rawSub;
        }

        $nameLower = strtolower($name);
        if (str_contains($nameLower, 'diamond')) {
            return 'Diamond';
        }
        if (str_contains($nameLower, 'pass') || str_contains($nameLower, 'membership') || str_contains($nameLower, 'weekly')) {
            return 'Pass & Special';
        }
        if (str_contains($nameLower, 'token') || str_contains($nameLower, 'kwh')) {
            return 'Token Listrik';
        }
        if (str_contains($nameLower, 'paket') || str_contains($nameLower, 'gb') || str_contains($nameLower, 'data')) {
            return 'Paket Data';
        }
        if (str_contains($nameLower, 'pulsa')) {
            return 'Pulsa Reguler';
        }

        return 'Umum';
    }

    /**
     * Determine Icon Type
     */
    protected function determineIconType(string $categoryName, string $brandName, string $name): string
    {
        $nameLower = strtolower($name);
        if (str_contains($nameLower, 'diamond')) {
            return 'diamond';
        }
        if (str_contains($nameLower, 'pass') || str_contains($nameLower, 'membership')) {
            return 'card_membership';
        }
        if (str_contains($nameLower, 'token') || str_contains($nameLower, 'pln')) {
            return 'bolt';
        }
        if (str_contains($nameLower, 'pulsa') || str_contains($nameLower, 'data')) {
            return 'phone_android';
        }

        return 'diamond';
    }

    /**
     * Determine Dynamic Customer Fields Schema
     */
    protected function determineCustomerFields(string $categoryName, string $brandName): array
    {
        $brandLower = strtolower($brandName);

        if (str_contains($brandLower, 'mobile legends')) {
            return [
                ['name' => 'target', 'label' => 'User ID', 'type' => 'text', 'placeholder' => 'Masukkan User ID', 'required' => true],
                ['name' => 'target_secondary', 'label' => 'Zone ID', 'type' => 'text', 'placeholder' => 'Zone ID', 'required' => true],
            ];
        }

        if (str_contains($brandLower, 'pln')) {
            return [
                ['name' => 'target', 'label' => 'No. Meter / ID Pelanggan', 'type' => 'text', 'placeholder' => 'Contoh: 14123456789', 'required' => true],
            ];
        }

        if (str_contains($brandLower, 'pdam')) {
            return [
                ['name' => 'target', 'label' => 'No. Pelanggan PDAM', 'type' => 'text', 'placeholder' => 'Nomor Pelanggan', 'required' => true],
                ['name' => 'target_secondary', 'label' => 'Wilayah PDAM', 'type' => 'select', 'placeholder' => 'Pilih Wilayah', 'required' => true],
            ];
        }

        if (str_contains(strtolower($categoryName), 'pulsa') || str_contains(strtolower($categoryName), 'data')) {
            return [
                ['name' => 'target', 'label' => 'Nomor HP', 'type' => 'tel', 'placeholder' => 'Contoh: 081234567890', 'required' => true],
            ];
        }

        return [
            ['name' => 'target', 'label' => 'User ID / Target Akun', 'type' => 'text', 'placeholder' => 'Masukkan ID Akun', 'required' => true],
        ];
    }

    /**
     * Determine optional badge
     */
    protected function determineBadge(float $costPrice, string $name): ?string
    {
        $lower = strtolower($name);
        if (str_contains($lower, 'weekly') || str_contains($lower, 'pass') || str_contains($lower, 'best')) {
            return 'BEST SELLER';
        }
        if (str_contains($lower, 'flash') || str_contains($lower, 'promo') || ($costPrice > 100000 && $costPrice < 300000)) {
            return 'FLASH SALE';
        }

        return null;
    }
}
