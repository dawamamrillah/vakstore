<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Product;
use App\Models\ProductSyncChange;
use App\Models\User;
use App\Support\ErrorSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSyncChangeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Category $category;

    protected Game $game;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@vakstore.id',
            'role' => 'admin',
        ]);

        $this->category = Category::create([
            'name' => 'Games',
            'slug' => 'games',
            'type' => 'game',
            'status' => 'active',
        ]);

        $this->game = Game::create([
            'category_id' => $this->category->id,
            'name' => 'Mobile Legends',
            'slug' => 'mobile-legends',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'game_id' => $this->game->id,
            'sku' => 'MLBB-86',
            'provider_sku' => 'ML86',
            'name' => '86 Diamonds',
            'cost_price' => 20000,
            'selling_price' => 22000,
            'profit' => 2000,
            'status' => 'active',
        ]);
    }

    public function test_error_sanitizer_removes_digiflazz_and_extracts_clean_message(): void
    {
        // 1. Replaces digiflazz with Provider
        $raw1 = 'Koneksi ke Digiflazz timeout';
        $this->assertEquals('Koneksi ke Provider timeout', ErrorSanitizer::sanitize($raw1));

        // 2. Extracts message from JSON string
        $rawJson = json_encode(['message' => 'Saldo digiflazz tidak mencukupi']);
        $this->assertEquals('Saldo Provider tidak mencukupi', ErrorSanitizer::sanitize($rawJson));

        // 3. Cleans SQL error
        $rawSql = 'SQLSTATE[HY000] [2002] Connection refused (Connection: mysql, SQL: select * from users)';
        $cleanedSql = ErrorSanitizer::sanitize($rawSql);
        $this->assertStringNotContainsString('SQLSTATE', $cleanedSql);
        $this->assertStringNotContainsString('select * from users', $cleanedSql);

        // 4. Default fallback on empty/null
        $this->assertEquals('Terjadi kendala pada sistem. Silakan coba beberapa saat lagi.', ErrorSanitizer::sanitize(''));
    }

    public function test_can_record_and_scope_product_sync_changes(): void
    {
        $change = ProductSyncChange::create([
            'product_id' => $this->product->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'change_type' => 'price_changed',
            'sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'old_cost_price' => 19000,
            'new_cost_price' => 20000,
            'old_selling_price' => 21000,
            'new_selling_price' => 22000,
            'is_reviewed' => false,
        ]);

        $this->assertCount(1, ProductSyncChange::unreviewed()->get());
        $this->assertCount(1, ProductSyncChange::forGame($this->game->id)->get());
        $this->assertCount(1, ProductSyncChange::forCategory($this->category->id)->get());
    }

    public function test_admin_can_review_and_update_price_from_sync_change(): void
    {
        $change = ProductSyncChange::create([
            'product_id' => $this->product->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'change_type' => 'price_changed',
            'sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'old_cost_price' => 19000,
            'new_cost_price' => 20000,
            'old_selling_price' => 21000,
            'new_selling_price' => 22000,
            'is_reviewed' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.products.changes.review', $change->id), [
                'selling_price' => 23500,
            ]);

        $response->assertSessionHas('success');

        // Verify change is marked reviewed
        $this->assertTrue($change->fresh()->is_reviewed);
        $this->assertNotNull($change->fresh()->reviewed_at);

        // Verify product price and profit updated
        $updatedProduct = $this->product->fresh();
        $this->assertEquals(23500, $updatedProduct->selling_price);
        $this->assertEquals(3500, $updatedProduct->profit);
    }

    public function test_admin_can_mark_all_changes_reviewed_for_category(): void
    {
        ProductSyncChange::create([
            'product_id' => $this->product->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'change_type' => 'new_product',
            'sku' => 'MLBB-NEW-1',
            'product_name' => 'New Product 1',
            'new_cost_price' => 10000,
            'new_selling_price' => 11500,
            'is_reviewed' => false,
        ]);

        ProductSyncChange::create([
            'product_id' => $this->product->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'change_type' => 'status_changed',
            'sku' => 'MLBB-NEW-2',
            'product_name' => 'New Product 2',
            'new_cost_price' => 10000,
            'new_selling_price' => 11500,
            'is_reviewed' => false,
        ]);

        $this->assertEquals(2, ProductSyncChange::unreviewed()->count());

        $response = $this->actingAs($this->admin)
            ->post(route('admin.products.changes.mark-all-reviewed'), [
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, ProductSyncChange::unreviewed()->count());
    }

    public function test_admin_products_page_renders_without_mentioning_digiflazz(): void
    {
        ProductSyncChange::create([
            'product_id' => $this->product->id,
            'game_id' => $this->game->id,
            'category_id' => $this->category->id,
            'change_type' => 'price_changed',
            'sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'old_cost_price' => 19000,
            'new_cost_price' => 20000,
            'old_selling_price' => 21000,
            'new_selling_price' => 22000,
            'is_reviewed' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.products'));

        $response->assertOk();
        $content = $response->getContent();

        $response->assertSee('Pusat Perubahan Data');
        $response->assertDontSee('Tarik Data Digiflazz');
        $response->assertDontSee('Sinkronisasi Digiflazz');
        $response->assertDontSee('Digiflazz API');
    }
}
