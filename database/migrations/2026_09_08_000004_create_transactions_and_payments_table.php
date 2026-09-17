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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();

            // Customer & Target Info
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('target'); // User ID / No HP / No Meter
            $table->string('target_secondary')->nullable(); // Zone ID / Wilayah
            $table->string('nickname')->nullable(); // Player/Customer Nickname

            // Pricing Snapshots
            $table->decimal('cost_price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('profit', 15, 2);
            $table->string('voucher_code')->nullable();

            // Statuses
            $table->string('payment_status')->default('pending'); // pending, paid, failed, expired, refunded
            $table->string('transaction_status')->default('pending'); // pending, processing, success, failed, refunded

            // Provider & Delivery
            $table->string('provider_reference')->nullable();
            $table->text('serial_number')->nullable(); // SN Diamond / Token Listrik PLN
            $table->text('failure_reason')->nullable();
            $table->string('idempotency_key')->nullable()->unique();

            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method'); // wallet, qris, bca_va, bri_va, mandiri_va, bni_va, dana, ovo, gopay
            $table->string('payment_reference')->nullable()->index();
            $table->decimal('amount', 15, 2);
            $table->text('qr_string')->nullable();
            $table->string('pay_code')->nullable();
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('transactions');
    }
};
