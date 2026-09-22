<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\PpobServiceOption;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VakstoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        User::firstOrCreate([
            'email' => 'rian@vakstore.id',
        ], [
            'name' => 'Rian Pratama',
            'phone' => '081234567890',
            'password' => Hash::make('password'),
            'role' => 'user',
            'balance' => 250000,
        ]);
    }

    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('VAKSTORE');
        $response->assertSee('poster12.png');
        $response->assertSee('main-search-input');
        $response->assertSee('Mobile Legends: Bang Bang');
        $response->assertSee('Free Fire');
        $response->assertSee('PLN');
        $response->assertSee('Pulsa &amp; Paket Data', false);
        $response->assertSee('QRIS Dinamis 24/7');

        // Assert no buyer login or register buttons on public layout
        $response->assertDontSee('href="'.route('login').'"', false);
        $response->assertDontSee('href="'.route('register').'"', false);
    }

    public function test_topup_game_directory_only_contains_games(): void
    {
        $response = $this->get('/top-up-game');
        $response->assertStatus(200);
        $response->assertSee('Mobile Legends: Bang Bang');
        $response->assertSee('Free Fire');
        $response->assertSee('Free Fire MAX');
        $response->assertSee('Valorant');
        $response->assertSee('Honor of Kings');
        $response->assertSee('Call of Duty: Mobile');
        $response->assertSee('eFootball');
        $response->assertSee('PUBG Mobile');
        $response->assertSee('Roblox');
        $response->assertSee('Magic Chess: Go Go');

        // Verify games list does not contain PPOB services
        $games = $response->viewData('games');
        $this->assertNotEmpty($games);
        foreach ($games as $game) {
            $this->assertNotEquals('ppob', $game->category->type);
            $this->assertNotContains($game->slug, ['pln', 'pulsa-all-operator', 'pdam-nusantara', 'telkom-indihome']);
        }

        // Must NOT contain PPOB routes in game cards
        $response->assertDontSee('/topup/pln');
        $response->assertDontSee('/topup/pdam-nusantara');
        $response->assertDontSee('/topup/pulsa-all-operator');
    }

    public function test_new_games_detail_pages_render_with_products(): void
    {
        // 1. Valorant
        $valRes = $this->get('/topup/valorant');
        $valRes->assertStatus(200);
        $valRes->assertSee('Valorant 125 Points (VP)');
        $valRes->assertSee('Points (VP)');
        $valRes->assertSee('Riot ID (Username#Tagline)');

        // 2. Free Fire MAX
        $ffMaxRes = $this->get('/topup/free-fire-max');
        $ffMaxRes->assertStatus(200);
        $ffMaxRes->assertSee('FF MAX 70 Diamond');
        $ffMaxRes->assertSee('Membership &amp; Pass', false);

        // 3. Honor of Kings
        $hokRes = $this->get('/topup/honor-of-kings');
        $hokRes->assertStatus(200);
        $hokRes->assertSee('Honor of Kings Weekly Card');
        $hokRes->assertSee('Tokens');

        // 4. Call of Duty Mobile
        $codmRes = $this->get('/topup/call-of-duty-mobile');
        $codmRes->assertStatus(200);
        $codmRes->assertSee('CODM 62 CP');
        $codmRes->assertSee('CP Points');

        // 5. eFootball
        $efRes = $this->get('/topup/efootball');
        $efRes->assertStatus(200);
        $efRes->assertSee('130 eFootball Coins');
        $efRes->assertSee('eFootball Coins');
    }

    public function test_api_check_id_validation_for_new_games(): void
    {
        // Valorant
        $val = $this->postJson('/api/check-id', [
            'game_slug' => 'valorant',
            'user_id' => 'TenZ#NA1',
        ]);
        $val->assertStatus(200)->assertJson(['status' => 'success', 'nickname' => 'VAK_ViperAce#ID1']);

        // Honor of Kings
        $hok = $this->postJson('/api/check-id', [
            'game_slug' => 'honor-of-kings',
            'user_id' => '8391029381',
        ]);
        $hok->assertStatus(200)->assertJson(['status' => 'success', 'nickname' => 'HOK_DragonMaster']);

        // CODM
        $codm = $this->postJson('/api/check-id', [
            'game_slug' => 'call-of-duty-mobile',
            'user_id' => '8192039102938491',
        ]);
        $codm->assertStatus(200)->assertJson(['status' => 'success', 'nickname' => 'Ghost_SpecOps_ID']);

        // eFootball
        $ef = $this->postJson('/api/check-id', [
            'game_slug' => 'efootball',
            'user_id' => '92837162',
        ]);
        $ef->assertStatus(200)->assertJson(['status' => 'success', 'nickname' => 'Garuda_Eleven_FC']);
    }

    public function test_game_detail_page_renders_with_products(): void
    {
        $response = $this->get('/topup/mobile-legends');
        $response->assertStatus(200);
        $response->assertSee('Lengkapi Identitas Akun');
        $response->assertSee('MLBB 86 Diamond');
        $response->assertSee('QRIS Dinamis 24 Jam');
    }

    public function test_api_check_id_validation(): void
    {
        $response = $this->postJson('/api/check-id', [
            'game_slug' => 'mobile-legends',
            'user_id' => '12849102',
            'zone_id' => '2314',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'nickname' => 'RexRegum_Pro (Region: Indonesia)',
        ]);
    }

    public function test_api_validate_voucher_success(): void
    {
        $product = Product::where('sku', 'MLBB-172')->first();

        $response = $this->postJson('/api/validate-voucher', [
            'code' => 'TOPUPHEMAT',
            'subtotal' => $product->selling_price,
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'code' => 'TOPUPHEMAT',
        ]);
        $this->assertGreaterThan(0, $response->json('discount'));
    }

    public function test_buyer_can_purchase_using_qris_and_simulate_payment(): void
    {
        $product = Product::where('sku', 'MLBB-86')->first();

        $response = $this->post('/topup/checkout', [
            'product_id' => $product->id,
            'target' => '12849102',
            'target_secondary' => '2314',
            'nickname' => 'RexRegum_Pro',
            'payment_method' => 'qris',
            'voucher_code' => '',
            'customer_name' => 'Guest Gamer',
            'customer_phone' => '081234567890',
            'customer_email' => 'gamer@example.com',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('product_id', $product->id)->latest()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('pending', $transaction->payment_status);
        $this->assertEquals('pending', $transaction->transaction_status);

        // Assert payment record with QRIS was created
        $this->assertDatabaseHas('payments', [
            'transaction_id' => $transaction->id,
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        // Simulate instant payment on invoice
        $payRes = $this->post(route('invoice.simulate-pay', $transaction->invoice_number));
        $payRes->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('paid', $transaction->payment_status);
        $this->assertEquals('success', $transaction->transaction_status);
        $this->assertNotNull($transaction->serial_number);
    }

    public function test_normal_user_cannot_access_admin_panel(): void
    {
        $user = User::where('email', 'rian@vakstore.id')->first();

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_dashboard_and_reports(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Super Admin Telemetry');

        $reports = $this->actingAs($admin)->get('/admin/reports');
        $reports->assertStatus(200);
        $reports->assertSee('Laporan Keuntungan');
    }

    public function test_admin_can_adjust_user_balance_with_audit(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();
        $user = User::where('email', 'rian@vakstore.id')->first();
        $initialBalance = $user->balance;

        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/adjust-balance", [
            'action_type' => 'add',
            'amount' => 50000,
            'reason' => 'Bonus loyalitas member VIP',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertEquals($initialBalance + 50000, $user->balance);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'adjust_user_balance',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_create_new_user_and_admin(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();

        // 1. Create new regular user
        $resUser = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Member Baru Vakstore',
            'email' => 'member.baru@vakstore.id',
            'phone' => '081299988877',
            'role' => 'user',
            'status' => 'active',
            'password' => 'secret123',
        ]);
        $resUser->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'member.baru@vakstore.id',
            'role' => 'user',
            'status' => 'active',
        ]);

        // 2. Create new sub-admin
        $resAdmin = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Admin Operasional',
            'email' => 'operasional@vakstore.id',
            'phone' => '081377766655',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'adminpass123',
        ]);
        $resAdmin->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'operasional@vakstore.id',
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_user_password(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();
        $user = User::where('email', 'rian@vakstore.id')->first();

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}/password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'change_user_password',
            'user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_delete_user_but_cannot_delete_self(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();
        $user = User::where('email', 'rian@vakstore.id')->first();

        // 1. Admin deletes user
        $delRes = $this->actingAs($admin)->delete("/admin/users/{$user->id}");
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);

        // 2. Admin cannot delete self
        $selfRes = $this->actingAs($admin)->delete("/admin/users/{$admin->id}");
        $selfRes->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }

    public function test_ppob_showcase_and_individual_service_pages(): void
    {
        // 1. Pulsa & Data Showcase / Directory
        $response = $this->get('/layanan-ppob');
        $response->assertStatus(200);
        $response->assertSee('Layanan Pulsa &amp; Data All Operator', false);
        $response->assertSee('Telkomsel');
        $response->assertSee('AXIS');
        $response->assertSee('XL Axiata');
        $response->assertSee('Tri (3) Indonesia');
        $response->assertSee('Smartfren');
        $response->assertSee('Indosat Ooredoo IM3');
        $response->assertSee('by.U (Telkomsel)');

        // 2. Individual PPOB Service (PLN)
        $resPln = $this->get('/ppob/pln');
        $resPln->assertStatus(200);
        $resPln->assertSee('PLN');
        $resPln->assertSee('Prabayar (Token Listrik)');
        $resPln->assertSee('Pascabayar (Tagihan Listrik)');

        // 3. Individual Operator Service (Telkomsel)
        $resTelkomsel = $this->get('/topup/telkomsel');
        $resTelkomsel->assertStatus(200);
        $resTelkomsel->assertSee('Telkomsel');

        // 4. PDAM & Telkom Bill payment pages
        $resPdam = $this->get('/ppob/pdam-nusantara');
        $resPdam->assertStatus(200);
        $resPdam->assertSee('Tagihan Air PDAM');

        $resTelkom = $this->get('/ppob/telkom-indihome');
        $resTelkom->assertStatus(200);
        $resTelkom->assertSee('Tagihan Internet');
    }

    public function test_bill_payment_checkout_adjusts_price_to_bill_plus_admin_fee(): void
    {
        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first(); // cost: 0, sell: 2500 (Admin Fee)

        $billAmount = 148500;
        $adminFee = (float) $pdamProduct->selling_price; // 2500
        $expectedTotal = $billAmount + $adminFee; // 151000

        $response = $this->post('/layanan-ppob/checkout', [
            'product_id' => $pdamProduct->id,
            'target' => '102938491',
            'nickname' => 'HENDRA WIJAYA - PDAM TIRTA MOEDAL',
            'payment_method' => 'qris',
            'bill_amount' => $billAmount,
            'customer_name' => 'Hendra Wijaya',
            'customer_phone' => '081298765432',
            'customer_email' => 'hendra@example.com',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('product_id', $pdamProduct->id)->latest()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($billAmount, (float) $transaction->cost_price);
        $this->assertEquals($expectedTotal, (float) $transaction->selling_price);
        $this->assertEquals($expectedTotal, (float) $transaction->total);
        $this->assertEquals($adminFee, (float) $transaction->profit);
        $this->assertEquals('pending', $transaction->payment_status);

        // Simulate payment
        $payRes = $this->post(route('invoice.simulate-pay', $transaction->invoice_number));
        $payRes->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('paid', $transaction->payment_status);
        $this->assertEquals('success', $transaction->transaction_status);
        $this->assertNotNull($transaction->serial_number);
    }

    public function test_pln_pascabayar_checkout_with_dynamic_bill(): void
    {
        $plnPostpaid = Product::where('sku', 'PLN-POSTPAID')->first(); // cost: 0, sell: 2500

        $billAmount = 245000;
        $adminFee = (float) $plnPostpaid->selling_price; // 2500
        $expectedTotal = $billAmount + $adminFee; // 247500

        $response = $this->post('/layanan-ppob/checkout', [
            'product_id' => $plnPostpaid->id,
            'target' => '14298102948',
            'nickname' => 'BUDI SANTOSO / R1M-900VA',
            'payment_method' => 'qris',
            'bill_amount' => $billAmount,
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081298765432',
            'customer_email' => 'budi@example.com',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('product_id', $plnPostpaid->id)->latest()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($billAmount, (float) $transaction->cost_price);
        $this->assertEquals($expectedTotal, (float) $transaction->selling_price);
        $this->assertEquals($expectedTotal, (float) $transaction->total);
        $this->assertEquals($adminFee, (float) $transaction->profit);
        $this->assertEquals('pending', $transaction->payment_status);

        // Simulate payment
        $payRes = $this->post(route('invoice.simulate-pay', $transaction->invoice_number));
        $payRes->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('paid', $transaction->payment_status);
        $this->assertEquals('success', $transaction->transaction_status);
        $this->assertNotNull($transaction->serial_number);
    }

    public function test_admin_can_update_product_price_with_fixed_price_and_percent_margin(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();
        $product = Product::where('sku', 'MLBB-86')->first(); // cost: 18500

        // 1. Fixed Price update
        $resFixed = $this->actingAs($admin)->post("/admin/products/{$product->id}", [
            'cost_price' => 18500,
            'pricing_mode' => 'fixed',
            'selling_price' => 22000,
            'badge' => 'HOT',
            'status' => 'active',
        ]);
        $resFixed->assertRedirect();
        $product->refresh();
        $this->assertEquals(22000, (int) $product->selling_price);
        $this->assertEquals(3500, (int) $product->profit);

        // 2. Profit Percentage update (20% margin on 18500 cost = 22200)
        $resPercent = $this->actingAs($admin)->post("/admin/products/{$product->id}", [
            'cost_price' => 18500,
            'pricing_mode' => 'percent',
            'profit_percent' => 20,
            'badge' => 'SPECIAL',
            'status' => 'active',
        ]);
        $resPercent->assertRedirect();
        $product->refresh();
        $this->assertEquals(22200, (int) $product->selling_price);
        $this->assertEquals(3700, (int) $product->profit);
    }

    public function test_game_subcategories_render_correctly_on_topup_pages(): void
    {
        // 1. MLBB: Special Items, Weekly/Monthly Pack, Top Up
        $resMlbb = $this->get('/topup/mobile-legends');
        $resMlbb->assertStatus(200);
        $resMlbb->assertSee('Special Items');
        $resMlbb->assertSee('Weekly/Monthly Pack');
        $resMlbb->assertSee('Top Up');

        // 2. Magic Chess: Special Items, Top Up
        $resMc = $this->get('/topup/magic-chess-go-go');
        $resMc->assertStatus(200);
        $resMc->assertSee('Special Items');
        $resMc->assertSee('Top Up');

        // 3. Free Fire: Membership, Top Up
        $resFf = $this->get('/topup/free-fire');
        $resFf->assertStatus(200);
        $resFf->assertSee('Membership');
        $resFf->assertSee('Top Up');

        // 4. Roblox: Voucher Gift Card, Robux
        $resRblx = $this->get('/topup/roblox');
        $resRblx->assertStatus(200);
        $resRblx->assertSee('Voucher Gift Card');
        $resRblx->assertSee('Robux');
    }

    public function test_admin_transactions_date_filtering_and_latest_order(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();

        $response = $this->actingAs($admin)->get('/admin/transactions');
        $response->assertStatus(200);
        $response->assertSee('Laporan Transaksi Real-Time');
        $response->assertSee('Pintasan Periode:');
        $response->assertSee('Hari Ini');
        $response->assertSee('Semua Transaksi');

        // Test with date filter
        $resDate = $this->actingAs($admin)->get('/admin/transactions?date='.now()->toDateString());
        $resDate->assertStatus(200);
        $resDate->assertSee('Total Transaksi');
    }

    public function test_admin_can_store_and_destroy_product(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();
        $mlbb = Game::where('slug', 'mobile-legends')->first();

        // 1. Store new product
        $storeRes = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'MLBB 10000 Diamond Sultan Edition',
            'game_id' => $mlbb->id,
            'sub_category' => 'Special Items',
            'sku' => 'MLBB-10000-SULTAN',
            'cost_price' => 2000000,
            'selling_price' => 2250000,
            'badge' => 'EXCLUSIVE',
            'status' => 'active',
            'sort_order' => 99,
        ]);
        $storeRes->assertRedirect();

        $newProduct = Product::where('sku', 'MLBB-10000-SULTAN')->first();
        $this->assertNotNull($newProduct);
        $this->assertEquals('Special Items', $newProduct->sub_category);
        $this->assertEquals(250000, (int) $newProduct->profit);

        // 2. Destroy product
        $deleteRes = $this->actingAs($admin)->delete("/admin/products/{$newProduct->id}");
        $deleteRes->assertRedirect();
        $this->assertNull(Product::where('sku', 'MLBB-10000-SULTAN')->first());
    }

    public function test_admin_products_catalog_tabs_for_all_games(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();

        // Check tabs for each Digiflazz game/service
        foreach (['valorant', 'mobile-legends', 'free-fire', 'pubg-mobile', 'telkomsel', 'pln', 'xl', 'tri', 'smartfren'] as $gameSlug) {
            $response = $this->actingAs($admin)->get("/admin/products?tab={$gameSlug}");
            $response->assertStatus(200);
            $response->assertSee('Daftar Item &amp; Penyesuaian Harga Modal / Jual', false);
            $products = $response->viewData('products');
            $this->assertNotEmpty($products);
        }
    }

    public function test_digiflazz_sync_command_and_service(): void
    {
        $this->artisan('digiflazz:sync')
            ->assertExitCode(0);

        // Verify products have cost price from Digiflazz
        $mlbbProduct = Product::where('provider_sku', 'ml86')->orWhere('sku', 'ml86')->orWhere('buyer_sku_code', 'ml86')->orWhere('provider_sku', 'ML86')->first();
        $this->assertNotNull($mlbbProduct);
        $this->assertGreaterThan(0, (int) $mlbbProduct->cost_price);
        $this->assertGreaterThan((int) $mlbbProduct->cost_price, (int) $mlbbProduct->selling_price);
        $this->assertNotNull($mlbbProduct->last_synced_at);

        $plnProduct = Product::where('provider_sku', 'pln50')->orWhere('sku', 'pln50')->orWhere('buyer_sku_code', 'pln50')->orWhere('name', 'like', '%PLN 50%')->first();
        $this->assertNotNull($plnProduct);
        $this->assertGreaterThan(0, (int) $plnProduct->cost_price);

        $telkomselProduct = Product::where('provider_sku', 's10')->orWhere('sku', 's10')->orWhere('buyer_sku_code', 's10')->orWhere('name', 'like', '%Telkomsel 10%')->first();
        $this->assertNotNull($telkomselProduct);
        $this->assertGreaterThan(0, (int) $telkomselProduct->cost_price);
    }

    public function test_admin_can_trigger_digiflazz_sync_and_bulk_margin(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();

        $syncRes = $this->actingAs($admin)->post('/admin/products/sync-digiflazz');
        $syncRes->assertRedirect();
        $syncRes->assertSessionHas('success');

        $bulkMarginRes = $this->actingAs($admin)->post('/admin/products/bulk-margin', [
            'mode' => 'percent',
            'value' => 7,
        ]);
        $bulkMarginRes->assertRedirect();
        $bulkMarginRes->assertSessionHas('success');
    }

    public function test_admin_can_bulk_batch_update_products(): void
    {
        $admin = User::where('email', 'admin@vakstore.id')->first();
        $product1 = Product::first();
        $product2 = Product::skip(1)->first();

        $response = $this->actingAs($admin)->postJson('/admin/products/batch-update', [
            'products' => [
                [
                    'id' => $product1->id,
                    'name' => $product1->name,
                    'sub_category' => 'Diamond Update',
                    'cost_price' => $product1->cost_price,
                    'selling_price' => $product1->cost_price + 2000,
                    'badge' => 'HOT',
                    'status' => 'active',
                ],
                [
                    'id' => $product2->id,
                    'name' => $product2->name,
                    'sub_category' => $product2->sub_category,
                    'cost_price' => $product2->cost_price,
                    'selling_price' => $product2->cost_price + 3500,
                    'badge' => 'SPECIAL',
                    'status' => 'active',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'updated_count' => 2,
            ]);

        $this->assertEquals('HOT', $product1->fresh()->badge);
        $this->assertEquals('Diamond Update', $product1->fresh()->sub_category);
        $this->assertEquals('SPECIAL', $product2->fresh()->badge);
    }

    public function test_public_pages_do_not_contain_admin_or_login_buttons(): void
    {
        $urls = ['/', '/top-up-game', '/layanan-ppob', '/cek-transaksi', '/ppob/pln', '/ppob/pdam-nusantara'];

        foreach ($urls as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertDontSee('href="'.route('login').'"', false);
            $response->assertDontSee('href="'.route('register').'"', false);
            $response->assertDontSee('route(\'admin.dashboard\')', false);
            $response->assertDontSee('Akses Portal Super Admin');
            $response->assertDontSee('demo-login');
            $response->assertDontSee('Digiflazz', false);
        }
    }

    public function test_guest_visiting_admin_is_redirected_to_admin_login(): void
    {
        // 1. Direct /admin
        $resAdmin = $this->get('/admin');
        $resAdmin->assertRedirect(route('admin.login'));

        // 2. /admin/dashboard
        $resDash = $this->get('/admin/dashboard');
        $resDash->assertRedirect(route('admin.login'));

        // 3. /admin/products
        $resProd = $this->get('/admin/products');
        $resProd->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_login_via_dedicated_admin_login_portal(): void
    {
        // 1. View login form
        $resForm = $this->get('/admin/login');
        $resForm->assertStatus(200);
        $resForm->assertSee('Portal Akses Administrator');
        $resForm->assertSee('Masuk ke Control Center');

        // 2. Submit valid admin credentials
        $resLogin = $this->post('/admin/login', [
            'email' => 'admin@vakstore.id',
            'password' => 'password',
        ]);

        $resLogin->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isAdmin());
    }

    public function test_non_admin_user_is_denied_on_admin_login_portal(): void
    {
        $user = User::where('role', 'user')->first();

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_login_with_invalid_credentials(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@vakstore.id',
            'password' => 'wrongpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_logout_redirects_to_admin_login_page(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post('/admin/logout');
        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHas('success');
        $this->assertGuest();
    }

    public function test_authenticated_admin_visiting_admin_or_login_redirects_to_dashboard(): void
    {
        $admin = User::where('role', 'admin')->first();

        // 1. Visiting /admin when logged in
        $resAdmin = $this->actingAs($admin)->get('/admin');
        $resAdmin->assertRedirect(route('admin.dashboard'));

        // 2. Visiting /admin/login when already logged in
        $resLogin = $this->actingAs($admin)->get('/admin/login');
        $resLogin->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_non_admin_accessing_admin_dashboard_gets_forbidden(): void
    {
        $user = User::where('role', 'user')->first();

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_pure_bill_inquiry_requires_customer_number_and_region(): void
    {
        // 1. PDAM without region should fail with 422
        $resNoRegionPdam = $this->postJson('/api/inquiry-ppob', [
            'service_type' => 'pdam-nusantara',
            'customer_number' => '123456789',
        ]);
        $resNoRegionPdam->assertStatus(422);
        $resNoRegionPdam->assertJsonValidationErrors(['region']);

        // 2. PDAM without customer_number should fail with 422
        $resNoCustPdam = $this->postJson('/api/inquiry-ppob', [
            'service_type' => 'pdam-nusantara',
            'region' => 'pd32',
        ]);
        $resNoCustPdam->assertStatus(422);
        $resNoCustPdam->assertJsonValidationErrors(['customer_number']);

        // 3. Telkom without region/provider should fail with 422
        $resNoRegionTelkom = $this->postJson('/api/inquiry-ppob', [
            'service_type' => 'telkom-indihome',
            'customer_number' => '02198765432',
        ]);
        $resNoRegionTelkom->assertStatus(422);
        $resNoRegionTelkom->assertJsonValidationErrors(['region']);
    }

    public function test_pure_bill_inquiry_with_valid_buyer_selected_sku_returns_expected_data(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'ref_id' => 'INQ-TEST-001',
                    'customer_no' => '123456789',
                    'buyer_sku_code' => 'pd32',
                    'customer_name' => 'SUGENG RAHAYU',
                    'admin' => 2500,
                    'message' => 'INQUIRY SUKSES',
                    'status' => 'Sukses',
                    'rc' => '00',
                    'selling_price' => 75000,
                    'desc' => [
                        'lembar_tagihan' => [
                            ['periode' => '2026-09', 'nilai_tagihan' => 75000, 'admin' => 2500],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/inquiry-ppob', [
            'service_type' => 'pdam-nusantara',
            'customer_number' => '123456789',
            'region' => 'pd32',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'target' => '123456789',
            'target_secondary' => 'pd32',
            'customer_name' => 'SUGENG RAHAYU',
            'bill_amount' => 75000,
        ]);
    }

    public function test_pure_bill_inquiry_returns_transparent_provider_error_on_failure(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'ref_id' => 'INQ-TEST-002',
                    'customer_no' => '999999999',
                    'buyer_sku_code' => 'pd32',
                    'message' => 'Nomor Pelanggan Salah / Tidak Terdaftar',
                    'status' => 'Gagal',
                    'rc' => '42',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/inquiry-ppob', [
            'service_type' => 'pdam-nusantara',
            'customer_number' => '999999999',
            'region' => 'pd32',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Nomor Pelanggan Salah / Tidak Terdaftar',
        ]);
        // Must NOT return dummy data or success
        $this->assertNull($response->json('bill_amount'));
    }

    public function test_postpaid_checkout_requires_checked_bill_amount(): void
    {
        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();

        // Attempting checkout with bill_amount = 0 should fail
        $response = $this->postJson('/layanan-ppob/checkout', [
            'product_id' => $pdamProduct->id,
            'target' => '123456789',
            'payment_method' => 'qris',
            'bill_amount' => 0,
            'customer_phone' => '081234567890',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Silakan lakukan Cek Tagihan terlebih dahulu untuk mendapatkan rincian pembayaran resmi.',
        ]);
    }

    public function test_pdam_page_renders_in_strict_order_and_has_no_default_values(): void
    {
        $response = $this->get('/ppob/pdam-nusantara');
        $response->assertStatus(200);

        // Verify Step 1: ID Pelanggan
        $response->assertSee('Masukkan ID Pelanggan');
        // Verify Step 2: Pilih Wilayah
        $response->assertSee('Pilih Wilayah PDAM');
        // Verify Step 3: Cek Rincian Tagihan
        $response->assertSee('Cek Rincian Tagihan');
        $response->assertSee('Cek Tagihan Sekarang');

        // Verify inputs are empty by default
        $response->assertSee('id="input-target"', false);
        $response->assertSee('id="biller-combobox-input"', false);
        $response->assertSee('id="customer-phone"', false);
        $response->assertSee('id="customer-email"', false);
    }

    public function test_database_mapping_pdam_sumenep_maps_to_pd32(): void
    {
        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();
        $this->assertNotNull($pdamProduct);

        $sumenepOption = PpobServiceOption::where('product_id', $pdamProduct->id)
            ->where('name', 'PDAM Kabupaten Sumenep')
            ->first();

        $this->assertNotNull($sumenepOption, 'Option PDAM Kabupaten Sumenep must exist in database');
        $this->assertEquals('pd32', $sumenepOption->buyer_sku_code);
        $this->assertEquals('active', $sumenepOption->status);
    }

    public function test_inquiry_with_invalid_or_nonexistent_service_option_id_returns_422(): void
    {
        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();

        $response = $this->postJson('/api/inquiry-ppob', [
            'product_id' => $pdamProduct->id,
            'service_option_id' => 999999,
            'customer_number' => '123456789',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Opsi layanan tidak ditemukan atau tidak sesuai dengan produk yang dipilih.',
        ]);
    }

    public function test_inquiry_with_mismatched_product_and_service_option_returns_422(): void
    {
        $telkomProduct = Product::where('sku', 'TELKOM-BILL')->first();
        $pdamOption = PpobServiceOption::where('buyer_sku_code', 'pd32')->first();

        $response = $this->postJson('/api/inquiry-ppob', [
            'product_id' => $telkomProduct->id,
            'service_option_id' => $pdamOption->id, // Sending PDAM option for Telkom product!
            'customer_number' => '123456789',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Opsi layanan tidak ditemukan atau tidak sesuai dengan produk yang dipilih.',
        ]);
    }

    public function test_inquiry_with_empty_customer_number_returns_422(): void
    {
        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();
        $pdamOption = PpobServiceOption::where('buyer_sku_code', 'pd32')->first();

        $response = $this->postJson('/api/inquiry-ppob', [
            'product_id' => $pdamProduct->id,
            'service_option_id' => $pdamOption->id,
            'customer_number' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_number']);
    }

    public function test_inquiry_without_sku_or_option_returns_422(): void
    {
        $response = $this->postJson('/api/inquiry-ppob', [
            'customer_number' => '123456789',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['region']);
    }

    public function test_inquiry_with_real_digiflazz_error_returns_transparent_system_error_and_no_simulation(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'message' => 'Test case untuk produk pd32 tidak ditemukan',
            ], 400),
        ]);

        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();
        $sumenepOption = PpobServiceOption::where('buyer_sku_code', 'pd32')->first();

        $response = $this->postJson('/api/inquiry-ppob', [
            'product_id' => $pdamProduct->id,
            'service_option_id' => $sumenepOption->id,
            'customer_number' => '0203763',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Test case untuk produk pd32 tidak ditemukan',
        ]);

        // CRITICAL CHECK: Ensure NO simulation data leak (Section 32)
        $content = json_encode($response->json());
        $this->assertStringNotContainsString('BUDI SANTOSO', $content);
        $this->assertStringNotContainsString('SITI AMINAH', $content);
        $this->assertNull($response->json('bill_amount'));
    }

    public function test_inquiry_with_digiflazz_success_displays_real_provider_data(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'ref_id' => 'INQ-TEST-SUMENEP-001',
                    'customer_no' => '0203763',
                    'buyer_sku_code' => 'pd32',
                    'customer_name' => 'ACHMAD ROFIQI',
                    'admin' => 2000,
                    'message' => 'INQUIRY SUKSES',
                    'status' => 'Sukses',
                    'rc' => '00',
                    'selling_price' => 84200,
                    'desc' => [
                        'lembar_tagihan' => [
                            ['periode' => 'September 2026', 'nilai_tagihan' => 84200, 'admin' => 2000],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();
        $sumenepOption = PpobServiceOption::where('buyer_sku_code', 'pd32')->first();

        $response = $this->postJson('/api/inquiry-ppob', [
            'product_id' => $pdamProduct->id,
            'service_option_id' => $sumenepOption->id,
            'customer_number' => '0203763',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'customer_name' => 'ACHMAD ROFIQI',
            'bill_amount' => 84200,
            'admin_fee' => 2000,
        ]);
    }

    public function test_checkout_and_purchase_resolves_sku_strictly_from_database_mapping(): void
    {
        $pdamProduct = Product::where('sku', 'PDAM-BILL')->first();
        $sumenepOption = PpobServiceOption::where('buyer_sku_code', 'pd32')->first();

        $billAmount = 84200;
        $adminFee = (float) $pdamProduct->selling_price; // 2500

        // 1. Checkout with service_option_id
        $response = $this->post('/layanan-ppob/checkout', [
            'product_id' => $pdamProduct->id,
            'service_option_id' => $sumenepOption->id,
            'target' => '0203763',
            'nickname' => 'ACHMAD ROFIQI - PDAM SUMENEP',
            'payment_method' => 'qris',
            'bill_amount' => $billAmount,
            'customer_name' => 'Achmad Rofiqi',
            'customer_phone' => '081298765432',
        ]);

        $response->assertRedirect();

        $transaction = Transaction::where('product_id', $pdamProduct->id)->latest()->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($sumenepOption->id, $transaction->ppob_service_option_id);

        // 2. Fake Digiflazz Payment response
        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => function (Request $request) {
                $payload = json_decode($request->body(), true);
                // CRITICAL CHECK: buyer_sku_code sent to Digiflazz MUST BE pd32 from the database mapping!
                if ($payload['buyer_sku_code'] === 'pd32' && $payload['customer_no'] === '0203763') {
                    return Http::response([
                        'data' => [
                            'ref_id' => $payload['ref_id'],
                            'customer_no' => '0203763',
                            'buyer_sku_code' => 'pd32',
                            'sn' => 'SN-PDAM-REAL-998877',
                            'status' => 'Sukses',
                            'rc' => '00',
                            'message' => 'Pembayaran tagihan sukses',
                        ],
                    ], 200);
                }

                return Http::response(['message' => 'Invalid request SKU'], 400);
            },
        ]);

        // 3. Simulate payment on invoice
        $payRes = $this->post(route('invoice.simulate-pay', $transaction->invoice_number));
        $payRes->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('paid', $transaction->payment_status);
        $this->assertEquals('success', $transaction->transaction_status);
        $this->assertEquals('SN-PDAM-REAL-998877', $transaction->serial_number);
    }
}
