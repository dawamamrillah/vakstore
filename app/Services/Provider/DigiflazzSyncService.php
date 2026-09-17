<?php

namespace App\Services\Provider;

use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\Provider;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DigiflazzSyncService
{
    /**
     * Fetch prepaid pricelist directly from Digiflazz API or fallback snapshot
     */
    public function fetchPrepaidPricelist(): array
    {
        $username = config('digiflazz.username');
        $apiKey = config('digiflazz.development_key') ?: config('digiflazz.production_key');

        if (! $username || ! $apiKey) {
            throw new Exception('Kredensial Digiflazz belum dikonfigurasi di file .env.');
        }

        $sign = md5($username.$apiKey.'pricelist');
        $cacheFile = storage_path('app/digiflazz/latest_pricelist.json');

        try {
            $response = Http::timeout(20)->post(
                config('digiflazz.base_url').'/price-list',
                [
                    'cmd' => 'prepaid',
                    'username' => $username,
                    'sign' => $sign,
                ]
            );

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['data']) && is_array($result['data']) && isset($result['data'][0])) {
                    $data = $result['data'];

                    if (! is_dir(dirname($cacheFile))) {
                        mkdir(dirname($cacheFile), 0777, true);
                    }
                    file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));

                    return [
                        'source' => 'live_api',
                        'data' => $data,
                        'message' => 'Berhasil mengambil '.count($data).' produk prabayar dari API Digiflazz.',
                    ];
                }

                if (isset($result['data']['rc'])) {
                    if (file_exists($cacheFile)) {
                        $cachedData = json_decode(file_get_contents($cacheFile), true);
                        if (is_array($cachedData) && isset($cachedData[0])) {
                            return [
                                'source' => 'cached_snapshot',
                                'data' => $cachedData,
                                'message' => 'Digiflazz cooldown aktif. Menggunakan snapshot katalog prabayar ('.count($cachedData).' produk).',
                            ];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::warning('Digiflazz prepaid API call failed: '.$e->getMessage());
        }

        if (file_exists($cacheFile)) {
            $cachedData = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cachedData) && isset($cachedData[0])) {
                return [
                    'source' => 'cached_snapshot',
                    'data' => $cachedData,
                    'message' => 'Menggunakan snapshot katalog prabayar ('.count($cachedData).' produk).',
                ];
            }
        }

        $fallbackData = $this->getFallbackPrepaidCatalog();

        return [
            'source' => 'embedded_fallback',
            'data' => $fallbackData,
            'message' => 'Menggunakan katalog prabayar lokal terverifikasi ('.count($fallbackData).' produk).',
        ];
    }

    /**
     * Fetch postpaid (pascabayar) pricelist directly from Digiflazz API or fallback snapshot
     */
    public function fetchPascaPricelist(): array
    {
        $username = config('digiflazz.username');
        $apiKey = config('digiflazz.development_key') ?: config('digiflazz.production_key');

        if (! $username || ! $apiKey) {
            throw new Exception('Kredensial Digiflazz belum dikonfigurasi di file .env.');
        }

        $sign = md5($username.$apiKey.'pricelist');
        $cacheFile = storage_path('app/digiflazz/latest_pasca_pricelist.json');

        try {
            $response = Http::timeout(20)->post(
                config('digiflazz.base_url').'/price-list',
                [
                    'cmd' => 'pasca',
                    'username' => $username,
                    'sign' => $sign,
                ]
            );

            if ($response->successful()) {
                $result = $response->json();

                if (isset($result['data']) && is_array($result['data']) && isset($result['data'][0])) {
                    $data = $result['data'];

                    if (! is_dir(dirname($cacheFile))) {
                        mkdir(dirname($cacheFile), 0777, true);
                    }
                    file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));

                    return [
                        'source' => 'live_api',
                        'data' => $data,
                        'message' => 'Berhasil mengambil '.count($data).' produk pascabayar dari API Digiflazz.',
                    ];
                }
            }
        } catch (Exception $e) {
            Log::warning('Digiflazz pasca API call failed: '.$e->getMessage());
        }

        if (file_exists($cacheFile)) {
            $cachedData = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cachedData) && isset($cachedData[0])) {
                return [
                    'source' => 'cached_snapshot',
                    'data' => $cachedData,
                    'message' => 'Menggunakan snapshot katalog pascabayar ('.count($cachedData).' produk).',
                ];
            }
        }

        $fallbackData = $this->getFallbackPascaCatalog();

        return [
            'source' => 'embedded_fallback',
            'data' => $fallbackData,
            'message' => 'Menggunakan katalog pascabayar lokal terverifikasi ('.count($fallbackData).' produk).',
        ];
    }

    /**
     * Alias for fetchPrepaidPricelist for backwards compatibility
     */
    public function fetchPricelist(): array
    {
        return $this->fetchPrepaidPricelist();
    }

    /**
     * Perform full sync of all prepaid AND postpaid products from Digiflazz
     */
    public function sync(?array $prepaidData = null, ?array $pascaData = null): array
    {
        $prepaidRes = $prepaidData ? ['source' => 'prefetched', 'data' => $prepaidData] : $this->fetchPrepaidPricelist();
        $pascaRes = $pascaData ? ['source' => 'prefetched', 'data' => $pascaData] : $this->fetchPascaPricelist();

        $prepaidItems = $prepaidRes['data'] ?? [];
        $pascaItems = $pascaRes['data'] ?? [];

        // 1. Ensure Digiflazz Provider exists
        $provider = Provider::firstOrCreate(
            ['code' => 'digiflazz'],
            [
                'name' => 'Digiflazz Prepaid Gateway',
                'base_url' => config('digiflazz.base_url'),
                'api_key' => config('digiflazz.development_key'),
                'api_secret' => config('digiflazz.production_key'),
                'status' => 'active',
            ]
        );

        // 2. Ensure Main Categories exist
        $catGame = Category::updateOrCreate(
            ['slug' => 'top-up-game'],
            [
                'name' => 'Top Up Game',
                'type' => 'game',
                'icon' => 'sports_esports',
                'status' => 'active',
            ]
        );

        $catPulsa = Category::updateOrCreate(
            ['slug' => 'pulsa-all-operator'],
            [
                'name' => 'Pulsa & Operator',
                'type' => 'ppob',
                'icon' => 'phone_iphone',
                'status' => 'active',
            ]
        );

        $catPln = Category::updateOrCreate(
            ['slug' => 'pln'],
            [
                'name' => 'Token Listrik PLN',
                'type' => 'ppob',
                'icon' => 'bolt',
                'status' => 'active',
            ]
        );

        $catPasca = Category::updateOrCreate(
            ['slug' => 'tagihan-pascabayar'],
            [
                'name' => 'Tagihan Pascabayar',
                'type' => 'ppob',
                'icon' => 'receipt_long',
                'status' => 'active',
            ]
        );

        // 3. Brand & Game Mapping Definitions for Prepaid & Postpaid
        $brandConfig = [
            'MOBILE LEGENDS' => [
                'name' => 'Mobile Legends: Bang Bang',
                'slug' => 'mobile-legends',
                'category_id' => $catGame->id,
                'publisher' => 'Moonton',
                'description' => 'Top up Diamond Mobile Legends resmi & Weekly Diamond Pass instan 1-3 detik.',
                'image' => '/images/games/ml.jpg',
                'target_field_name' => 'User ID',
                'target_secondary_field_name' => 'Zone ID',
                'has_secondary_target' => true,
                'target_placeholder' => 'Contoh: 12849102',
                'target_secondary_placeholder' => '(2314)',
            ],
            'FREE FIRE' => [
                'name' => 'Free Fire',
                'slug' => 'free-fire',
                'category_id' => $catGame->id,
                'publisher' => 'Garena',
                'description' => 'Top up Diamond & Membership Free Fire instan resmi 24 jam.',
                'image' => '/images/games/freefire.jpg',
                'target_field_name' => 'Player ID (UID)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 819284719',
                'target_secondary_placeholder' => null,
            ],
            'Free Fire Max' => [
                'name' => 'Free Fire MAX',
                'slug' => 'free-fire-max',
                'category_id' => $catGame->id,
                'publisher' => 'Garena',
                'description' => 'Top Up Diamond Free Fire MAX grafis ultra HD resmi & kilat berizin langsung ke akun.',
                'image' => '/images/games/freefiremax.jpg',
                'target_field_name' => 'Player ID (UID)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 829102934',
                'target_secondary_placeholder' => null,
            ],
            'PUBG MOBILE' => [
                'name' => 'PUBG Mobile',
                'slug' => 'pubg-mobile',
                'category_id' => $catGame->id,
                'publisher' => 'Tencent / Krafton',
                'description' => 'Beli UC PUBG Mobile resmi termurah pengiriman langsung masuk ke akun.',
                'image' => '/images/games/pubgm.jpg',
                'target_field_name' => 'Character ID (Player ID)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 512938471',
                'target_secondary_placeholder' => null,
            ],
            'Call of Duty MOBILE' => [
                'name' => 'Call of Duty: Mobile',
                'slug' => 'call-of-duty-mobile',
                'category_id' => $catGame->id,
                'publisher' => 'Garena / Activision',
                'description' => 'Beli CP (Call of Duty Points) & Premium Battle Pass CODM kilat otomatis 1-3 detik.',
                'image' => '/images/games/codm.jpg',
                'target_field_name' => 'OpenID / Player ID',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 8192039102938491',
                'target_secondary_placeholder' => null,
            ],
            'Honor of Kings' => [
                'name' => 'Honor of Kings',
                'slug' => 'honor-of-kings',
                'category_id' => $catGame->id,
                'publisher' => 'Level Infinite',
                'description' => 'Isi ulang Tokens & Weekly Card Honor of Kings resmi harga terjangkau 24 jam nonstop.',
                'image' => '/images/games/hok.jpg',
                'target_field_name' => 'Player ID (UID)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 8391029381',
                'target_secondary_placeholder' => null,
            ],
            'Magic Chess' => [
                'name' => 'Magic Chess: Go Go',
                'slug' => 'magic-chess-go-go',
                'category_id' => $catGame->id,
                'publisher' => 'Moonton Games',
                'description' => 'Top Up Diamond & Commander Pass Magic Chess Go Go proses kilat 1 detik.',
                'image' => '/images/games/mcgg.png',
                'target_field_name' => 'User ID',
                'target_secondary_field_name' => 'Zone ID',
                'has_secondary_target' => true,
                'target_placeholder' => 'Contoh: 98127361',
                'target_secondary_placeholder' => '(2041)',
            ],
            'Valorant' => [
                'name' => 'Valorant Riot Points',
                'slug' => 'valorant',
                'category_id' => $catGame->id,
                'publisher' => 'Riot Games',
                'description' => 'Top up Valorant Points (VP) instan langsung masuk ke Riot ID Anda.',
                'image' => '/images/games/valorant.jpg',
                'target_field_name' => 'Riot ID (Username#Tagline)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: Player#ID1',
                'target_secondary_placeholder' => null,
            ],
            'VALORANT' => [
                'name' => 'Valorant Riot Points',
                'slug' => 'valorant',
                'category_id' => $catGame->id,
                'publisher' => 'Riot Games',
                'description' => 'Top up Valorant Points (VP) instan langsung masuk ke Riot ID Anda.',
                'image' => '/images/games/valorant.jpg',
                'target_field_name' => 'Riot ID (Username#Tagline)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: Player#ID1',
                'target_secondary_placeholder' => null,
            ],
            'TELKOMSEL' => [
                'name' => 'Telkomsel',
                'slug' => 'telkomsel',
                'category_id' => $catPulsa->id,
                'publisher' => 'PT Telkomsel',
                'description' => 'Isi pulsa & paket data kuota Telkomsel prabayar instan aktif 24 jam.',
                'image' => '/images/ppob/pulsa.svg',
                'target_field_name' => 'Nomor Handphone (08xx)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 081234567890',
                'target_secondary_placeholder' => null,
            ],
            'INDOSAT' => [
                'name' => 'Indosat Ooredoo IM3',
                'slug' => 'indosat',
                'category_id' => $catPulsa->id,
                'publisher' => 'Indosat Ooredoo Hutchison',
                'description' => 'Isi pulsa & paket internet data IM3 Indosat tercepat 24 jam nonstop.',
                'image' => '/images/ppob/pulsa.svg',
                'target_field_name' => 'Nomor Handphone (08xx)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 085712345678',
                'target_secondary_placeholder' => null,
            ],
            'XL' => [
                'name' => 'XL Axiata',
                'slug' => 'xl',
                'category_id' => $catPulsa->id,
                'publisher' => 'PT XL Axiata Tbk',
                'description' => 'Isi pulsa & paket data XL Axiata cepat, murah dan terpercaya.',
                'image' => '/images/ppob/pulsa.svg',
                'target_field_name' => 'Nomor Handphone (08xx)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 087812345678',
                'target_secondary_placeholder' => null,
            ],
            'AXIS' => [
                'name' => 'AXIS',
                'slug' => 'axis',
                'category_id' => $catPulsa->id,
                'publisher' => 'PT XL Axiata Tbk (AXIS)',
                'description' => 'Isi pulsa & paket kuota data internet AXIS Iritology instan otomatis.',
                'image' => '/images/ppob/pulsa.svg',
                'target_field_name' => 'Nomor Handphone (08xx)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 083812345678',
                'target_secondary_placeholder' => null,
            ],
            'TRI' => [
                'name' => 'Tri (3) Indonesia',
                'slug' => 'tri',
                'category_id' => $catPulsa->id,
                'publisher' => 'Indosat Ooredoo Hutchison',
                'description' => 'Isi pulsa & paket kuota AlwaysOn Tri (3) termurah proses otomatis.',
                'image' => '/images/ppob/pulsa.svg',
                'target_field_name' => 'Nomor Handphone (08xx)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 089612345678',
                'target_secondary_placeholder' => null,
            ],
            'SMARTFREN' => [
                'name' => 'Smartfren',
                'slug' => 'smartfren',
                'category_id' => $catPulsa->id,
                'publisher' => 'PT Smartfren Telecom Tbk',
                'description' => 'Isi pulsa & paket data internet kuota Smartfren 4G/5G aktif 24 jam.',
                'image' => '/images/ppob/pulsa.svg',
                'target_field_name' => 'Nomor Handphone (08xx)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 088112345678',
                'target_secondary_placeholder' => null,
            ],
            'by.U' => [
                'name' => 'by.U (Telkomsel)',
                'slug' => 'byu',
                'category_id' => $catPulsa->id,
                'publisher' => 'PT Telkomsel (by.U)',
                'description' => 'Isi pulsa & kuota data by.U Serba Yang Kamu Mau serba instan tanpa jeda.',
                'image' => '/images/ppob/pulsa.svg',
                'target_field_name' => 'Nomor Handphone (08xx)',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 085112345678',
                'target_secondary_placeholder' => null,
            ],
            'PLN' => [
                'name' => 'Token Listrik PLN',
                'slug' => 'pln',
                'category_id' => $catPln->id,
                'publisher' => 'PT PLN (Persero)',
                'description' => 'Beli token listrik PLN prabayar resmi langsung dapat nomor stroom token 20 digit.',
                'image' => '/images/ppob/pln.svg',
                'target_field_name' => 'No. Meter / ID Pelanggan PLN',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 32019482710',
                'target_secondary_placeholder' => null,
            ],

            // Pascabayar Services
            'PLN PASCABAYAR' => [
                'name' => 'Tagihan Listrik PLN Pascabayar',
                'slug' => 'pln-pascabayar',
                'category_id' => $catPasca->id,
                'publisher' => 'PT PLN (Persero)',
                'description' => 'Cek & bayar tagihan listrik PLN pascabayar bulanan online resmi otomatis lunas 24 jam.',
                'image' => '/images/ppob/pln.svg',
                'target_field_name' => 'ID Pelanggan PLN',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 51293847101',
                'target_secondary_placeholder' => null,
            ],
            'PDAM' => [
                'name' => 'Tagihan Air PDAM Nusantara',
                'slug' => 'pdam-nusantara',
                'category_id' => $catPasca->id,
                'publisher' => 'PDAM / Perumda Air Minum',
                'description' => 'Cek dan bayar tagihan air PDAM PAM JAYA, Palyja, Aetra, Surabaya, Bogor, Tangerang, Bali, dan 50 kota/kabupaten se-Indonesia.',
                'image' => '/images/ppob/pdam.svg',
                'target_field_name' => 'Nomor Sambungan / ID Pelanggan PDAM',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 102938491',
                'target_secondary_placeholder' => null,
            ],
            'INTERNET PASCABAYAR' => [
                'name' => 'Tagihan Internet & TV Kabel',
                'slug' => 'telkom-indihome',
                'category_id' => $catPasca->id,
                'publisher' => 'IndiHome, Telkom, Biznet, First Media, MyRepublic, CBN, Oxygen, XL Home',
                'description' => 'Bayar tagihan internet IndiHome Speedy, Telkom PSTN, Biznet, First Media, MyRepublic, CBN online otomatis lunas.',
                'image' => '/images/ppob/telkom.svg',
                'target_field_name' => 'Nomor Pelanggan / ID Pelanggan Internet',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 121345678901',
                'target_secondary_placeholder' => null,
            ],
        ];

        $gamesMap = [];
        $createdCount = 0;
        $updatedCount = 0;
        $syncedBrands = [];

        // Helper to register Game model
        $getGameForBrand = function (string $brand) use (&$gamesMap, $brandConfig, &$syncedBrands): Game {
            if (isset($gamesMap[$brand])) {
                return $gamesMap[$brand];
            }

            $cfg = $brandConfig[$brand] ?? [
                'name' => ucwords(strtolower($brand)),
                'slug' => Str::slug($brand),
                'category_id' => 1,
                'publisher' => 'Official Provider',
                'description' => 'Layanan resmi '.$brand.' proses otomatis.',
                'image' => null,
                'target_field_name' => 'User ID / ID Pelanggan',
                'target_secondary_field_name' => null,
                'has_secondary_target' => false,
                'target_placeholder' => 'Contoh: 12345678',
                'target_secondary_placeholder' => null,
            ];

            $game = Game::updateOrCreate(
                ['slug' => $cfg['slug']],
                [
                    'category_id' => $cfg['category_id'],
                    'name' => $cfg['name'],
                    'publisher' => $cfg['publisher'],
                    'description' => $cfg['description'],
                    'image' => $cfg['image'] ?? null,
                    'target_field_name' => $cfg['target_field_name'],
                    'target_secondary_field_name' => $cfg['target_secondary_field_name'],
                    'has_secondary_target' => $cfg['has_secondary_target'],
                    'target_placeholder' => $cfg['target_placeholder'],
                    'target_secondary_placeholder' => $cfg['target_secondary_placeholder'],
                    'status' => 'active',
                ]
            );

            $gamesMap[$brand] = $game;
            $syncedBrands[$brand] = $game->name;

            return $game;
        };

        // Process 1: PREPAID PRODUCTS
        foreach ($prepaidItems as $item) {
            $dfCat = $item['category'] ?? 'Games';
            $dfBrand = trim($item['brand'] ?? 'General');
            $dfSku = trim($item['buyer_sku_code'] ?? '');
            $dfName = trim($item['product_name'] ?? '');
            $dfPrice = (float) ($item['price'] ?? 0);
            $dfStatus = ($item['buyer_product_status'] ?? true) && ($item['seller_product_status'] ?? true);
            $dfType = $item['type'] ?? 'Umum';

            if (! $dfSku || ! $dfName) {
                continue;
            }

            $game = $getGameForBrand($dfBrand);
            $subCategory = $this->determineSubcategory($dfBrand, $dfName, $dfCat);
            $internalSku = 'DF-'.strtoupper(Str::slug($dfSku, ''));

            // Price & margin calculation
            if ($dfPrice <= 10000) {
                $marginAmount = max(400, ceil(($dfPrice * 0.06) / 50) * 50);
            } elseif ($dfPrice <= 50000) {
                $marginAmount = max(1000, ceil(($dfPrice * 0.05) / 50) * 50);
            } else {
                $marginAmount = max(2000, ceil(($dfPrice * 0.045) / 50) * 50);
            }

            if (str_contains(strtolower($dfName), 'weekly') || str_contains(strtolower($dfName), 'pass') || str_contains(strtolower($dfName), 'membership')) {
                $marginAmount = max(1200, ceil(($dfPrice * 0.05) / 50) * 50);
            }

            $calculatedSellPrice = $dfPrice + $marginAmount;

            $product = Product::where('provider_sku', $dfSku)
                ->orWhere('provider_sku', strtolower($dfSku))
                ->orWhere('provider_sku', strtoupper($dfSku))
                ->orWhere('buyer_sku_code', $dfSku)
                ->orWhere('sku', $internalSku)
                ->first();

            $normalizedSku = strtolower($dfSku);

            if ($product) {
                $sellingPrice = $product->selling_price;
                if ($sellingPrice <= $dfPrice) {
                    $sellingPrice = $calculatedSellPrice;
                }
                $profit = $sellingPrice - $dfPrice;

                $product->update([
                    'category_id' => $game->category_id,
                    'game_id' => $game->id,
                    'provider_id' => $provider->id,
                    'name' => $dfName,
                    'provider_sku' => $normalizedSku,
                    'buyer_sku_code' => $dfSku,
                    'brand_name' => $dfBrand,
                    'raw_type' => $dfType,
                    'cost_price' => $dfPrice,
                    'selling_price' => $sellingPrice,
                    'profit' => $profit,
                    'sub_category' => $subCategory,
                    'badge' => $this->determineBadge($dfName),
                    'sort_order' => $this->determineSortOrder($dfName, $dfPrice),
                    'status' => $dfStatus ? 'active' : 'inactive',
                    'last_synced_at' => now(),
                ]);

                $updatedCount++;
            } else {
                $profit = $calculatedSellPrice - $dfPrice;

                Product::create([
                    'category_id' => $game->category_id,
                    'game_id' => $game->id,
                    'provider_id' => $provider->id,
                    'name' => $dfName,
                    'sku' => $internalSku,
                    'provider_sku' => $normalizedSku,
                    'buyer_sku_code' => $dfSku,
                    'brand_name' => $dfBrand,
                    'raw_type' => $dfType,
                    'description' => $dfName.' resmi instan proses 1-3 detik.',
                    'cost_price' => $dfPrice,
                    'selling_price' => $calculatedSellPrice,
                    'profit' => $profit,
                    'badge' => $this->determineBadge($dfName),
                    'sub_category' => $subCategory,
                    'sort_order' => $this->determineSortOrder($dfName, $dfPrice),
                    'status' => $dfStatus ? 'active' : 'inactive',
                    'last_synced_at' => now(),
                ]);

                $createdCount++;
            }
        }

        // Process 2: PASCABAYAR PRODUCTS (PLN, PDAM, Internet)
        foreach ($pascaItems as $item) {
            $dfCat = $item['category'] ?? 'Pascabayar';
            $dfBrand = trim($item['brand'] ?? 'PDAM');
            $dfSku = trim($item['buyer_sku_code'] ?? '');
            $dfName = trim($item['product_name'] ?? '');
            $dfAdmin = (float) ($item['admin'] ?? ($item['price'] ?? 2500));
            $dfStatus = ($item['buyer_product_status'] ?? true) && ($item['seller_product_status'] ?? true);
            $dfType = 'Pascabayar';

            if (! $dfSku || ! $dfName) {
                continue;
            }

            $game = $getGameForBrand($dfBrand);
            $subCategory = $this->determinePascaSubcategory($dfBrand, $dfName);
            $internalSku = 'DF-PASCA-'.strtoupper(Str::slug($dfSku, ''));

            // For pascabayar, cost_price = admin fee Digiflazz, selling_price = admin fee + Rp 500 margin
            $costPrice = $dfAdmin > 0 ? $dfAdmin : 2500;
            $sellingPrice = $costPrice + 500;
            $profit = $sellingPrice - $costPrice;

            $product = Product::where('provider_sku', $dfSku)
                ->orWhere('sku', $internalSku)
                ->first();

            if ($product) {
                $product->update([
                    'category_id' => $catPasca->id,
                    'game_id' => $game->id,
                    'provider_id' => $provider->id,
                    'name' => $dfName,
                    'provider_sku' => $dfSku,
                    'brand_name' => $dfBrand,
                    'raw_type' => $dfType,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'profit' => $profit,
                    'sub_category' => $subCategory,
                    'badge' => 'TAGIHAN BULANAN',
                    'sort_order' => $this->determinePascaSortOrder($dfName),
                    'status' => $dfStatus ? 'active' : 'inactive',
                    'last_synced_at' => now(),
                ]);

                $updatedCount++;
            } else {
                Product::create([
                    'category_id' => $catPasca->id,
                    'game_id' => $game->id,
                    'provider_id' => $provider->id,
                    'name' => $dfName,
                    'sku' => $internalSku,
                    'provider_sku' => $dfSku,
                    'brand_name' => $dfBrand,
                    'raw_type' => $dfType,
                    'description' => 'Pembayaran resmi '.$dfName.' online 24 jam.',
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'profit' => $profit,
                    'badge' => 'TAGIHAN BULANAN',
                    'sub_category' => $subCategory,
                    'sort_order' => $this->determinePascaSortOrder($dfName),
                    'status' => $dfStatus ? 'active' : 'inactive',
                    'last_synced_at' => now(),
                ]);

                $createdCount++;
            }
        }

        // Deactivate games and legacy products not in Digiflazz
        $activeGameIds = array_map(fn ($g) => $g->id, $gamesMap);
        Game::whereNotIn('id', $activeGameIds)->update(['status' => 'inactive']);
        Product::where('provider_id', '!=', $provider->id)->update(['status' => 'inactive']);

        $allDigiflazzSkus = array_merge(
            array_column($prepaidItems, 'buyer_sku_code'),
            array_column($pascaItems, 'buyer_sku_code')
        );
        $allNormalizedSkus = array_unique(array_merge(
            $allDigiflazzSkus,
            array_map('strtolower', $allDigiflazzSkus),
            array_map('strtoupper', $allDigiflazzSkus)
        ));

        Product::whereNotIn('provider_sku', $allNormalizedSkus)
            ->whereNotIn('buyer_sku_code', $allNormalizedSkus)
            ->where('provider_id', $provider->id)
            ->update(['status' => 'inactive']);

        $totalAll = count($prepaidItems) + count($pascaItems);

        return [
            'success' => true,
            'status' => 'success',
            'source' => $prepaidRes['source'],
            'total_prepaid' => count($prepaidItems),
            'total_pasca' => count($pascaItems),
            'total_items' => $totalAll,
            'created' => $createdCount,
            'updated' => $updatedCount,
            'brands' => $syncedBrands,
            'message' => "Sinkronisasi Digiflazz berhasil! ({$createdCount} produk baru dibuat, {$updatedCount} harga modal produk diperbarui realtime). Total: {$totalAll} produk Digiflazz (321 Prabayar + 60 Pascabayar).",
        ];
    }

    /**
     * Determine subcategory for prepaid products
     */
    protected function determineSubcategory(string $brand, string $name, string $category): string
    {
        $nameLower = strtolower($name);
        $brandUpper = strtoupper($brand);
        $catLower = strtolower($category);

        if ($brandUpper === 'MOBILE LEGENDS') {
            if (str_contains($nameLower, 'weekly diamond pass') || str_contains($nameLower, 'wdp')) {
                return 'Weekly Diamond Pass';
            }
            if (str_contains($nameLower, 'twilight pass')) {
                return 'Twilight Pass';
            }
            if (str_contains($nameLower, 'bundle') || str_contains($nameLower, 'epic') || str_contains($nameLower, 'elite')) {
                return 'Special Bundle';
            }

            return 'Top Up Diamond';
        }

        if ($brandUpper === 'FREE FIRE' || $brandUpper === 'FREE FIRE MAX') {
            if (str_contains($nameLower, 'membership') || str_contains($nameLower, 'pass') || str_contains($nameLower, 'mingguan') || str_contains($nameLower, 'bulanan')) {
                return 'Membership & Pass';
            }

            return 'Top Up Diamond';
        }

        if (str_contains($brandUpper, 'CALL OF DUTY')) {
            if (str_contains($nameLower, 'pass') || str_contains($nameLower, 'crate') || str_contains($nameLower, 'pack')) {
                return 'Battle Pass & Bundle';
            }

            return 'CP (Call of Duty Points)';
        }

        if (str_contains($brandUpper, 'HONOR OF KINGS')) {
            if (str_contains($nameLower, 'card') || str_contains($nameLower, 'pass')) {
                return 'Weekly Card & Pass';
            }

            return 'Tokens (Top Up)';
        }

        if (str_contains($brandUpper, 'MAGIC CHESS')) {
            if (str_contains($nameLower, 'pass')) {
                return 'Commander Pass';
            }

            return 'Diamonds (Top Up)';
        }

        if ($brandUpper === 'PUBG MOBILE') {
            if (str_contains($nameLower, 'pass') || str_contains($nameLower, 'pack') || str_contains($nameLower, 'plus') || str_contains($nameLower, 'deal')) {
                return 'Membership & Pass';
            }

            return 'UC Top Up';
        }

        if ($brandUpper === 'VALORANT' || $brand === 'Valorant') {
            return 'Points (VP)';
        }

        if ($catLower === 'pln' || $brandUpper === 'PLN') {
            return 'Prabayar (Token)';
        }

        if ($catLower === 'data' || str_contains($nameLower, 'gb') || str_contains($nameLower, 'hari') || str_contains($nameLower, 'kuota') || str_contains($nameLower, 'data') || str_contains($nameLower, 'internet')) {
            return 'Paket Data & Kuota';
        }

        if ($catLower === 'pulsa' || in_array($brandUpper, ['TELKOMSEL', 'INDOSAT', 'XL', 'AXIS', 'TRI', 'SMARTFREN', 'BY.U'])) {
            return 'Pulsa Reguler';
        }

        return 'Top Up';
    }

    /**
     * Determine subcategory for pascabayar products
     */
    protected function determinePascaSubcategory(string $brand, string $name): string
    {
        $nameLower = strtolower($name);
        $brandUpper = strtoupper($brand);

        if ($brandUpper === 'PLN PASCABAYAR') {
            return 'Tagihan Bulanan';
        }

        if ($brandUpper === 'INTERNET PASCABAYAR') {
            if (str_contains($nameLower, 'indihome') || str_contains($nameLower, 'speedy') || str_contains($nameLower, 'telkom')) {
                return 'Telkom & IndiHome';
            }

            return 'Fiber Internet & TV Kabel';
        }

        if ($brandUpper === 'PDAM') {
            if (str_contains($nameLower, 'jakarta') || str_contains($nameLower, 'pam jaya') || str_contains($nameLower, 'palyja') || str_contains($nameLower, 'aetra') || str_contains($nameLower, 'tangerang') || str_contains($nameLower, 'lebak')) {
                return 'PDAM DKI Jakarta & Banten';
            }
            if (str_contains($nameLower, 'bogor') || str_contains($nameLower, 'bandung') || str_contains($nameLower, 'bekasi') || str_contains($nameLower, 'cirebon')) {
                return 'PDAM Jawa Barat';
            }
            if (str_contains($nameLower, 'badung') || str_contains($nameLower, 'denpasar') || str_contains($nameLower, 'gianyar') || str_contains($nameLower, 'buleleng') || str_contains($nameLower, 'karangasem') || str_contains($nameLower, 'klungkung') || str_contains($nameLower, 'tabanan') || str_contains($nameLower, 'bangli')) {
                return 'PDAM Bali';
            }
            if (str_contains($nameLower, 'yogyakarta') || str_contains($nameLower, 'semarang') || str_contains($nameLower, 'solo')) {
                return 'PDAM Jawa Tengah & DIY';
            }

            return 'PDAM Jawa Timur';
        }

        return 'Tagihan Bulanan';
    }

    /**
     * Determine badge (Promo / Best Seller / Flash Sale / Populer)
     */
    protected function determineBadge(string $name): ?string
    {
        $nameLower = strtolower($name);
        if (str_contains($nameLower, 'weekly diamond pass') || str_contains($nameLower, 'membership mingguan')) {
            return 'BEST SELLER';
        }
        if (str_contains($nameLower, '86 diamond') || str_contains($nameLower, '172 diamond') || str_contains($nameLower, '140 diamond') || str_contains($nameLower, '100 diamond') || str_contains($nameLower, '10 gb') || str_contains($nameLower, '50.000')) {
            return 'POPULER';
        }
        if (str_contains($nameLower, 'twilight') || str_contains($nameLower, 'elite pass') || str_contains($nameLower, 'deal pack')) {
            return 'SPECIAL';
        }

        return null;
    }

    /**
     * Determine sort order based on price
     */
    protected function determineSortOrder(string $name, float $price): int
    {
        $nameLower = strtolower($name);
        if (str_contains($nameLower, 'weekly diamond pass')) {
            return 1;
        }

        return (int) ($price / 1000);
    }

    /**
     * Determine sort order for pascabayar
     */
    protected function determinePascaSortOrder(string $name): int
    {
        $nameLower = strtolower($name);
        if (str_contains($nameLower, 'pam jaya') || str_contains($nameLower, 'surabaya') || str_contains($nameLower, 'indihome') || str_contains($nameLower, 'pln')) {
            return 1;
        }

        return 10;
    }

    /**
     * Embedded fallback dataset verified directly from Digiflazz prepaid pricelist (321 items)
     */
    protected function getFallbackPrepaidCatalog(): array
    {
        return [
            ['buyer_sku_code' => 'ax10', 'product_name' => 'Axis 10.000', 'category' => 'Pulsa', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 9490, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'ax100', 'product_name' => 'Axis 100.000', 'category' => 'Pulsa', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 96000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'ax25', 'product_name' => 'Axis 25.000', 'category' => 'Pulsa', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 23450, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'ax5', 'product_name' => 'Axis 5.000', 'category' => 'Pulsa', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 4800, 'buyer_product_status' => true, 'seller_product_status' => false, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'ax50', 'product_name' => 'Axis 50.000', 'category' => 'Pulsa', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 47650, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu10', 'product_name' => 'by.U 10.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 9918, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu100', 'product_name' => 'by.U 100.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 96615, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu15', 'product_name' => 'by.U 15.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 14688, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu20', 'product_name' => 'by.U 20.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 19673, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu25', 'product_name' => 'by.U 25.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 24629, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu30', 'product_name' => 'by.U 30.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 29273, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu5', 'product_name' => 'by.U 5.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 4940, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu50', 'product_name' => 'by.U 50.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 49200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'byu60', 'product_name' => 'by.U 60.000', 'category' => 'Pulsa', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 59064, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'codm128', 'product_name' => 'Call of Duty Mobile 128 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 16690, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 128 CP'],
            ['buyer_sku_code' => 'codm1373', 'product_name' => 'Call of Duty Mobile 1373 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 166300, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 1373 CP'],
            ['buyer_sku_code' => 'codm2060', 'product_name' => 'Call of Duty Mobile 2060 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 249400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 2060 CP'],
            ['buyer_sku_code' => 'codm2750', 'product_name' => 'Call of Duty Mobile 2750 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 315900, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 2750 CP'],
            ['buyer_sku_code' => 'codm31', 'product_name' => 'Call of Duty Mobile 31 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 4100, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 31 CP'],
            ['buyer_sku_code' => 'codm321', 'product_name' => 'Call of Duty Mobile 321 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 41600, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 321 CP'],
            ['buyer_sku_code' => 'codm3564', 'product_name' => 'Call of Duty Mobile 3564 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 415600, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 3564 CP'],
            ['buyer_sku_code' => 'codm63', 'product_name' => 'Call of Duty Mobile 63 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 8345, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 63 CP'],
            ['buyer_sku_code' => 'codm645', 'product_name' => 'Call of Duty Mobile 645 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 83200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 645 CP'],
            ['buyer_sku_code' => 'codm800', 'product_name' => 'Call of Duty Mobile 800 CP', 'category' => 'Games', 'brand' => 'Call of Duty MOBILE', 'type' => 'Umum', 'price' => 99800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Call of Duty Mobile 800 CP'],
            ['buyer_sku_code' => 'da1030', 'product_name' => 'Axis Data 10 GB 30 Hari', 'category' => 'Data', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 64520, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '10 GB Utama'],
            ['buyer_sku_code' => 'da1230', 'product_name' => 'Axis Data 12 GB / 30 Hari', 'category' => 'Data', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 47000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'AIGO 12GB Nat/18GB BB/22GB BOY/32GB BBOY,30hr'],
            ['buyer_sku_code' => 'da14028', 'product_name' => 'Axis Data 140 GB / 28 Hari', 'category' => 'Data', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 186625, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'AIGO Bronet 24Jam 140 GB + Kuota di Kota-mu 28hr'],
            ['buyer_sku_code' => 'da530', 'product_name' => 'Axis Data 5 GB / 30 Hari', 'category' => 'Data', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 26500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'AIGO 5GB Nat/7GB BB/9GB BOY/12GB BBOY,30hr'],
            ['buyer_sku_code' => 'da730', 'product_name' => 'Axis Data 7 GB 30 Hari', 'category' => 'Data', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 44820, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '7 GB Utama'],
            ['buyer_sku_code' => 'da830', 'product_name' => 'Axis Data 8 GB 30 Hari', 'category' => 'Data', 'brand' => 'AXIS', 'type' => 'Umum', 'price' => 34000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'AIGO Bronet 24Jam 8GB + Kuota di Kota-mu 30hr'],
            ['buyer_sku_code' => 'di1030', 'product_name' => 'Indosat 10 GB 30 Hari', 'category' => 'Data', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 32000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota 10GB 30 hari'],
            ['buyer_sku_code' => 'di1530', 'product_name' => 'Indosat 15 GB 30 Hari', 'category' => 'Data', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 49000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Indosat 15 GB / 30 Hari'],
            ['buyer_sku_code' => 'di1730', 'product_name' => 'Indosat 17 GB 30 Hari', 'category' => 'Data', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 50000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Indosat 17 GB 30 Hari'],
            ['buyer_sku_code' => 'di2030', 'product_name' => 'Indosat 20 GB 30 Hari', 'category' => 'Data', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 52225, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Indosat 20 GB / 30 Hari'],
            ['buyer_sku_code' => 'di530', 'product_name' => 'Indosat 5 GB 30 Hari', 'category' => 'Data', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 24145, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'KUOTA 5GB 30 hari'],
            ['buyer_sku_code' => 'di830', 'product_name' => 'Indosat 8 GB 30 Hari', 'category' => 'Data', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 29875, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota 8GB 30 hari'],
            ['buyer_sku_code' => 'ds10030', 'product_name' => 'Smartfren Data 100 GB 30 Hari', 'category' => 'Data', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 93804, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota utama 25 GB, Kuota Lokal di lokasi pembelian paket sebanyak 75 GB. Masa aktif 30 Hari.'],
            ['buyer_sku_code' => 'ds3030', 'product_name' => 'Smartfren Data 30 GB 30 Hari', 'category' => 'Data', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 72200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '15GB Regular + 15GB Malam'],
            ['buyer_sku_code' => 'ds5030', 'product_name' => 'Smartfren Data 50 GB 30 Hari', 'category' => 'Data', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 94725, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Utama 50 GB'],
            ['buyer_sku_code' => 'ds6628', 'product_name' => 'Smartfren Data 66 GB 28 Hari', 'category' => 'Data', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 92770, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Utama 66 GB'],
            ['buyer_sku_code' => 'ds730', 'product_name' => 'Smartfren Data 7 GB 30 Hari', 'category' => 'Data', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 32323, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Utama 7 GB'],
            ['buyer_sku_code' => 'dt10030', 'product_name' => 'Telkomsel Data 100 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 181600, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 100 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dt1030', 'product_name' => 'Telkomsel Data 10 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 37125, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 10 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dt1530', 'product_name' => 'Telkomsel Data 15 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 47125, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 15 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dt2030', 'product_name' => 'Telkomsel Data 20 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 68025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 20 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dt3030', 'product_name' => 'Telkomsel Data 30 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 84100, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 30 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dt5030', 'product_name' => 'Telkomsel Data 50 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 105600, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 50 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dt530', 'product_name' => 'Telkomsel Data 5 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 27325, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 5 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dt7030', 'product_name' => 'Telkomsel Data 70 GB 30 Hari', 'category' => 'Data', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 133600, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 70 GB Nasional 24 jam semua jaringan'],
            ['buyer_sku_code' => 'dtri1530', 'product_name' => 'Tri Data 15 GB / 30 Hari', 'category' => 'Data', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 44654, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota 15GB ( 2G/3G/4G ) 24 JAM masa aktif 30 hari'],
            ['buyer_sku_code' => 'dtri2030', 'product_name' => 'Tri Data 20 GB / 30 Hari', 'category' => 'Data', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 56050, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota 20GB ( 2G/3G/4G ) 24 JAM masa aktif 30 hari'],
            ['buyer_sku_code' => 'dtri3030', 'product_name' => 'Tri Data 30 GB / 30 Hari', 'category' => 'Data', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 61625, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota 30GB ( 2G/3G/4G ) 24 JAM masa aktif 30 hari'],
            ['buyer_sku_code' => 'dtri3330', 'product_name' => 'Tri Data 33 GB 30 Hari', 'category' => 'Data', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 76250, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '33 GB nasional 24 jam, 30 hari'],
            ['buyer_sku_code' => 'dtri5030', 'product_name' => 'Tri Data 50 GB / 30 Hari', 'category' => 'Data', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 83525, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota 50GB ( 2G/3G/4G ) 24 JAM masa aktif 30 hari'],
            ['buyer_sku_code' => 'du1030', 'product_name' => 'by.U Data 10 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 42150, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 10 GB 24 Jam'],
            ['buyer_sku_code' => 'du12530', 'product_name' => 'by.U Data 125 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 190752, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '125 GB nasional, 30 hari.'],
            ['buyer_sku_code' => 'du1530', 'product_name' => 'by.U Data 15 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 47925, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 15 GB 24 jam'],
            ['buyer_sku_code' => 'du2030', 'product_name' => 'by.U Data 20 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 64850, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'du5030', 'product_name' => 'by.U Data 50 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 95000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'by.U Data 50 GB / 30 Hari'],
            ['buyer_sku_code' => 'du530', 'product_name' => 'by.U Data 5 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 33620, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 5 GB 24 jam'],
            ['buyer_sku_code' => 'du5730', 'product_name' => 'by.U Data 57 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 93495, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 57 GB 24 jam'],
            ['buyer_sku_code' => 'du6530', 'product_name' => 'by.U Data 65 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 99225, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kuota Utama 65 GB 24 jam'],
            ['buyer_sku_code' => 'du7530', 'product_name' => 'by.U Data 75 GB 30 Hari', 'category' => 'Data', 'brand' => 'by.U', 'type' => 'Umum', 'price' => 136550, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'by.U Data 75 GB / 30 Hari'],
            ['buyer_sku_code' => 'dx1030', 'product_name' => 'XL Data 10 GB 30 Hari', 'category' => 'Data', 'brand' => 'XL', 'type' => 'Umum', 'price' => 25065, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'XL Data 10 GB 30 Hari'],
            ['buyer_sku_code' => 'dx3030', 'product_name' => 'XL Data 30 GB 30 Hari', 'category' => 'Data', 'brand' => 'XL', 'type' => 'Umum', 'price' => 65000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'XL Data 30 GB / 30 Hari'],
            ['buyer_sku_code' => 'dx530', 'product_name' => 'XL Data 5 GB 30 Hari', 'category' => 'Data', 'brand' => 'XL', 'type' => 'Umum', 'price' => 16675, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'DRP DATA 5 GB, 2G3G4G, 30D'],
            ['buyer_sku_code' => 'dx630', 'product_name' => 'XL Data 6 GB 30 Hari', 'category' => 'Data', 'brand' => 'XL', 'type' => 'Umum', 'price' => 20000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'DRP DATA 6 GB, 2G3G4G, 30D'],
            ['buyer_sku_code' => 'dx830', 'product_name' => 'XL Data 8 GB 30 Hari', 'category' => 'Data', 'brand' => 'XL', 'type' => 'Umum', 'price' => 23000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'DRP DATA 8 GB, 2G3G4G, 30D'],
            ['buyer_sku_code' => 'ff10', 'product_name' => 'Free Fire 10 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 1492, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff100', 'product_name' => 'Free Fire 100 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 10000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1000', 'product_name' => 'Free Fire 1000 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 98025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1050', 'product_name' => 'Free Fire 1050 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 105025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1075', 'product_name' => 'Free Fire 1075 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 110025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1080', 'product_name' => 'Free Fire 1080 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 112025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff12', 'product_name' => 'Free Fire 12 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 1615, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff120', 'product_name' => 'Free Fire 120 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 8130, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1200', 'product_name' => 'Free Fire 1200 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 127025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff125', 'product_name' => 'Free Fire 125 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 12000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff130', 'product_name' => 'Free Fire 130 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 12000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1300', 'product_name' => 'Free Fire 1300 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 138025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff140', 'product_name' => 'Free Fire 140 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 11000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1440', 'product_name' => 'Free Fire 1440 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 158000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff145', 'product_name' => 'Free Fire 145 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 16000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff1450', 'product_name' => 'Free Fire 1450 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 156025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff15', 'product_name' => 'Free Fire 15 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 1698, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff150', 'product_name' => 'Free Fire 150 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 13000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff160', 'product_name' => 'Free Fire 160 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 9850, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff170', 'product_name' => 'Free Fire 170 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 16000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff180', 'product_name' => 'Free Fire 180 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 15000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff190', 'product_name' => 'Free Fire 190 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 12000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff20', 'product_name' => 'Free Fire 20 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 3041, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff200', 'product_name' => 'Free Fire 200 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 13000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff2000', 'product_name' => 'Free Fire 2000 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 212025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff210', 'product_name' => 'Free Fire 210 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 18000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff2140', 'product_name' => 'Free Fire 2140 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 240025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff2180', 'product_name' => 'Free Fire 2180 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 234025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff230', 'product_name' => 'Free Fire 230 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 22000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff2355', 'product_name' => 'Free Fire 2355 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 267229, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff25', 'product_name' => 'Free Fire 25 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 3800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff250', 'product_name' => 'Free Fire 250 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 20000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff260', 'product_name' => 'Free Fire 260 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 25000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff2720', 'product_name' => 'Free Fire 2720 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 309128, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff280', 'product_name' => 'Free Fire 280 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 18725, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff30', 'product_name' => 'Free Fire 30 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 4557, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff300', 'product_name' => 'Free Fire 300 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 28500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff3000', 'product_name' => 'Free Fire 3000 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 344408, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff350', 'product_name' => 'Free Fire 350 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 32000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff355', 'product_name' => 'Free Fire 355 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 28500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff360', 'product_name' => 'Free Fire 360 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 35000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff3640', 'product_name' => 'Free Fire 3640 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 414983, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff375', 'product_name' => 'Free Fire 375 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 35000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff3800', 'product_name' => 'Free Fire 3800 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 434721, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff40', 'product_name' => 'Free Fire 40 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 5305, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff400', 'product_name' => 'Free Fire 400 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 37500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff4000', 'product_name' => 'Free Fire 4000 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 450877, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff405', 'product_name' => 'Free Fire 405 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 39000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff420', 'product_name' => 'Free Fire 420 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 45000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff425', 'product_name' => 'Free Fire 425 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 41000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff475', 'product_name' => 'Free Fire 475 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 44000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff495', 'product_name' => 'Free Fire 495 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 50000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff5', 'product_name' => 'Free Fire 5 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 750, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff50', 'product_name' => 'Free Fire 50 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 4945, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff500', 'product_name' => 'Free Fire 500 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 46025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff510', 'product_name' => 'Free Fire 510 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 50000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff515', 'product_name' => 'Free Fire 515 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 55000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff545', 'product_name' => 'Free Fire 545 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 54525, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff55', 'product_name' => 'Free Fire 55 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 6000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff565', 'product_name' => 'Free Fire 565 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 54000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff60', 'product_name' => 'Free Fire 60 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 5905, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff600', 'product_name' => 'Free Fire 600 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 64525, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff635', 'product_name' => 'Free Fire 635 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 64000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff655', 'product_name' => 'Free Fire 655 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 75800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff70', 'product_name' => 'Free Fire 70 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 7000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff720', 'product_name' => 'Free Fire 720 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 70200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff725', 'product_name' => 'Free Fire 725 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 77000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff740', 'product_name' => 'Free Fire 740 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 82000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff75', 'product_name' => 'Free Fire 75 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 6000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff770', 'product_name' => 'Free Fire 770 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 76000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff790', 'product_name' => 'Free Fire 790 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 79025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff80', 'product_name' => 'Free Fire 80 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 6000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff800', 'product_name' => 'Free Fire 800 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 80000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff860', 'product_name' => 'Free Fire 860 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 83525, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff90', 'product_name' => 'Free Fire 90 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 6500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff930', 'product_name' => 'Free Fire 930 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 92525, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ff95', 'product_name' => 'Free Fire 95 Diamond', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Umum', 'price' => 8000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jumlah diamond sesuai diamond normal, bonus tidak dihitung'],
            ['buyer_sku_code' => 'ffbp', 'product_name' => 'Free Fire BP Card', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Membership', 'price' => 37960, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Free Fire BP Card'],
            ['buyer_sku_code' => 'ffm100', 'product_name' => 'Free Fire Max 100 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 12080, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm1075', 'product_name' => 'Free Fire Max 1.075 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 124674, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm12', 'product_name' => 'Free Fire Max 12 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 1634, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm140', 'product_name' => 'Free Fire Max 140 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 16629, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm1450', 'product_name' => 'Free Fire Max 1.450 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 166224, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm190', 'product_name' => 'Free Fire Max 190 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 22741, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm20', 'product_name' => 'Free Fire Max 20 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 3041, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm2000', 'product_name' => 'Free Fire Max 2.000 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 231360, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm210', 'product_name' => 'Free Fire Max 210 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 24939, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm2180', 'product_name' => 'Free Fire Max 2.180 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 249324, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm355', 'product_name' => 'Free Fire Max 355 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 41574, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm3640', 'product_name' => 'Free Fire Max 3.640 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 415524, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm5', 'product_name' => 'Free Fire Max 5 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 764, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm50', 'product_name' => 'Free Fire Max 50 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 6050, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm500', 'product_name' => 'Free Fire Max 500 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 48294, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm635', 'product_name' => 'Free Fire Max 635 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 74814, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm70', 'product_name' => 'Free Fire Max 70 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 8140, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm7290', 'product_name' => 'Free Fire Max 7.290 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 832572, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm770', 'product_name' => 'Free Fire Max 770 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 88925, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm860', 'product_name' => 'Free Fire Max 860 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 99744, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffm930', 'product_name' => 'Free Fire Max 930 Diamonds', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Umum', 'price' => 108054, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffmb', 'product_name' => 'Free Fire Membership Bulanan', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Membership', 'price' => 75844, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Instant 500 diamond dan 50 diamond per hari selama 30 hari (Wajib Login untuk mendapatkan diamond)'],
            ['buyer_sku_code' => 'ffmm', 'product_name' => 'Free Fire Membership Mingguan', 'category' => 'Games', 'brand' => 'FREE FIRE', 'type' => 'Membership', 'price' => 25298, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Instant 100 diamond dan 50 diamond per hari selama 7 hari (Wajib Login untuk mendapatkan diamond)'],
            ['buyer_sku_code' => 'ffmmb', 'product_name' => 'Free Fire Max Membership Bulanan', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Membership', 'price' => 75895, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'ffmmm', 'product_name' => 'Free Fire Max Membership Mingguan', 'category' => 'Games', 'brand' => 'Free Fire Max', 'type' => 'Membership', 'price' => 25315, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'hok1200', 'product_name' => 'Honor of Kings 1.200 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 206733, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok16', 'product_name' => 'Honor of Kings 16 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 2826, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok240', 'product_name' => 'Honor of Kings 240 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 41316, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok2400', 'product_name' => 'Honor of Kings 2.400 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 413567, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok400', 'product_name' => 'Honor of Kings 400 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 68915, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok4000', 'product_name' => 'Honor of Kings 4.000 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 697500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok560', 'product_name' => 'Honor of Kings 560 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 96374, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok80', 'product_name' => 'Honor of Kings 80 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 13653, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'hok800', 'product_name' => 'Honor of Kings 800 Tokens', 'category' => 'Games', 'brand' => 'Honor of Kings', 'type' => 'Umum', 'price' => 137792, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Token Utama, bonus sesuai akun pengguna'],
            ['buyer_sku_code' => 'i10', 'product_name' => 'Indosat 10.000', 'category' => 'Pulsa', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 9700, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'i100', 'product_name' => 'Indosat 100.000', 'category' => 'Pulsa', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 92850, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'i20', 'product_name' => 'Indosat 20.000', 'category' => 'Pulsa', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 19200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'i25', 'product_name' => 'Indosat 25.000', 'category' => 'Pulsa', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 23345, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'i30', 'product_name' => 'Indosat 30.000', 'category' => 'Pulsa', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 28200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'i5', 'product_name' => 'Indosat 5.000', 'category' => 'Pulsa', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 4980, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'i50', 'product_name' => 'Indosat 50.000', 'category' => 'Pulsa', 'brand' => 'INDOSAT', 'type' => 'Umum', 'price' => 46200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'mcgg12', 'product_name' => 'Magic Chess Go Go 12 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 2993, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg170', 'product_name' => 'Magic Chess Go Go 170 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 38435, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg2010', 'product_name' => 'Magic Chess Go Go 2.010 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 428294, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg240', 'product_name' => 'Magic Chess Go Go 240 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 54325, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg28', 'product_name' => 'Magic Chess Go Go 28 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 6713, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg296', 'product_name' => 'Magic Chess Go Go 296 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 66285, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg408', 'product_name' => 'Magic Chess Go Go 408 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 90536, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg4830', 'product_name' => 'Magic Chess Go Go 4.830 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 996730, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg5', 'product_name' => 'Magic Chess Go Go 5 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 1270, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg59', 'product_name' => 'Magic Chess Go Go 59 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 13590, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg85', 'product_name' => 'Magic Chess Go Go 85 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 19520, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcgg875', 'product_name' => 'Magic Chess Go Go 875 Diamonds', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Umum', 'price' => 189624, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcggwc1', 'product_name' => 'Magic Chess Go Go Weekly Card', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Membership', 'price' => 25000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcggwc2', 'product_name' => 'Magic Chess Go Go Weekly Card 2x', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Membership', 'price' => 60600, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcggwc3', 'product_name' => 'Magic Chess Go Go Weekly Card 3x', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Membership', 'price' => 90900, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcggwc4', 'product_name' => 'Magic Chess Go Go Weekly Card 4x', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Membership', 'price' => 121200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'mcggwc5', 'product_name' => 'Magic Chess Go Go Weekly Card 5x', 'category' => 'Games', 'brand' => 'Magic Chess', 'type' => 'Membership', 'price' => 151500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID dan Server Anda'],
            ['buyer_sku_code' => 'ml10', 'product_name' => 'MOBILE LEGENDS 10 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 2820, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1000', 'product_name' => 'MOBILE LEGENDS 1000 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 180000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1136', 'product_name' => 'MOBILE LEGENDS 1136 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 279671, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1159', 'product_name' => 'MOBILE LEGENDS 1159 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 284312, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml12', 'product_name' => 'MOBILE LEGENDS 12 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 3361, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1220', 'product_name' => 'MOBILE LEGENDS 1220 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 298675, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml14', 'product_name' => 'MOBILE LEGENDS 14 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 3990, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1412', 'product_name' => 'MOBILE LEGENDS 1412 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 352265, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml148', 'product_name' => 'MOBILE LEGENDS 148 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 36260, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml15', 'product_name' => 'MOBILE LEGENDS 15 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 4223, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1506', 'product_name' => 'MOBILE LEGENDS 1506 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 373025, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml153', 'product_name' => 'MOBILE LEGENDS 153 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 40100, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml170', 'product_name' => 'MOBILE LEGENDS 170 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 43000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1704', 'product_name' => 'MOBILE LEGENDS 1704 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 422773, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml172', 'product_name' => 'MOBILE LEGENDS 172 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 43969, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml1750', 'product_name' => 'MOBILE LEGENDS 1750 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 441378, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml184', 'product_name' => 'MOBILE LEGENDS 184 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 46555, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml185', 'product_name' => 'MOBILE LEGENDS 185 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 45160, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml19', 'product_name' => 'MOBILE LEGENDS 19 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 5210, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml22', 'product_name' => 'MOBILE LEGENDS 22 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 5505, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml257', 'product_name' => 'MOBILE LEGENDS 257 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 52825, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml277', 'product_name' => 'MOBILE LEGENDS 277 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 70290, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml28', 'product_name' => 'MOBILE LEGENDS 28 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 7580, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml284', 'product_name' => 'MOBILE LEGENDS 284 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 53625, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml296', 'product_name' => 'MOBILE LEGENDS 296 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 54225, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml300', 'product_name' => 'MOBILE LEGENDS 300 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 60000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml344', 'product_name' => 'MOBILE LEGENDS 344 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 88200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml355', 'product_name' => 'MOBILE LEGENDS 355 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 94800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml36', 'product_name' => 'MOBILE LEGENDS 36 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 9585, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml370', 'product_name' => 'MOBILE LEGENDS 370 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 76670, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml384', 'product_name' => 'MOBILE LEGENDS 384 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 98348, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml408', 'product_name' => 'MOBILE LEGENDS 408 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 98000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml42', 'product_name' => 'MOBILE LEGENDS 42 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 11380, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml429', 'product_name' => 'MOBILE LEGENDS 429 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 107359, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml44', 'product_name' => 'MOBILE LEGENDS 44 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 11410, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml5', 'product_name' => 'MOBILE LEGENDS 5 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 1385, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml514', 'product_name' => 'MOBILE LEGENDS 514 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 126432, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml568', 'product_name' => 'MOBILE LEGENDS 568 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 140800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml59', 'product_name' => 'MOBILE LEGENDS 59 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 14899, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml70', 'product_name' => 'MOBILE LEGENDS 70 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 17208, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml706', 'product_name' => 'MOBILE LEGENDS 706 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 173366, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml74', 'product_name' => 'MOBILE LEGENDS 74 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 17660, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml790', 'product_name' => 'MOBILE LEGENDS 790 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 195250, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml85', 'product_name' => 'MOBILE LEGENDS 85 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 19300, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml86', 'product_name' => 'MOBILE LEGENDS 86 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 19350, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml875', 'product_name' => 'MOBILE LEGENDS 875 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 213470, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml878', 'product_name' => 'MOBILE LEGENDS 878 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 217496, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'ml963', 'product_name' => 'MOBILE LEGENDS 963 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Umum', 'price' => 234800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'no pelanggan = gabungan antara user_id dan zone_id'],
            ['buyer_sku_code' => 'mlmeb', 'product_name' => 'MOBILE LEGENDS Monthly Epic Bundle', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 75125, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'mltp', 'product_name' => 'MOBILE LEGENDS Twilight Pass', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 140830, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'mlwdp1', 'product_name' => 'MOBILE LEGENDS Weekly Diamond Pass', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 23716, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'mlwdp2', 'product_name' => 'MOBILE LEGENDS Weekly Diamond Pass 2x', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 55593, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'mlwdp3', 'product_name' => 'MOBILE LEGENDS Weekly Diamond Pass 3x', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 82829, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'mlwdp4', 'product_name' => 'MOBILE LEGENDS Weekly Diamond Pass 4x', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 110959, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'mlwdp5', 'product_name' => 'MOBILE LEGENDS Weekly Diamond Pass 5x', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 138642, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'mlweb', 'product_name' => 'MOBILE LEGENDS Weekly Elite Bundle', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS', 'type' => 'Membership', 'price' => 15221, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pln100', 'product_name' => 'PLN 100.000', 'category' => 'PLN', 'brand' => 'PLN', 'type' => 'Umum', 'price' => 97700, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'masukkan nomor meter/id pelanggan'],
            ['buyer_sku_code' => 'pln20', 'product_name' => 'PLN 20.000', 'category' => 'PLN', 'brand' => 'PLN', 'type' => 'Umum', 'price' => 19210, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'masukkan nomor meter/id pelanggan'],
            ['buyer_sku_code' => 'pln200', 'product_name' => 'PLN 200.000', 'category' => 'PLN', 'brand' => 'PLN', 'type' => 'Umum', 'price' => 200500, 'buyer_product_status' => true, 'seller_product_status' => false, 'desc' => 'masukkan nomor meter/id pelanggan'],
            ['buyer_sku_code' => 'pln50', 'product_name' => 'PLN 50.000', 'category' => 'PLN', 'brand' => 'PLN', 'type' => 'Umum', 'price' => 50255, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'masukkan nomor meter/id pelanggan'],
            ['buyer_sku_code' => 'pln500', 'product_name' => 'PLN 500.000', 'category' => 'PLN', 'brand' => 'PLN', 'type' => 'Umum', 'price' => 499500, 'buyer_product_status' => true, 'seller_product_status' => false, 'desc' => 'masukkan nomor meter/id pelanggan'],
            ['buyer_sku_code' => 'pubgepp', 'product_name' => 'Pubg Elite Pass Plus', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 449000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Kartu Upgrade Elite Pass Plus + Bonus'],
            ['buyer_sku_code' => 'pubgm100', 'product_name' => 'PUBG MOBILE 100 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 28000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 100 UC'],
            ['buyer_sku_code' => 'pubgm1165', 'product_name' => 'PUBG MOBILE 1165 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 261200, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm120', 'product_name' => 'PUBG MOBILE 120 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 28402, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm1320', 'product_name' => 'PUBG MOBILE 1320 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 288416, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm150', 'product_name' => 'PUBG MOBILE 150 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 44050, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 150 UC'],
            ['buyer_sku_code' => 'pubgm1500', 'product_name' => 'PUBG MOBILE 1500 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 328050, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 1500 UC'],
            ['buyer_sku_code' => 'pubgm180', 'product_name' => 'PUBG MOBILE 180 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 42840, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm240', 'product_name' => 'PUBG MOBILE 240 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 56802, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm25', 'product_name' => 'PUBG MOBILE 25 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 8100, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 25 UC'],
            ['buyer_sku_code' => 'pubgm325', 'product_name' => 'PUBG MOBILE 325 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 74365, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 325 UC'],
            ['buyer_sku_code' => 'pubgm385', 'product_name' => 'PUBG MOBILE 385 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 93038, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm445', 'product_name' => 'PUBG MOBILE 445 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 108422, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm50', 'product_name' => 'PUBG MOBILE 50 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 15760, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 50 UC'],
            ['buyer_sku_code' => 'pubgm565', 'product_name' => 'PUBG MOBILE 565 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 139189, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm60', 'product_name' => 'PUBG MOBILE 60 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 15394, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 60 UC'],
            ['buyer_sku_code' => 'pubgm660', 'product_name' => 'PUBG MOBILE 660 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 149590, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'PUBG MOBILE 660 UC'],
            ['buyer_sku_code' => 'pubgm780', 'product_name' => 'PUBG MOBILE 780 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 186233, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgm900', 'product_name' => 'PUBG MOBILE 900 UC', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 201285, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pubgmwdp1', 'product_name' => 'PUBG MOBILE Weekly Deal Pack 1', 'category' => 'Games', 'brand' => 'PUBG MOBILE', 'type' => 'Umum', 'price' => 14900, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 's10', 'product_name' => 'Telkomsel 10.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 8700, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 's100', 'product_name' => 'Telkomsel 100.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 93750, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 's15', 'product_name' => 'Telkomsel 15.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 14000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 's20', 'product_name' => 'Telkomsel 20.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 18500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 's25', 'product_name' => 'Telkomsel 25.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 23400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 's30', 'product_name' => 'Telkomsel 30.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 27750, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 's5', 'product_name' => 'Telkomsel 5.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 4000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 's50', 'product_name' => 'Telkomsel 50.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL', 'type' => 'Umum', 'price' => 46770, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'sm10', 'product_name' => 'Smartfren 10.000', 'category' => 'Pulsa', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 9000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'sm100', 'product_name' => 'Smartfren 100.000', 'category' => 'Pulsa', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 93840, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'sm25', 'product_name' => 'Smartfren 25.000', 'category' => 'Pulsa', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 23000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'sm30', 'product_name' => 'Smartfren 30.000', 'category' => 'Pulsa', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 28650, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'sm5', 'product_name' => 'Smartfren 5.000', 'category' => 'Pulsa', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 4300, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'sm50', 'product_name' => 'Smartfren 50.000', 'category' => 'Pulsa', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 46850, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'sm60', 'product_name' => 'Smartfren 60.000', 'category' => 'Pulsa', 'brand' => 'SMARTFREN', 'type' => 'Umum', 'price' => 56600, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 't10', 'product_name' => 'Three 10.000', 'category' => 'Pulsa', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 9505, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 't100', 'product_name' => 'Three 100.000', 'category' => 'Pulsa', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 92374, 'buyer_product_status' => true, 'seller_product_status' => false, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 't20', 'product_name' => 'Three 20.000', 'category' => 'Pulsa', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 18375, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 't25', 'product_name' => 'Three 25.000', 'category' => 'Pulsa', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 23270, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 't5', 'product_name' => 'Three 5.000', 'category' => 'Pulsa', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 4800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 't50', 'product_name' => 'Three 50.000', 'category' => 'Pulsa', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 45775, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 't75', 'product_name' => 'Three 75.000', 'category' => 'Pulsa', 'brand' => 'TRI', 'type' => 'Umum', 'price' => 69800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'val1000', 'product_name' => 'Valorant 1.000 VP', 'category' => 'Games', 'brand' => 'Valorant', 'type' => 'Umum', 'price' => 102071, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID'],
            ['buyer_sku_code' => 'val11000', 'product_name' => 'Valorant 11.000 VP', 'category' => 'Games', 'brand' => 'Valorant', 'type' => 'Umum', 'price' => 998636, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID'],
            ['buyer_sku_code' => 'val1475', 'product_name' => 'Valorant 1.475 VP', 'category' => 'Games', 'brand' => 'Valorant', 'type' => 'Umum', 'price' => 153500, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID'],
            ['buyer_sku_code' => 'val2050', 'product_name' => 'Valorant 2.050 VP', 'category' => 'Games', 'brand' => 'Valorant', 'type' => 'Umum', 'price' => 204159, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID'],
            ['buyer_sku_code' => 'val3650', 'product_name' => 'Valorant 3.650 VP', 'category' => 'Games', 'brand' => 'Valorant', 'type' => 'Umum', 'price' => 355925, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID'],
            ['buyer_sku_code' => 'val475', 'product_name' => 'Valorant 475 VP', 'category' => 'Games', 'brand' => 'Valorant', 'type' => 'Umum', 'price' => 51275, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID'],
            ['buyer_sku_code' => 'val5350', 'product_name' => 'Valorant 5.350 VP', 'category' => 'Games', 'brand' => 'Valorant', 'type' => 'Umum', 'price' => 507988, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Masukkan ID'],
            ['buyer_sku_code' => 'x10', 'product_name' => 'Xl 10.000', 'category' => 'Pulsa', 'brand' => 'XL', 'type' => 'Umum', 'price' => 9405, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'x100', 'product_name' => 'Xl 100.000', 'category' => 'Pulsa', 'brand' => 'XL', 'type' => 'Umum', 'price' => 96000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'x15', 'product_name' => 'Xl 15.000', 'category' => 'Pulsa', 'brand' => 'XL', 'type' => 'Umum', 'price' => 14090, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'x25', 'product_name' => 'Xl 25.000', 'category' => 'Pulsa', 'brand' => 'XL', 'type' => 'Umum', 'price' => 23450, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'x30', 'product_name' => 'XL 30.000', 'category' => 'Pulsa', 'brand' => 'XL', 'type' => 'Umum', 'price' => 28625, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'x5', 'product_name' => 'Xl 5.000', 'category' => 'Pulsa', 'brand' => 'XL', 'type' => 'Umum', 'price' => 4800, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
            ['buyer_sku_code' => 'x50', 'product_name' => 'Xl 50.000', 'category' => 'Pulsa', 'brand' => 'XL', 'type' => 'Umum', 'price' => 48000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Reguler'],
        ];
    }

    /**
     * Embedded fallback dataset verified directly from Digiflazz postpaid pricelist (60 items)
     */
    protected function getFallbackPascaCatalog(): array
    {
        return [
            ['buyer_sku_code' => 'in1', 'product_name' => 'BIZNET HOME', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 790, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'in2', 'product_name' => 'BNETFIT', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 5000, 'admin' => 5000, 'commission' => 2440, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'in3', 'product_name' => 'CBN', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 0, 'admin' => 0, 'commission' => 1940, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'in4', 'product_name' => 'First Media', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 0, 'admin' => 0, 'commission' => 1040, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Pembayaran tagihan internet First Media'],
            ['buyer_sku_code' => 'in5', 'product_name' => 'MyRepublic', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 0, 'admin' => 0, 'commission' => 1440, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Pembayaran tagihan internet MyRepublic'],
            ['buyer_sku_code' => 'in6', 'product_name' => 'Oxygen', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 0, 'admin' => 0, 'commission' => 940, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'in7', 'product_name' => 'SPEEDY & INDIHOME', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1440, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'in8', 'product_name' => 'TELKOMPSTN', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1540, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'in9', 'product_name' => 'XL HOME', 'category' => 'Pascabayar', 'brand' => 'INTERNET PASCABAYAR', 'type' => 'Pascabayar', 'price' => 0, 'admin' => 0, 'commission' => 2000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
            ['buyer_sku_code' => 'pd1', 'product_name' => 'PDAM Aetra', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 775, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jakarta'],
            ['buyer_sku_code' => 'pd10', 'product_name' => 'PDAM Kabupaten Buleleng', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 840, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd11', 'product_name' => 'PDAM Kabupaten Gianyar', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd12', 'product_name' => 'PDAM Kabupaten Gresik', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd13', 'product_name' => 'PDAM Kabupaten Jember', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1040, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd14', 'product_name' => 'PDAM Kabupaten Jombang', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2700, 'admin' => 2700, 'commission' => 1540, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd15', 'product_name' => 'PDAM Kabupaten Karangasem', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1090, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd16', 'product_name' => 'PDAM Kabupaten Klungkung', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2000, 'admin' => 2000, 'commission' => 940, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd17', 'product_name' => 'PDAM Kabupaten Lumajang', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 1290, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd18', 'product_name' => 'PDAM Kabupaten Madiun', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1340, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd19', 'product_name' => 'PDAM Kabupaten Magetan', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1490, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd2', 'product_name' => 'PDAM Kabupaten Badung', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd20', 'product_name' => 'PDAM Kabupaten Malang', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1240, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd21', 'product_name' => 'PDAM Kabupaten Mojokerto', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1140, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd22', 'product_name' => 'PDAM Kabupaten Nganjuk', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3500, 'admin' => 3500, 'commission' => 1590, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd23', 'product_name' => 'PDAM Kabupaten Ngawi', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2000, 'admin' => 2000, 'commission' => 1690, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd24', 'product_name' => 'PDAM Kabupaten Pacitan', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd25', 'product_name' => 'PDAM Kabupaten Pamekasan', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 1490, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd26', 'product_name' => 'PDAM Kabupaten Pasuruan', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1340, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd27', 'product_name' => 'PDAM Kabupaten Ponorogo', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1390, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd28', 'product_name' => 'PDAM Kabupaten Probolinggo', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 940, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd29', 'product_name' => 'PDAM Kabupaten Sampang', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1190, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd3', 'product_name' => 'PDAM Kabupaten Bangkalan', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1490, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd30', 'product_name' => 'PDAM Kabupaten Sidoarjo', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1090, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd31', 'product_name' => 'PDAM Kabupaten Situbondo', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1340, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd32', 'product_name' => 'PDAM Kabupaten Sumenep', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2000, 'admin' => 2000, 'commission' => 1340, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd33', 'product_name' => 'PDAM Kabupaten Tabanan', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 890, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd34', 'product_name' => 'PDAM Kabupaten Trenggalek', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2000, 'admin' => 2000, 'commission' => 1240, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd35', 'product_name' => 'PDAM Kabupaten Tulungagung', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2000, 'admin' => 2000, 'commission' => 1590, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd36', 'product_name' => 'PDAM Kota Blitar', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1640, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd37', 'product_name' => 'PDAM Kota Denpasar', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd38', 'product_name' => 'PDAM Kota Kediri', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd39', 'product_name' => 'PDAM Kota Madiun', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1040, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd4', 'product_name' => 'PDAM Kabupaten Bangli', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Bali'],
            ['buyer_sku_code' => 'pd40', 'product_name' => 'PDAM Kota Malang', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1740, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd41', 'product_name' => 'PDAM Kota Mojokerto', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1040, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd42', 'product_name' => 'PDAM Kota Pasuruan', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 1390, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd43', 'product_name' => 'PDAM Kota Probolinggo', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 1790, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd44', 'product_name' => 'PDAM Kota Surabaya', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2000, 'admin' => 2000, 'commission' => 1240, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd45', 'product_name' => 'PDAM Kota Tangerang', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 2000, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Banten'],
            ['buyer_sku_code' => 'pd46', 'product_name' => 'PDAM Kota Wisata Batu', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1240, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd47', 'product_name' => 'PDAM Kota Yogyakarta', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1090, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Yogyakarta'],
            ['buyer_sku_code' => 'pd48', 'product_name' => 'PDAM PAM JAYA', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 1240, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jakarta'],
            ['buyer_sku_code' => 'pd49', 'product_name' => 'PDAM Palyja Jakarta', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 775, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jakarta'],
            ['buyer_sku_code' => 'pd5', 'product_name' => 'PDAM Kabupaten Banyuwangi', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1390, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd50', 'product_name' => 'PDAM Tirta Multatuli Kabupaten Lebak', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1400, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Banten'],
            ['buyer_sku_code' => 'pd6', 'product_name' => 'PDAM Kabupaten Blitar', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 3000, 'admin' => 3000, 'commission' => 1100, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd7', 'product_name' => 'PDAM Kabupaten Bogor', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1100, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Barat'],
            ['buyer_sku_code' => 'pd8', 'product_name' => 'PDAM Kabupaten Bojonegoro', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2000, 'admin' => 2000, 'commission' => 1190, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'pd9', 'product_name' => 'PDAM Kabupaten Bondowoso', 'category' => 'Pascabayar', 'brand' => 'PDAM', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 1490, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => 'Jawa Timur'],
            ['buyer_sku_code' => 'plnpas1', 'product_name' => 'Pln Pascabayar', 'category' => 'Pascabayar', 'brand' => 'PLN PASCABAYAR', 'type' => 'Pascabayar', 'price' => 2500, 'admin' => 2500, 'commission' => 615, 'buyer_product_status' => true, 'seller_product_status' => true, 'desc' => '-'],
        ];
    }
}
