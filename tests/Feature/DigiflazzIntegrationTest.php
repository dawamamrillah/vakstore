<?php

namespace Tests\Feature;

use App\Models\DigiflazzTransaction;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Digiflazz\DigiflazzClient;
use App\Services\Digiflazz\DigiflazzInquiryService;
use App\Services\Digiflazz\DigiflazzProductService;
use App\Services\Digiflazz\DigiflazzTransactionService;
use App\Services\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DigiflazzIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_digiflazz_client_generates_correct_md5_signature(): void
    {
        config([
            'digiflazz.username' => 'testuser',
            'digiflazz.development_key' => 'devkey123',
        ]);

        $client = new DigiflazzClient;
        $expectedSignature = md5('testuser'.'devkey123'.'depo');

        $this->assertEquals($expectedSignature, $client->generateSignature('depo'));
    }

    public function test_digiflazz_test_command_executes_successfully(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/cek-saldo' => Http::response([
                'data' => [
                    'deposit' => 500000,
                ],
            ], 200),
        ]);

        $this->artisan('digiflazz:test')
            ->expectsOutputToContain('Menguji koneksi ke Digiflazz API')
            ->assertExitCode(0);
    }

    public function test_digiflazz_product_sync_upserts_products_without_duplicates(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/price-list' => Http::response([
                'data' => [
                    [
                        'buyer_sku_code' => 'ML86',
                        'product_name' => '86 Diamonds Mobile Legends',
                        'category' => 'Games',
                        'brand' => 'MOBILE LEGENDS',
                        'seller_name' => 'Digiflazz',
                        'price' => 19000,
                        'buyer_product_status' => true,
                        'seller_product_status' => true,
                        'unlimited_stock' => true,
                        'stock' => 9999,
                        'type' => 'prepaid',
                    ],
                    [
                        'buyer_sku_code' => 'PLN20',
                        'product_name' => 'Token PLN 20.000',
                        'category' => 'PLN',
                        'brand' => 'PLN',
                        'seller_name' => 'Digiflazz',
                        'price' => 20100,
                        'buyer_product_status' => true,
                        'seller_product_status' => true,
                        'unlimited_stock' => true,
                        'stock' => 9999,
                        'type' => 'prepaid',
                    ],
                ],
            ], 200),
        ]);

        $productService = app(DigiflazzProductService::class);
        $result = $productService->sync('prepaid');

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['total_prepaid']);

        // Assert products table has these items
        $this->assertDatabaseHas('products', [
            'buyer_sku_code' => 'ML86',
            'cost_price' => 19000,
        ]);

        $this->assertDatabaseHas('products', [
            'buyer_sku_code' => 'PLN20',
            'cost_price' => 20100,
        ]);

        // Run sync a second time to ensure no duplicates
        $secondResult = $productService->sync('prepaid');
        $this->assertEquals(2, $secondResult['total_prepaid']);
        $this->assertEquals(0, $secondResult['created']);
    }

    public function test_order_creation_snapshots_price_and_margin(): void
    {
        $product = Product::where('status', 'active')->first();
        $this->assertNotNull($product);

        $transactionService = app(TransactionService::class);
        $order = $transactionService->createOrder([
            'product_id' => $product->id,
            'target' => '12345678',
            'target_secondary' => '1234',
            'customer_name' => 'Test Gamer',
            'customer_phone' => '081234567890',
            'payment_method' => 'qris',
        ]);

        $this->assertNotNull($order);
        $this->assertEquals($product->cost_price, $order->cost_price);
        $this->assertEquals($product->selling_price, $order->selling_price);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals('pending', $order->transaction_status);
    }

    public function test_prepaid_transaction_service_processes_and_records_audit(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'ref_id' => 'INV-TEST-001',
                    'buyer_sku_code' => 'ML86',
                    'customer_no' => '123456781234',
                    'status' => 'Sukses',
                    'rc' => '00',
                    'sn' => 'SN-DIAMOND-998877',
                    'message' => 'Transaksi sukses',
                ],
            ], 200),
        ]);

        $product = Product::where('status', 'active')->first();
        $transaction = Transaction::create([
            'invoice_number' => 'INV-TEST-001',
            'product_id' => $product->id,
            'target' => '12345678',
            'cost_price' => 15000,
            'selling_price' => 17000,
            'total' => 17000,
            'profit' => 2000,
            'payment_status' => 'paid',
            'transaction_status' => 'processing',
        ]);

        $trxService = app(DigiflazzTransactionService::class);
        $result = $trxService->processPrepaidTransaction($transaction, 'ML86', '123456781234', 'INV-TEST-001');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('SN-DIAMOND-998877', $result['serial_number']);

        // Assert audit record in digiflazz_transactions
        $this->assertDatabaseHas('digiflazz_transactions', [
            'ref_id' => 'INV-TEST-001',
            'supplier_status' => 'Sukses',
            'rc' => '00',
            'serial_number' => 'SN-DIAMOND-998877',
        ]);

        // Assert Transaction model updated
        $this->assertEquals('success', $transaction->fresh()->transaction_status);
        $this->assertEquals('SN-DIAMOND-998877', $transaction->fresh()->serial_number);
    }

    public function test_postpaid_inquiry_service_returns_bill_details(): void
    {
        Http::fake([
            'https://api.digiflazz.com/v1/transaction' => Http::response([
                'data' => [
                    'ref_id' => 'INQ-PLN-01',
                    'customer_no' => '512345678901',
                    'customer_name' => 'BUDI SANTOSO',
                    'selling_price' => 164500,
                    'admin' => 2500,
                    'status' => 'Sukses',
                    'rc' => '00',
                    'desc' => [
                        'lembar_tagihan' => [
                            ['periode' => 'September 2026', 'nilai_tagihan' => 164500],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $inqService = app(DigiflazzInquiryService::class);
        $result = $inqService->inquiry('plnpas1', '512345678901', 'INQ-PLN-01');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('BUDI SANTOSO', $result['customer_name']);
        $this->assertEquals(164500, $result['bill_amount']);
    }

    public function test_digiflazz_webhook_callback_processes_and_is_idempotent(): void
    {
        $product = Product::where('status', 'active')->first();
        $transaction = Transaction::create([
            'invoice_number' => 'INV-WEBHOOK-01',
            'product_id' => $product->id,
            'target' => '081234567890',
            'cost_price' => 10000,
            'selling_price' => 12000,
            'total' => 12000,
            'profit' => 2000,
            'payment_status' => 'paid',
            'transaction_status' => 'processing',
        ]);

        $dfTrx = DigiflazzTransaction::create([
            'transaction_id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number,
            'buyer_sku_code' => 'TP10',
            'customer_no' => '081234567890',
            'ref_id' => 'INV-WEBHOOK-01',
            'supplier_status' => 'Pending',
        ]);

        $callbackPayload = [
            'data' => [
                'ref_id' => 'INV-WEBHOOK-01',
                'customer_no' => '081234567890',
                'buyer_sku_code' => 'TP10',
                'status' => 'Sukses',
                'rc' => '00',
                'sn' => 'SN-CALLBACK-TOKEN-1234',
                'message' => 'Transaksi Sukses',
            ],
        ];

        // 1st Webhook hit
        $response1 = $this->postJson('/api/webhook/digiflazz', $callbackPayload);
        $response1->assertStatus(200);
        $response1->assertJson(['status' => 'success']);

        $this->assertEquals('success', $transaction->fresh()->transaction_status);
        $this->assertEquals('SN-CALLBACK-TOKEN-1234', $transaction->fresh()->serial_number);

        // 2nd Duplicate Webhook hit (Idempotency test)
        $response2 = $this->postJson('/api/webhook/digiflazz', $callbackPayload);
        $response2->assertStatus(200);
        $response2->assertJson(['status' => 'success']);

        // Ensure status remains success and no duplicate rows created
        $this->assertEquals(1, DigiflazzTransaction::where('ref_id', 'INV-WEBHOOK-01')->count());
        $this->assertEquals('success', $transaction->fresh()->transaction_status);
    }
}
