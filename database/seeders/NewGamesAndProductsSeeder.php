<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\Provider;
use Illuminate\Database\Seeder;

class NewGamesAndProductsSeeder extends Seeder
{
    public function run(): void
    {
        $catGame = Category::where('slug', 'top-up-game')->first() ?? Category::firstOrCreate([
            'slug' => 'top-up-game',
        ], [
            'name' => 'Top Up Game',
            'type' => 'game',
            'icon' => 'sports_esports',
            'status' => 'active',
        ]);

        $provider = Provider::where('code', 'VAK_GATEWAY')->first() ?? Provider::first();

        // 1. Valorant
        $valorant = Game::updateOrCreate([
            'slug' => 'valorant',
        ], [
            'category_id' => $catGame->id,
            'name' => 'Valorant',
            'publisher' => 'Riot Games',
            'description' => 'Top Up Riot Points (VP) resmi & Battle Pass Valorant kilat otomatis detik itu juga.',
            'image' => '/images/games/valorant.jpg',
            'target_field_name' => 'Riot ID (Username#Tagline)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: TenZ#NA1 atau VAK#ID1',
            'status' => 'active',
        ]);

        $valorantItems = [
            ['name' => 'Valorant 125 Points (VP)', 'sku' => 'VAL-125', 'cost' => 14000, 'sell' => 15500, 'badge' => null, 'sub' => 'Points (VP)', 'sort' => 1],
            ['name' => 'Valorant 420 Points (VP)', 'sku' => 'VAL-420', 'cost' => 45000, 'sell' => 49500, 'badge' => 'POPULER', 'sub' => 'Points (VP)', 'sort' => 2],
            ['name' => 'Valorant 700 Points (VP)', 'sku' => 'VAL-700', 'cost' => 75000, 'sell' => 82500, 'badge' => 'FLASH SALE', 'sub' => 'Points (VP)', 'sort' => 3],
            ['name' => 'Valorant 1375 Points (VP)', 'sku' => 'VAL-1375', 'cost' => 142000, 'sell' => 154500, 'badge' => 'BEST VALUE', 'sub' => 'Points (VP)', 'sort' => 4],
            ['name' => 'Valorant 2400 Points (VP)', 'sku' => 'VAL-2400', 'cost' => 245000, 'sell' => 265000, 'badge' => 'VIP', 'sub' => 'Points (VP)', 'sort' => 5],
            ['name' => 'Valorant 4000 Points (VP)', 'sku' => 'VAL-4000', 'cost' => 395000, 'sell' => 425000, 'badge' => null, 'sub' => 'Points (VP)', 'sort' => 6],
            ['name' => 'Valorant 8150 Points (VP)', 'sku' => 'VAL-8150', 'cost' => 780000, 'sell' => 840000, 'badge' => 'SULTAN', 'sub' => 'Points (VP)', 'sort' => 7],
            ['name' => 'Valorant Battle Pass Season', 'sku' => 'VAL-BP', 'cost' => 110000, 'sell' => 122000, 'badge' => 'EXCLUSIVE', 'sub' => 'Battle Pass & Bundle', 'sort' => 8],
            ['name' => 'Valorant Premium Deluxe Pack', 'sku' => 'VAL-PREMIUM-PACK', 'cost' => 320000, 'sell' => 349000, 'badge' => 'VIP BUNDLE', 'sub' => 'Battle Pass & Bundle', 'sort' => 9],
        ];

        foreach ($valorantItems as $item) {
            Product::updateOrCreate([
                'sku' => $item['sku'],
            ], [
                'category_id' => $catGame->id,
                'game_id' => $valorant->id,
                'provider_id' => $provider?->id,
                'name' => $item['name'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Points & Battle Pass Valorant resmi instan.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // 2. Free Fire MAX
        $ffMax = Game::updateOrCreate([
            'slug' => 'free-fire-max',
        ], [
            'category_id' => $catGame->id,
            'name' => 'Free Fire MAX',
            'publisher' => 'Garena Official',
            'description' => 'Top Up Diamond Free Fire MAX grafis ultra HD resmi & kilat berizin langsung ke akun.',
            'image' => '/images/games/freefiremax.jpg',
            'target_field_name' => 'Player ID',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 829102934',
            'status' => 'active',
        ]);

        $ffMaxItems = [
            ['name' => 'FF MAX Membership Mingguan', 'sku' => 'FFMAX-MEM-W', 'cost' => 28000, 'sell' => 32000, 'badge' => 'BEST SELLER', 'sub' => 'Membership & Pass', 'sort' => 1],
            ['name' => 'FF MAX Membership Bulanan', 'sku' => 'FFMAX-MEM-M', 'cost' => 112000, 'sell' => 125000, 'badge' => 'HEMAT 70%', 'sub' => 'Membership & Pass', 'sort' => 2],
            ['name' => 'FF MAX Level Up Pass', 'sku' => 'FFMAX-LVL-PASS', 'cost' => 15000, 'sell' => 17500, 'badge' => 'EXCLUSIVE', 'sub' => 'Membership & Pass', 'sort' => 3],
            ['name' => 'FF MAX 70 Diamond', 'sku' => 'FFMAX-70', 'cost' => 9200, 'sell' => 10500, 'badge' => 'FLASH SALE', 'sub' => 'Diamond', 'sort' => 4],
            ['name' => 'FF MAX 140 Diamond', 'sku' => 'FFMAX-140', 'cost' => 18400, 'sell' => 20800, 'badge' => 'POPULER', 'sub' => 'Diamond', 'sort' => 5],
            ['name' => 'FF MAX 355 Diamond', 'sku' => 'FFMAX-355', 'cost' => 45500, 'sell' => 51000, 'badge' => null, 'sub' => 'Diamond', 'sort' => 6],
            ['name' => 'FF MAX 720 Diamond', 'sku' => 'FFMAX-720', 'cost' => 91000, 'sell' => 101500, 'badge' => 'BEST VALUE', 'sub' => 'Diamond', 'sort' => 7],
            ['name' => 'FF MAX 1440 Diamond', 'sku' => 'FFMAX-1440', 'cost' => 180000, 'sell' => 199000, 'badge' => 'VIP', 'sub' => 'Diamond', 'sort' => 8],
            ['name' => 'FF MAX 2000 Diamond', 'sku' => 'FFMAX-2000', 'cost' => 248000, 'sell' => 275000, 'badge' => 'SULTAN', 'sub' => 'Diamond', 'sort' => 9],
        ];

        foreach ($ffMaxItems as $item) {
            Product::updateOrCreate([
                'sku' => $item['sku'],
            ], [
                'category_id' => $catGame->id,
                'game_id' => $ffMax->id,
                'provider_id' => $provider?->id,
                'name' => $item['name'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Diamond & Membership Free Fire MAX resmi instan.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // 3. Honor of Kings
        $hok = Game::updateOrCreate([
            'slug' => 'honor-of-kings',
        ], [
            'category_id' => $catGame->id,
            'name' => 'Honor of Kings',
            'publisher' => 'Level Infinite',
            'description' => 'Isi ulang Tokens & Weekly Card Honor of Kings resmi harga terjangkau 24 jam nonstop.',
            'image' => '/images/games/hok.jpg',
            'target_field_name' => 'Player ID (UID)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 8391029381',
            'status' => 'active',
        ]);

        $hokItems = [
            ['name' => 'Honor of Kings Weekly Card', 'sku' => 'HOK-WC', 'cost' => 14500, 'sell' => 16500, 'badge' => 'BEST SELLER', 'sub' => 'Weekly / Monthly Card', 'sort' => 1],
            ['name' => 'Honor of Kings Weekly Card Plus', 'sku' => 'HOK-WCP', 'cost' => 42000, 'sell' => 47500, 'badge' => 'POPULER', 'sub' => 'Weekly / Monthly Card', 'sort' => 2],
            ['name' => 'Honor of Kings 80 (+8) Tokens', 'sku' => 'HOK-88', 'cost' => 14000, 'sell' => 16000, 'badge' => 'FLASH SALE', 'sub' => 'Tokens', 'sort' => 3],
            ['name' => 'Honor of Kings 240 (+24) Tokens', 'sku' => 'HOK-264', 'cost' => 41000, 'sell' => 46000, 'badge' => 'POPULER', 'sub' => 'Tokens', 'sort' => 4],
            ['name' => 'Honor of Kings 400 (+40) Tokens', 'sku' => 'HOK-440', 'cost' => 68000, 'sell' => 76000, 'badge' => null, 'sub' => 'Tokens', 'sort' => 5],
            ['name' => 'Honor of Kings 800 (+85) Tokens', 'sku' => 'HOK-885', 'cost' => 135000, 'sell' => 149000, 'badge' => 'BEST VALUE', 'sub' => 'Tokens', 'sort' => 6],
            ['name' => 'Honor of Kings 1200 (+135) Tokens', 'sku' => 'HOK-1335', 'cost' => 200000, 'sell' => 220000, 'badge' => 'VIP', 'sub' => 'Tokens', 'sort' => 7],
            ['name' => 'Honor of Kings 2400 (+275) Tokens', 'sku' => 'HOK-2675', 'cost' => 395000, 'sell' => 435000, 'badge' => 'SULTAN', 'sub' => 'Tokens', 'sort' => 8],
        ];

        foreach ($hokItems as $item) {
            Product::updateOrCreate([
                'sku' => $item['sku'],
            ], [
                'category_id' => $catGame->id,
                'game_id' => $hok->id,
                'provider_id' => $provider?->id,
                'name' => $item['name'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Tokens & Weekly Card Honor of Kings resmi.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // 4. Call of Duty: Mobile
        $codm = Game::updateOrCreate([
            'slug' => 'call-of-duty-mobile',
        ], [
            'category_id' => $catGame->id,
            'name' => 'Call of Duty: Mobile',
            'publisher' => 'Garena / Activision',
            'description' => 'Beli CP (Call of Duty Points) & Premium Battle Pass CODM kilat otomatis 1-3 detik.',
            'image' => '/images/games/codm.jpg',
            'target_field_name' => 'OpenID / Player ID',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 8192039102938491',
            'status' => 'active',
        ]);

        $codmItems = [
            ['name' => 'CODM Premium Battle Pass', 'sku' => 'CODM-BP-PREMIUM', 'cost' => 48000, 'sell' => 55000, 'badge' => 'BEST SELLER', 'sub' => 'Battle Pass & Special', 'sort' => 1],
            ['name' => 'CODM Battle Pass Bundle (Plus Tier)', 'sku' => 'CODM-BP-BUNDLE', 'cost' => 115000, 'sell' => 129000, 'badge' => 'EXCLUSIVE', 'sub' => 'Battle Pass & Special', 'sort' => 2],
            ['name' => 'CODM 31 CP', 'sku' => 'CODM-31', 'cost' => 4800, 'sell' => 5500, 'badge' => null, 'sub' => 'CP Points', 'sort' => 3],
            ['name' => 'CODM 62 CP', 'sku' => 'CODM-62', 'cost' => 9500, 'sell' => 11000, 'badge' => 'FLASH SALE', 'sub' => 'CP Points', 'sort' => 4],
            ['name' => 'CODM 127 CP', 'sku' => 'CODM-127', 'cost' => 19000, 'sell' => 21500, 'badge' => 'POPULER', 'sub' => 'CP Points', 'sort' => 5],
            ['name' => 'CODM 317 CP', 'sku' => 'CODM-317', 'cost' => 47000, 'sell' => 53000, 'badge' => null, 'sub' => 'CP Points', 'sort' => 6],
            ['name' => 'CODM 634 CP', 'sku' => 'CODM-634', 'cost' => 93000, 'sell' => 104000, 'badge' => 'BEST VALUE', 'sub' => 'CP Points', 'sort' => 7],
            ['name' => 'CODM 1373 CP', 'sku' => 'CODM-1373', 'cost' => 188000, 'sell' => 209000, 'badge' => 'VIP', 'sub' => 'CP Points', 'sort' => 8],
            ['name' => 'CODM 3564 CP', 'sku' => 'CODM-3564', 'cost' => 460000, 'sell' => 510000, 'badge' => 'SULTAN', 'sub' => 'CP Points', 'sort' => 9],
        ];

        foreach ($codmItems as $item) {
            Product::updateOrCreate([
                'sku' => $item['sku'],
            ], [
                'category_id' => $catGame->id,
                'game_id' => $codm->id,
                'provider_id' => $provider?->id,
                'name' => $item['name'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'CP Points & Battle Pass Call of Duty Mobile resmi.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // 5. eFootball
        $efootball = Game::updateOrCreate([
            'slug' => 'efootball',
        ], [
            'category_id' => $catGame->id,
            'name' => 'eFootball',
            'publisher' => 'KONAMI',
            'description' => 'Beli eFootball Coins resmi untuk rekrut pemain Epic & Showtime, serta aktivasi Match Pass instant.',
            'image' => '/images/games/efootball.jpg',
            'target_field_name' => 'Konami ID / User ID',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 92837162',
            'status' => 'active',
        ]);

        $efootballItems = [
            ['name' => 'eFootball Match Pass Regular', 'sku' => 'EFT-MATCHPASS-REG', 'cost' => 45000, 'sell' => 51000, 'badge' => 'BEST SELLER', 'sub' => 'Match Pass & Bundle', 'sort' => 1],
            ['name' => 'eFootball Match Pass Value', 'sku' => 'EFT-MATCHPASS-VAL', 'cost' => 90000, 'sell' => 102000, 'badge' => 'POPULER', 'sub' => 'Match Pass & Bundle', 'sort' => 2],
            ['name' => '130 eFootball Coins', 'sku' => 'EFT-130', 'cost' => 15000, 'sell' => 17500, 'badge' => 'FLASH SALE', 'sub' => 'eFootball Coins', 'sort' => 3],
            ['name' => '550 eFootball Coins', 'sku' => 'EFT-550', 'cost' => 65000, 'sell' => 73000, 'badge' => 'POPULER', 'sub' => 'eFootball Coins', 'sort' => 4],
            ['name' => '1050 eFootball Coins', 'sku' => 'EFT-1050', 'cost' => 120000, 'sell' => 134000, 'badge' => 'BEST VALUE', 'sub' => 'eFootball Coins', 'sort' => 5],
            ['name' => '2130 eFootball Coins', 'sku' => 'EFT-2130', 'cost' => 235000, 'sell' => 260000, 'badge' => 'VIP', 'sub' => 'eFootball Coins', 'sort' => 6],
            ['name' => '3250 eFootball Coins', 'sku' => 'EFT-3250', 'cost' => 350000, 'sell' => 388000, 'badge' => null, 'sub' => 'eFootball Coins', 'sort' => 7],
            ['name' => '5700 eFootball Coins', 'sku' => 'EFT-5700', 'cost' => 590000, 'sell' => 650000, 'badge' => 'SULTAN', 'sub' => 'eFootball Coins', 'sort' => 8],
        ];

        foreach ($efootballItems as $item) {
            Product::updateOrCreate([
                'sku' => $item['sku'],
            ], [
                'category_id' => $catGame->id,
                'game_id' => $efootball->id,
                'provider_id' => $provider?->id,
                'name' => $item['name'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'eFootball Coins & Match Pass resmi instan.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }
    }
}
