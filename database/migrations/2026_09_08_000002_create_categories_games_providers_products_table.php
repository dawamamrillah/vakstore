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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('game'); // game, ppob
            $table->string('icon')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('publisher')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('target_field_name')->default('User ID');
            $table->string('target_secondary_field_name')->nullable()->default('Zone ID');
            $table->boolean('has_secondary_target')->default(true);
            $table->string('target_placeholder')->default('Contoh: 12849102');
            $table->string('target_secondary_placeholder')->nullable()->default('(2314)');
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('provider_sku')->nullable();
            $table->text('description')->nullable();
            $table->decimal('cost_price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->decimal('profit', 15, 2);
            $table->string('badge')->nullable(); // FLASH SALE, POPULAR, BEST SELLER
            $table->string('icon_type')->default('diamond'); // diamond, token, credit, etc
            $table->string('sub_category')->nullable(); // Diamond, Pass / Bundle, PLN 20rb, dll
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('games');
        Schema::dropIfExists('categories');
    }
};
