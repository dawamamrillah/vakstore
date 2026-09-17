<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'buyer_sku_code')) {
                $table->string('buyer_sku_code')->nullable()->index()->after('provider_sku');
            }
            if (! Schema::hasColumn('products', 'seller_name')) {
                $table->string('seller_name')->nullable()->after('brand_name');
            }
            if (! Schema::hasColumn('products', 'buyer_product_status')) {
                $table->boolean('buyer_product_status')->default(true)->after('status');
            }
            if (! Schema::hasColumn('products', 'seller_product_status')) {
                $table->boolean('seller_product_status')->default(true)->after('buyer_product_status');
            }
            if (! Schema::hasColumn('products', 'customer_fields')) {
                $table->json('customer_fields')->nullable()->after('seller_product_status');
            }
            if (! Schema::hasColumn('products', 'metadata')) {
                $table->json('metadata')->nullable()->after('customer_fields');
            }
        });

        if (! Schema::hasTable('digiflazz_transactions')) {
            Schema::create('digiflazz_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
                $table->string('invoice_number')->nullable()->index();
                $table->string('buyer_sku_code');
                $table->string('customer_no');
                $table->string('ref_id')->unique()->index();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->json('callback_payload')->nullable();
                $table->string('supplier_status')->nullable(); // Sukses, Pending, Gagal
                $table->string('rc')->nullable();
                $table->text('message')->nullable();
                $table->text('serial_number')->nullable();
                $table->timestamp('request_sent_at')->nullable();
                $table->timestamp('callback_received_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digiflazz_transactions');

        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['buyer_sku_code', 'seller_name', 'buyer_product_status', 'seller_product_status', 'customer_fields', 'metadata'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
