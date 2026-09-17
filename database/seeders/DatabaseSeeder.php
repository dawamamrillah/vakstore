<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use App\Models\Payment;
use App\Models\PpobServiceOption;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Settings
        Setting::set('app_name', 'VAKSTORE');
        Setting::set('tagline', 'Exclusive Game Top Up & PPOB Portal');
        Setting::set('contact_whatsapp', '6281234567890');
        Setting::set('contact_email', 'support@vakstore.id');
        Setting::set('admin_fee_default', '0');
        Setting::set('min_deposit', '10000');
        Setting::set('max_deposit', '10000000');

        // 2. Users & Wallets
        $admin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@vakstore.id',
            'phone' => '081122334455',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $adminWallet = Wallet::create([
            'user_id' => $admin->id,
            'balance' => 10000000,
        ]);

        $user = User::create([
            'name' => 'Rian Pratama',
            'email' => 'rian@vakstore.id',
            'phone' => '081298765432',
            'avatar' => 'https://lh3.googleusercontent.com/aida/AEtjO1Vz35s7cNNz41844hTRs2yYeWUsQADZmv1cBBT0xB8I9IHvSZy8Uxv9lc45vs94e3sw3tVzmEQ1LWYn7Waj8RUgPoaqAG6S7HCmYlF_PVh_Cia92wuV36EjDpKb7PrI2C1HjOP8fSt1BPfFg_lj-FAnIB-gxcZMCsXUdvZehyrHhhYfndSZ1w4mBzgJK8usJWzeHLuWzxbQb8pUGRgIF85A_mPLuqM870EVWONUFXfUTKImXJKcPcNDvIZJ',
            'role' => 'user',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $userWallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 450000,
        ]);

        // Seed initial wallet transactions for Rian Pratama
        WalletTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $userWallet->id,
            'type' => 'deposit',
            'amount' => 500000,
            'balance_before' => 0,
            'balance_after' => 500000,
            'reference' => 'DEP-20260901-001',
            'description' => 'Top Up Saldo via QRIS Instant Vault',
            'created_at' => now()->subDays(5),
        ]);

        WalletTransaction::create([
            'user_id' => $user->id,
            'wallet_id' => $userWallet->id,
            'type' => 'purchase',
            'amount' => 50000,
            'balance_before' => 500000,
            'balance_after' => 450000,
            'reference' => 'TRX-20260905-99281',
            'description' => 'Pembelian MLBB 172 Diamond (12849102)',
            'created_at' => now()->subDays(2),
        ]);

        // 3. Categories
        $catGame = Category::create([
            'name' => 'Top Up Game',
            'slug' => 'top-up-game',
            'type' => 'game',
            'icon' => 'sports_esports',
            'status' => 'active',
        ]);

        $catPulsa = Category::create([
            'name' => 'Pulsa & Operator',
            'slug' => 'pulsa-all-operator',
            'type' => 'ppob',
            'icon' => 'phone_iphone',
            'status' => 'active',
        ]);

        $catPln = Category::create([
            'name' => 'Token Listrik PLN',
            'slug' => 'pln',
            'type' => 'ppob',
            'icon' => 'bolt',
            'status' => 'active',
        ]);

        $catPasca = Category::create([
            'name' => 'Tagihan Pascabayar',
            'slug' => 'tagihan-pascabayar',
            'type' => 'ppob',
            'icon' => 'receipt_long',
            'status' => 'active',
        ]);

        // 4. Provider
        $provider = Provider::create([
            'name' => 'VAK Fast Delivery Core',
            'code' => 'VAK_GATEWAY',
            'base_url' => 'https://api.vakstore.id/v1',
            'api_key' => 'VAK_LIVE_SEC_8829102948172635489',
            'api_secret' => 'SECRET_ENCRYPTED_KEY_2026',
            'status' => 'active',
        ]);

        // 5. Games & Services
        $mlbb = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Mobile Legends: Bang Bang',
            'slug' => 'mobile-legends',
            'publisher' => 'Moonton Official',
            'description' => 'Isi ulang Diamond resmi & Weekly Pass secara instan. Cukup masukkan User ID dan Zone ID.',
            'image' => '/images/games/ml.jpg',
            'target_field_name' => 'User ID (Akun Utama)',
            'target_secondary_field_name' => 'Zone ID',
            'has_secondary_target' => true,
            'target_placeholder' => 'Contoh: 12849102',
            'target_secondary_placeholder' => '(2314)',
            'status' => 'active',
        ]);

        $ff = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Free Fire',
            'slug' => 'free-fire',
            'publisher' => 'Garena Official',
            'description' => 'Top Up Diamond Free Fire kilat otomatis masuk 1-3 detik berizin resmi.',
            'image' => '/images/games/freefire.jpg',
            'target_field_name' => 'Player ID',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 829102934',
            'status' => 'active',
        ]);

        $pubg = Game::create([
            'category_id' => $catGame->id,
            'name' => 'PUBG Mobile',
            'slug' => 'pubg-mobile',
            'publisher' => 'Level Infinite',
            'description' => 'Isi Unknown Cash (UC) PUBG Mobile instan dengan harga modal paling kompetitif.',
            'image' => '/images/games/pubgm.jpg',
            'target_field_name' => 'Character ID (Player ID)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 5129384910',
            'status' => 'active',
        ]);

        $roblox = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Roblox',
            'slug' => 'roblox',
            'publisher' => 'Roblox Corporation',
            'description' => 'Voucher Gift Card Robux resmi dan aman untuk semua item avatar & game pass.',
            'image' => '/images/games/roblox.jpg',
            'target_field_name' => 'Username Roblox / Email',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: RobloxPlayer2026',
            'status' => 'active',
        ]);

        $magicChess = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Magic Chess: Go Go',
            'slug' => 'magic-chess-go-go',
            'publisher' => 'Moonton Games',
            'description' => 'Top Up Diamond & Commander Pass Magic Chess Go Go proses kilat 1 detik.',
            'image' => '/images/games/mcgg.png',
            'target_field_name' => 'User ID',
            'target_secondary_field_name' => 'Zone ID',
            'has_secondary_target' => true,
            'target_placeholder' => 'Contoh: 98127361',
            'target_secondary_placeholder' => '(2041)',
            'status' => 'active',
        ]);

        $valorant = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Valorant',
            'slug' => 'valorant',
            'publisher' => 'Riot Games',
            'description' => 'Top Up Riot Points (VP) resmi & Battle Pass Valorant kilat otomatis detik itu juga.',
            'image' => '/images/games/valorant.jpg',
            'target_field_name' => 'Riot ID (Username#Tagline)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: TenZ#NA1 atau VAK#ID1',
            'status' => 'active',
        ]);

        $ffMax = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Free Fire MAX',
            'slug' => 'free-fire-max',
            'publisher' => 'Garena Official',
            'description' => 'Top Up Diamond Free Fire MAX grafis ultra HD resmi & kilat berizin langsung ke akun.',
            'image' => '/images/games/freefiremax.jpg',
            'target_field_name' => 'Player ID',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 829102934',
            'status' => 'active',
        ]);

        $hok = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Honor of Kings',
            'slug' => 'honor-of-kings',
            'publisher' => 'Level Infinite',
            'description' => 'Isi ulang Tokens & Weekly Card Honor of Kings resmi harga terjangkau 24 jam nonstop.',
            'image' => '/images/games/hok.jpg',
            'target_field_name' => 'Player ID (UID)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 8391029381',
            'status' => 'active',
        ]);

        $codm = Game::create([
            'category_id' => $catGame->id,
            'name' => 'Call of Duty: Mobile',
            'slug' => 'call-of-duty-mobile',
            'publisher' => 'Garena / Activision',
            'description' => 'Beli CP (Call of Duty Points) & Premium Battle Pass CODM kilat otomatis 1-3 detik.',
            'image' => '/images/games/codm.jpg',
            'target_field_name' => 'OpenID / Player ID',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 8192039102938491',
            'status' => 'active',
        ]);

        $efootball = Game::create([
            'category_id' => $catGame->id,
            'name' => 'eFootball',
            'slug' => 'efootball',
            'publisher' => 'KONAMI',
            'description' => 'Beli eFootball Coins resmi untuk rekrut pemain Epic & Showtime, serta aktivasi Match Pass instant.',
            'image' => '/images/games/efootball.jpg',
            'target_field_name' => 'Konami ID / User ID',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 92837162',
            'status' => 'active',
        ]);

        // PPOB & Operator Services (Distinct Service Categories)
        $plnService = Game::create([
            'category_id' => $catPln->id,
            'name' => 'PLN',
            'slug' => 'pln',
            'publisher' => 'PT PLN (Persero)',
            'description' => 'Layanan kelistrikan resmi PLN: Beli Token Prabayar instan atau Bayar Tagihan Listrik Pascabayar otomatis lunas.',
            'image' => '/images/ppob/pln.svg',
            'target_field_name' => 'No. Meter / ID Pelanggan PLN',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 14298102948',
            'status' => 'active',
        ]);

        $tsel = Game::create([
            'category_id' => $catPulsa->id,
            'name' => 'Telkomsel',
            'slug' => 'telkomsel',
            'publisher' => 'PT Telkomsel',
            'description' => 'Isi pulsa & paket data kuota Telkomsel prabayar instan aktif 24 jam.',
            'image' => '/images/ppob/pulsa.svg',
            'target_field_name' => 'Nomor Handphone (08xx)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 081234567890',
            'status' => 'active',
        ]);

        $axis = Game::create([
            'category_id' => $catPulsa->id,
            'name' => 'AXIS',
            'slug' => 'axis',
            'publisher' => 'PT XL Axiata Tbk (AXIS)',
            'description' => 'Isi pulsa & paket kuota data internet AXIS Iritology instan otomatis.',
            'image' => '/images/ppob/pulsa.svg',
            'target_field_name' => 'Nomor Handphone (08xx)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 083812345678',
            'status' => 'active',
        ]);

        $xl = Game::create([
            'category_id' => $catPulsa->id,
            'name' => 'XL Axiata',
            'slug' => 'xl',
            'publisher' => 'PT XL Axiata Tbk',
            'description' => 'Isi pulsa & paket data XL Axiata cepat, murah dan terpercaya.',
            'image' => '/images/ppob/pulsa.svg',
            'target_field_name' => 'Nomor Handphone (08xx)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 087812345678',
            'status' => 'active',
        ]);

        $tri = Game::create([
            'category_id' => $catPulsa->id,
            'name' => 'Tri (3) Indonesia',
            'slug' => 'tri',
            'publisher' => 'Indosat Ooredoo Hutchison',
            'description' => 'Isi pulsa & paket kuota AlwaysOn Tri (3) termurah proses otomatis.',
            'image' => '/images/ppob/pulsa.svg',
            'target_field_name' => 'Nomor Handphone (08xx)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 089612345678',
            'status' => 'active',
        ]);

        $smartfren = Game::create([
            'category_id' => $catPulsa->id,
            'name' => 'Smartfren',
            'slug' => 'smartfren',
            'publisher' => 'PT Smartfren Telecom Tbk',
            'description' => 'Isi pulsa & paket data internet kuota Smartfren 4G/5G aktif 24 jam.',
            'image' => '/images/ppob/pulsa.svg',
            'target_field_name' => 'Nomor Handphone (08xx)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 088112345678',
            'status' => 'active',
        ]);

        $isat = Game::create([
            'category_id' => $catPulsa->id,
            'name' => 'Indosat Ooredoo IM3',
            'slug' => 'indosat',
            'publisher' => 'Indosat Ooredoo Hutchison',
            'description' => 'Isi pulsa & paket internet data IM3 Indosat tercepat 24 jam nonstop.',
            'image' => '/images/ppob/pulsa.svg',
            'target_field_name' => 'Nomor Handphone (08xx)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 085712345678',
            'status' => 'active',
        ]);

        $byu = Game::create([
            'category_id' => $catPulsa->id,
            'name' => 'by.U (Telkomsel)',
            'slug' => 'byu',
            'publisher' => 'PT Telkomsel (by.U)',
            'description' => 'Isi pulsa & kuota data by.U Serba Yang Kamu Mau serba instan tanpa jeda.',
            'image' => '/images/ppob/pulsa.svg',
            'target_field_name' => 'Nomor Handphone (08xx)',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 085112345678',
            'status' => 'active',
        ]);

        $pdamService = Game::create([
            'category_id' => $catPasca->id,
            'name' => 'Tagihan Air PDAM',
            'slug' => 'pdam-nusantara',
            'publisher' => 'PDAM / Perumda Air Minum',
            'description' => 'Cek dan bayar tagihan air PDAM seluruh Indonesia mudah, cepat dan bebas antre.',
            'image' => '/images/ppob/pdam.svg',
            'target_field_name' => 'Nomor Sambungan / ID Pelanggan PDAM',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 102938491',
            'status' => 'active',
        ]);

        $telkomService = Game::create([
            'category_id' => $catPasca->id,
            'name' => 'Tagihan Internet',
            'slug' => 'telkom-indihome',
            'publisher' => 'PT Telkom Indonesia / CBN / MyRepublic / Biznet',
            'description' => 'Bayar tagihan internet IndiHome dan provider internet online otomatis lunas.',
            'image' => '/images/ppob/telkom.svg',
            'target_field_name' => 'Nomor Pelanggan Internet / IndiHome',
            'target_secondary_field_name' => null,
            'has_secondary_target' => false,
            'target_placeholder' => 'Contoh: 121345678901',
            'status' => 'active',
        ]);

        // 6. Products
        // MLBB Products
        $mlbbItems = [
            // Special Items
            ['name' => 'Twilight Pass Season', 'sku' => 'MLBB-TWILIGHT', 'cost' => 135000, 'sell' => 149000, 'badge' => 'EXCLUSIVE', 'sub' => 'Special Items', 'sort' => 1],
            ['name' => 'Starlight Member Premium', 'sku' => 'MLBB-STARLIGHT', 'cost' => 140000, 'sell' => 155000, 'badge' => 'POPULER', 'sub' => 'Special Items', 'sort' => 2],
            ['name' => 'Double 11 Special Bundle', 'sku' => 'MLBB-D11-BUNDLE', 'cost' => 78000, 'sell' => 89000, 'badge' => 'EVENT', 'sub' => 'Special Items', 'sort' => 3],
            // Weekly/Monthly Pack
            ['name' => 'Weekly Diamond Pass (WDP)', 'sku' => 'MLBB-WDP', 'cost' => 25000, 'sell' => 28500, 'badge' => 'BEST SELLER', 'sub' => 'Weekly/Monthly Pack', 'sort' => 4],
            ['name' => 'Weekly Diamond Pass (2x WDP)', 'sku' => 'MLBB-WDP-2X', 'cost' => 50000, 'sell' => 56000, 'badge' => 'HEMAT', 'sub' => 'Weekly/Monthly Pack', 'sort' => 5],
            ['name' => 'Monthly Epic Subscription', 'sku' => 'MLBB-MONTHLY-EPIC', 'cost' => 95000, 'sell' => 108000, 'badge' => 'VIP', 'sub' => 'Weekly/Monthly Pack', 'sort' => 6],
            // Top Up
            ['name' => 'MLBB 86 Diamond', 'sku' => 'MLBB-86', 'cost' => 18500, 'sell' => 21000, 'badge' => 'FLASH SALE', 'sub' => 'Top Up', 'sort' => 7],
            ['name' => 'MLBB 172 Diamond', 'sku' => 'MLBB-172', 'cost' => 37000, 'sell' => 41500, 'badge' => 'POPULER', 'sub' => 'Top Up', 'sort' => 8],
            ['name' => 'MLBB 257 Diamond', 'sku' => 'MLBB-257', 'cost' => 56000, 'sell' => 62500, 'badge' => null, 'sub' => 'Top Up', 'sort' => 9],
            ['name' => 'MLBB 344 Diamond', 'sku' => 'MLBB-344', 'cost' => 74500, 'sell' => 83000, 'badge' => null, 'sub' => 'Top Up', 'sort' => 10],
            ['name' => 'MLBB 706 Diamond', 'sku' => 'MLBB-706', 'cost' => 151000, 'sell' => 167500, 'badge' => 'BEST VALUE', 'sub' => 'Top Up', 'sort' => 11],
            ['name' => 'MLBB 2195 Diamond', 'sku' => 'MLBB-2195', 'cost' => 460000, 'sell' => 505000, 'badge' => 'VIP EXCLUSIVE', 'sub' => 'Top Up', 'sort' => 12],
            ['name' => 'MLBB 5000 Diamond', 'sku' => 'MLBB-5000', 'cost' => 1040000, 'sell' => 1140000, 'badge' => 'SULTAN', 'sub' => 'Top Up', 'sort' => 13],
        ];

        foreach ($mlbbItems as $item) {
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $mlbb->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Pengiriman instan 1-3 detik langsung masuk ke akun MLBB.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // Free Fire Products
        $ffItems = [
            // Membership
            ['name' => 'Free Fire Membership Mingguan', 'sku' => 'FF-MEMBERSHIP-W', 'cost' => 28000, 'sell' => 32000, 'badge' => 'BEST SELLER', 'sub' => 'Membership', 'sort' => 1],
            ['name' => 'Free Fire Membership Bulanan', 'sku' => 'FF-MEMBERSHIP-M', 'cost' => 112000, 'sell' => 125000, 'badge' => 'HEMAT 70%', 'sub' => 'Membership', 'sort' => 2],
            ['name' => 'Membership Mingguan VIP (Double Pass)', 'sku' => 'FF-MEMBERSHIP-VIP', 'cost' => 55000, 'sell' => 62000, 'badge' => 'VIP', 'sub' => 'Membership', 'sort' => 3],
            // Top Up
            ['name' => 'Free Fire 50 Diamond', 'sku' => 'FF-50', 'cost' => 6500, 'sell' => 7500, 'badge' => null, 'sub' => 'Top Up', 'sort' => 4],
            ['name' => 'Free Fire 70 Diamond', 'sku' => 'FF-70', 'cost' => 9200, 'sell' => 10500, 'badge' => 'FLASH SALE', 'sub' => 'Top Up', 'sort' => 5],
            ['name' => 'Free Fire 140 Diamond', 'sku' => 'FF-140', 'cost' => 18400, 'sell' => 20800, 'badge' => 'POPULER', 'sub' => 'Top Up', 'sort' => 6],
            ['name' => 'Free Fire 355 Diamond', 'sku' => 'FF-355', 'cost' => 45500, 'sell' => 51000, 'badge' => null, 'sub' => 'Top Up', 'sort' => 7],
            ['name' => 'Free Fire 720 Diamond', 'sku' => 'FF-720', 'cost' => 91000, 'sell' => 101500, 'badge' => 'BEST VALUE', 'sub' => 'Top Up', 'sort' => 8],
            ['name' => 'Free Fire 1440 Diamond', 'sku' => 'FF-1440', 'cost' => 180000, 'sell' => 199000, 'badge' => 'VIP', 'sub' => 'Top Up', 'sort' => 9],
            ['name' => 'Free Fire 2000 Diamond', 'sku' => 'FF-2000', 'cost' => 248000, 'sell' => 275000, 'badge' => 'SULTAN', 'sub' => 'Top Up', 'sort' => 10],
        ];

        foreach ($ffItems as $item) {
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $ff->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Top Up Diamond Free Fire resmi instan.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // PUBG Mobile Products
        $pubgItems = [
            ['name' => 'PUBG Mobile 60 UC', 'sku' => 'PUBG-60', 'cost' => 14000, 'sell' => 16000, 'badge' => null, 'sub' => 'UC', 'sort' => 1],
            ['name' => 'PUBG Mobile 325 UC', 'sku' => 'PUBG-325', 'cost' => 68000, 'sell' => 76000, 'badge' => 'POPULER', 'sub' => 'UC', 'sort' => 2],
            ['name' => 'PUBG Mobile 660 UC', 'sku' => 'PUBG-660', 'cost' => 135000, 'sell' => 149000, 'badge' => 'BEST VALUE', 'sub' => 'UC', 'sort' => 3],
            ['name' => 'PUBG Mobile 1800 UC', 'sku' => 'PUBG-1800', 'cost' => 365000, 'sell' => 399000, 'badge' => 'VIP', 'sub' => 'UC', 'sort' => 4],
        ];

        foreach ($pubgItems as $item) {
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $pubg->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Isi Unknown Cash PUBG Mobile.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // Roblox Products
        $robloxItems = [
            // Voucher Gift Card
            ['name' => 'Roblox Gift Card Rp 50.000', 'sku' => 'RBLX-GC-50K', 'cost' => 48500, 'sell' => 52000, 'badge' => 'INSTANT CODE', 'sub' => 'Voucher Gift Card', 'sort' => 1],
            ['name' => 'Roblox Gift Card Rp 100.000', 'sku' => 'RBLX-GC-100K', 'cost' => 96500, 'sell' => 103000, 'badge' => 'POPULER', 'sub' => 'Voucher Gift Card', 'sort' => 2],
            ['name' => 'Roblox Gift Card Rp 250.000', 'sku' => 'RBLX-GC-250K', 'cost' => 240000, 'sell' => 255000, 'badge' => 'BEST VALUE', 'sub' => 'Voucher Gift Card', 'sort' => 3],
            ['name' => 'Roblox Gift Card $10 USD Global', 'sku' => 'RBLX-GC-USD10', 'cost' => 155000, 'sell' => 168000, 'badge' => 'GLOBAL', 'sub' => 'Voucher Gift Card', 'sort' => 4],
            // Robux
            ['name' => 'Roblox 80 Robux', 'sku' => 'RBLX-80', 'cost' => 15000, 'sell' => 17500, 'badge' => null, 'sub' => 'Robux', 'sort' => 5],
            ['name' => 'Roblox 400 Robux', 'sku' => 'RBLX-400', 'cost' => 74000, 'sell' => 83000, 'badge' => 'POPULER', 'sub' => 'Robux', 'sort' => 6],
            ['name' => 'Roblox 800 Robux', 'sku' => 'RBLX-800', 'cost' => 146000, 'sell' => 162000, 'badge' => 'BEST VALUE', 'sub' => 'Robux', 'sort' => 7],
            ['name' => 'Roblox 1700 Robux', 'sku' => 'RBLX-1700', 'cost' => 305000, 'sell' => 335000, 'badge' => 'VIP', 'sub' => 'Robux', 'sort' => 8],
            ['name' => 'Roblox 4500 Robux', 'sku' => 'RBLX-4500', 'cost' => 790000, 'sell' => 865000, 'badge' => 'SULTAN', 'sub' => 'Robux', 'sort' => 9],
        ];

        foreach ($robloxItems as $item) {
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $roblox->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Robux Voucher code instan.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // Magic Chess Products
        $mcItems = [
            // Special Items
            ['name' => 'Commander Pass Season', 'sku' => 'MC-PASS', 'cost' => 105000, 'sell' => 119000, 'badge' => 'EXCLUSIVE', 'sub' => 'Special Items', 'sort' => 1],
            ['name' => 'Chess Pass Premium Bundle', 'sku' => 'MC-BUNDLE', 'cost' => 160000, 'sell' => 179000, 'badge' => 'BEST VALUE', 'sub' => 'Special Items', 'sort' => 2],
            ['name' => 'Magic Chess Special Box', 'sku' => 'MC-SPECBOX', 'cost' => 48000, 'sell' => 55000, 'badge' => 'POPULER', 'sub' => 'Special Items', 'sort' => 3],
            // Top Up
            ['name' => 'Magic Chess 60 Diamond', 'sku' => 'MC-60', 'cost' => 14000, 'sell' => 16000, 'badge' => null, 'sub' => 'Top Up', 'sort' => 4],
            ['name' => 'Magic Chess 300 Diamond', 'sku' => 'MC-300', 'cost' => 69000, 'sell' => 77500, 'badge' => 'POPULER', 'sub' => 'Top Up', 'sort' => 5],
            ['name' => 'Magic Chess 600 Diamond', 'sku' => 'MC-600', 'cost' => 135000, 'sell' => 151000, 'badge' => 'BEST VALUE', 'sub' => 'Top Up', 'sort' => 6],
            ['name' => 'Magic Chess 1000 Diamond', 'sku' => 'MC-1000', 'cost' => 220000, 'sell' => 245000, 'badge' => 'VIP', 'sub' => 'Top Up', 'sort' => 7],
        ];

        foreach ($mcItems as $item) {
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $magicChess->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Diamond & Pass Magic Chess Go Go.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // Valorant Products
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
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $valorant->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
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

        // Free Fire MAX Products
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
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $ffMax->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
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

        // Honor of Kings Products
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
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $hok->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
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

        // Call of Duty: Mobile Products
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
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $codm->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
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

        // eFootball Products
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
            Product::create([
                'category_id' => $catGame->id,
                'game_id' => $efootball->id,
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
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

        // PPOB & Operator Products (PLN, Pulsa, PDAM, Telkom)
        $ppobItems = [
            // PLN - Prabayar (Token)
            ['category_id' => $catPln->id, 'game_id' => $plnService->id, 'name' => 'Token PLN Rp 20.000', 'sku' => 'PLN-20K', 'cost' => 20150, 'sell' => 21500, 'badge' => 'INSTANT', 'sub' => 'Prabayar (Token)', 'sort' => 1],
            ['category_id' => $catPln->id, 'game_id' => $plnService->id, 'name' => 'Token PLN Rp 50.000', 'sku' => 'PLN-50K', 'cost' => 50150, 'sell' => 51500, 'badge' => 'POPULER', 'sub' => 'Prabayar (Token)', 'sort' => 2],
            ['category_id' => $catPln->id, 'game_id' => $plnService->id, 'name' => 'Token PLN Rp 100.000', 'sku' => 'PLN-100K', 'cost' => 100150, 'sell' => 101500, 'badge' => 'HEMAT', 'sub' => 'Prabayar (Token)', 'sort' => 3],
            ['category_id' => $catPln->id, 'game_id' => $plnService->id, 'name' => 'Token PLN Rp 200.000', 'sku' => 'PLN-200K', 'cost' => 200150, 'sell' => 201500, 'badge' => null, 'sub' => 'Prabayar (Token)', 'sort' => 4],
            ['category_id' => $catPln->id, 'game_id' => $plnService->id, 'name' => 'Token PLN Rp 500.000', 'sku' => 'PLN-500K', 'cost' => 500150, 'sell' => 502000, 'badge' => null, 'sub' => 'Prabayar (Token)', 'sort' => 5],
            ['category_id' => $catPln->id, 'game_id' => $plnService->id, 'name' => 'Token PLN Rp 1.000.000', 'sku' => 'PLN-1000K', 'cost' => 1000150, 'sell' => 1002500, 'badge' => null, 'sub' => 'Prabayar (Token)', 'sort' => 6],
            // PLN - Pascabayar
            ['category_id' => $catPln->id, 'game_id' => $plnService->id, 'name' => 'Pembayaran Tagihan Listrik PLN Pascabayar', 'sku' => 'PLN-POSTPAID', 'cost' => 0, 'sell' => 2500, 'badge' => 'BIAYA ADMIN', 'sub' => 'Pascabayar', 'sort' => 7],

            // Telkomsel
            ['category_id' => $catPulsa->id, 'game_id' => $tsel->id, 'name' => 'Pulsa Telkomsel 10.000', 'sku' => 'TSEL-10K', 'cost' => 10300, 'sell' => 11500, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 8],
            ['category_id' => $catPulsa->id, 'game_id' => $tsel->id, 'name' => 'Pulsa Telkomsel 25.000', 'sku' => 'TSEL-25K', 'cost' => 24900, 'sell' => 26000, 'badge' => 'POPULER', 'sub' => 'Pulsa', 'sort' => 9],
            ['category_id' => $catPulsa->id, 'game_id' => $tsel->id, 'name' => 'Pulsa Telkomsel 50.000', 'sku' => 'TSEL-50K', 'cost' => 49400, 'sell' => 51000, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 10],
            ['category_id' => $catPulsa->id, 'game_id' => $tsel->id, 'name' => 'Pulsa Telkomsel 100.000', 'sku' => 'TSEL-100K', 'cost' => 97800, 'sell' => 100000, 'badge' => 'HEMAT', 'sub' => 'Pulsa', 'sort' => 11],
            ['category_id' => $catPulsa->id, 'game_id' => $tsel->id, 'name' => 'Paket Data Telkomsel 5 GB (30 Hari)', 'sku' => 'DATA-TSEL-5GB', 'cost' => 35000, 'sell' => 38500, 'badge' => 'BEST SELLER', 'sub' => 'Paket Data', 'sort' => 12],
            ['category_id' => $catPulsa->id, 'game_id' => $tsel->id, 'name' => 'Paket Data Telkomsel 15 GB (30 Hari)', 'sku' => 'DATA-TSEL-15GB', 'cost' => 68000, 'sell' => 74500, 'badge' => 'POPULER', 'sub' => 'Paket Data', 'sort' => 13],

            // Indosat
            ['category_id' => $catPulsa->id, 'game_id' => $isat->id, 'name' => 'Pulsa Indosat 25.000', 'sku' => 'ISAT-25K', 'cost' => 24800, 'sell' => 26000, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 14],
            ['category_id' => $catPulsa->id, 'game_id' => $isat->id, 'name' => 'Pulsa Indosat 50.000', 'sku' => 'ISAT-50K', 'cost' => 49200, 'sell' => 51000, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 15],
            ['category_id' => $catPulsa->id, 'game_id' => $isat->id, 'name' => 'Paket Data Indosat Freedom 10 GB (30 Hari)', 'sku' => 'DATA-ISAT-10GB', 'cost' => 45000, 'sell' => 49500, 'badge' => 'HEMAT', 'sub' => 'Paket Data', 'sort' => 16],

            // XL Axiata
            ['category_id' => $catPulsa->id, 'game_id' => $xl->id, 'name' => 'Pulsa XL 25.000', 'sku' => 'XL-25K', 'cost' => 24800, 'sell' => 26000, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 17],
            ['category_id' => $catPulsa->id, 'game_id' => $xl->id, 'name' => 'Paket Data XL Xtra Combo 20 GB (30 Hari)', 'sku' => 'DATA-XL-20GB', 'cost' => 72000, 'sell' => 79000, 'badge' => 'BEST VALUE', 'sub' => 'Paket Data', 'sort' => 18],

            // AXIS
            ['category_id' => $catPulsa->id, 'game_id' => $axis->id, 'name' => 'Pulsa AXIS 25.000', 'sku' => 'AXIS-25K', 'cost' => 24800, 'sell' => 26000, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 19],
            ['category_id' => $catPulsa->id, 'game_id' => $axis->id, 'name' => 'Paket Data AXIS Bronet 8 GB (30 Hari)', 'sku' => 'DATA-AXIS-8GB', 'cost' => 32000, 'sell' => 36000, 'badge' => 'HEMAT', 'sub' => 'Paket Data', 'sort' => 20],

            // Tri (3)
            ['category_id' => $catPulsa->id, 'game_id' => $tri->id, 'name' => 'Pulsa Tri 25.000', 'sku' => 'TRI-25K', 'cost' => 24700, 'sell' => 25900, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 21],
            ['category_id' => $catPulsa->id, 'game_id' => $tri->id, 'name' => 'Paket Data Tri AlwaysOn 10 GB', 'sku' => 'DATA-TRI-10GB', 'cost' => 38000, 'sell' => 42000, 'badge' => 'HEMAT', 'sub' => 'Paket Data', 'sort' => 22],

            // Smartfren
            ['category_id' => $catPulsa->id, 'game_id' => $smartfren->id, 'name' => 'Pulsa Smartfren 25.000', 'sku' => 'SF-25K', 'cost' => 24800, 'sell' => 26000, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 23],
            ['category_id' => $catPulsa->id, 'game_id' => $smartfren->id, 'name' => 'Paket Data Smartfren Unlimited 28 Hari', 'sku' => 'DATA-SF-UNLI', 'cost' => 60000, 'sell' => 66000, 'badge' => 'BEST SELLER', 'sub' => 'Paket Data', 'sort' => 24],

            // by.U
            ['category_id' => $catPulsa->id, 'game_id' => $byu->id, 'name' => 'Pulsa by.U 25.000', 'sku' => 'BYU-25K', 'cost' => 25000, 'sell' => 26000, 'badge' => null, 'sub' => 'Pulsa', 'sort' => 25],
            ['category_id' => $catPulsa->id, 'game_id' => $byu->id, 'name' => 'Paket Data by.U 10 GB (30 Hari)', 'sku' => 'DATA-BYU-10GB', 'cost' => 30000, 'sell' => 34000, 'badge' => 'BEST VALUE', 'sub' => 'Paket Data', 'sort' => 26],

            // PDAM & Telkom Bill Inquiry
            ['category_id' => $catPasca->id, 'game_id' => $pdamService->id, 'name' => 'Pembayaran Tagihan PDAM Nusantara', 'sku' => 'PDAM-BILL', 'cost' => 0, 'sell' => 2500, 'badge' => 'BIAYA ADMIN', 'sub' => 'Bayar Tagihan', 'sort' => 27],
            ['category_id' => $catPasca->id, 'game_id' => $telkomService->id, 'name' => 'Pembayaran Tagihan Internet & IndiHome', 'sku' => 'TELKOM-BILL', 'cost' => 0, 'sell' => 2500, 'badge' => 'BIAYA ADMIN', 'sub' => 'Bayar Tagihan', 'sort' => 28],
        ];

        foreach ($ppobItems as $item) {
            Product::create([
                'category_id' => $item['category_id'],
                'game_id' => $item['game_id'],
                'provider_id' => $provider->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'provider_sku' => 'PRV-'.$item['sku'],
                'description' => 'Layanan PPOB resmi dengan konfirmasi SN/token seketika.',
                'cost_price' => $item['cost'],
                'selling_price' => $item['sell'],
                'profit' => $item['sell'] - $item['cost'],
                'badge' => $item['badge'],
                'sub_category' => $item['sub'],
                'sort_order' => $item['sort'],
                'status' => 'active',
            ]);
        }

        // 6b. PPOB Service Options (Mapping resmi dari Digiflazz)
        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();
        $telkomProduct = Product::where('sku', 'TELKOM-BILL')->first();
        $plnProduct = Product::where('sku', 'PLN-POSTPAID')->first();

        $pascaCatalogPath = storage_path('app/digiflazz/exported_pasca_catalog.php');
        $catalog = file_exists($pascaCatalogPath) ? include $pascaCatalogPath : [];

        if (! empty($catalog)) {
            foreach ($catalog as $item) {
                $brand = $item['brand'] ?? '';
                $targetProduct = null;
                $provName = null;
                $regionName = $item['desc'] ?? null;

                if ($brand === 'PDAM' && $pdamProduct) {
                    $targetProduct = $pdamProduct;
                    $provName = 'PDAM';
                } elseif ($brand === 'INTERNET PASCABAYAR' && $telkomProduct) {
                    $targetProduct = $telkomProduct;
                    $provName = 'INTERNET';
                    $regionName = 'Nasional';
                } elseif ($brand === 'PLN PASCABAYAR' && $plnProduct) {
                    $targetProduct = $plnProduct;
                    $provName = 'PLN';
                    $regionName = 'Nasional';
                }

                if ($targetProduct) {
                    PpobServiceOption::create([
                        'product_id' => $targetProduct->id,
                        'name' => $item['product_name'],
                        'code' => Str::slug($item['product_name']),
                        'provider_name' => $provName,
                        'region_name' => $regionName,
                        'buyer_sku_code' => $item['buyer_sku_code'],
                        'status' => 'active',
                        'metadata' => [
                            'admin' => $item['admin'] ?? 2500,
                            'commission' => $item['commission'] ?? 0,
                        ],
                    ]);
                }
            }
        }

        // 7. Vouchers
        Voucher::create([
            'code' => 'TOPUPHEMAT',
            'name' => 'Diskon Spesial Weekend 25%',
            'type' => 'percentage',
            'value' => 25,
            'minimum_transaction' => 20000,
            'maximum_discount' => 10000,
            'usage_limit' => 500,
            'usage_per_user' => 3,
            'used_count' => 14,
            'status' => 'active',
        ]);

        Voucher::create([
            'code' => 'RAMADANKAREEM',
            'name' => 'Potongan Langsung Berkah Rp 15.000',
            'type' => 'fixed',
            'value' => 15000,
            'minimum_transaction' => 50000,
            'maximum_discount' => 15000,
            'usage_limit' => 200,
            'usage_per_user' => 1,
            'used_count' => 8,
            'status' => 'active',
        ]);

        Voucher::create([
            'code' => 'VAULTVIP',
            'name' => 'Cashback Member VIP 10%',
            'type' => 'percentage',
            'value' => 10,
            'minimum_transaction' => 100000,
            'maximum_discount' => 50000,
            'usage_limit' => 0,
            'usage_per_user' => 5,
            'used_count' => 3,
            'status' => 'active',
        ]);

        // 8. Initial Historical Transactions (to populate telemetry graphs and user list)
        $mlbbProduct = Product::where('sku', 'MLBB-172')->first();
        $ffProduct = Product::where('sku', 'FF-355')->first();
        $plnProduct = Product::where('sku', 'PLN-50K')->first();

        $tx1 = Transaction::create([
            'invoice_number' => 'TRX-20260906-891023',
            'user_id' => $user->id,
            'product_id' => $mlbbProduct->id,
            'provider_id' => $provider->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'target' => '12849102',
            'target_secondary' => '2314',
            'nickname' => 'RexRegum_Pro (Region: Indonesia)',
            'cost_price' => $mlbbProduct->cost_price,
            'selling_price' => $mlbbProduct->selling_price,
            'discount' => 0,
            'admin_fee' => 0,
            'total' => $mlbbProduct->selling_price,
            'profit' => $mlbbProduct->profit,
            'payment_status' => 'paid',
            'transaction_status' => 'success',
            'provider_reference' => 'PRV-MLBB-99281726',
            'serial_number' => 'SN-MLBB-99218274-1290',
            'created_at' => now()->subDays(2),
        ]);

        Payment::create([
            'transaction_id' => $tx1->id,
            'payment_method' => 'wallet',
            'payment_reference' => 'PAY-20260906-891023',
            'amount' => $tx1->total,
            'status' => 'paid',
            'paid_at' => now()->subDays(2),
        ]);

        $tx2 = Transaction::create([
            'invoice_number' => 'TRX-20260907-772810',
            'user_id' => null,
            'product_id' => $ffProduct->id,
            'provider_id' => $provider->id,
            'customer_name' => 'Agus Pratama',
            'customer_phone' => '085712349988',
            'customer_email' => 'agus@gmail.com',
            'target' => '992810291',
            'target_secondary' => null,
            'nickname' => 'VAK_GhostHunter (ID Verified)',
            'cost_price' => $ffProduct->cost_price,
            'selling_price' => $ffProduct->selling_price,
            'discount' => 5000,
            'admin_fee' => 0,
            'total' => $ffProduct->selling_price - 5000,
            'profit' => ($ffProduct->selling_price - 5000) - $ffProduct->cost_price,
            'voucher_code' => 'TOPUPHEMAT',
            'payment_status' => 'paid',
            'transaction_status' => 'success',
            'provider_reference' => 'PRV-FF-88291029',
            'serial_number' => 'SN-FF-88192019-9921',
            'created_at' => now()->subDay(),
        ]);

        Payment::create([
            'transaction_id' => $tx2->id,
            'payment_method' => 'qris',
            'payment_reference' => 'PAY-20260907-772810',
            'amount' => $tx2->total,
            'status' => 'paid',
            'paid_at' => now()->subDay(),
        ]);

        $tx3 = Transaction::create([
            'invoice_number' => 'TRX-20260908-100293',
            'user_id' => $user->id,
            'product_id' => $plnProduct->id,
            'provider_id' => $provider->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'target' => '14298102948',
            'target_secondary' => null,
            'nickname' => 'BUDI SANTOSO / R1M-900VA',
            'cost_price' => $plnProduct->cost_price,
            'selling_price' => $plnProduct->selling_price,
            'discount' => 0,
            'admin_fee' => 0,
            'total' => $plnProduct->selling_price,
            'profit' => $plnProduct->profit,
            'payment_status' => 'paid',
            'transaction_status' => 'success',
            'provider_reference' => 'PRV-PLN-10029384',
            'serial_number' => '4521-8891-2309-8812-3490',
            'created_at' => now()->subHours(2),
        ]);

        Payment::create([
            'transaction_id' => $tx3->id,
            'payment_method' => 'wallet',
            'payment_reference' => 'PAY-20260908-100293',
            'amount' => $tx3->total,
            'status' => 'paid',
            'paid_at' => now()->subHours(2),
        ]);
    }
}
