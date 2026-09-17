<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'game_id',
        'provider_id',
        'name',
        'sku',
        'provider_sku',
        'buyer_sku_code',
        'brand_name',
        'seller_name',
        'raw_type',
        'description',
        'cost_price',
        'selling_price',
        'profit',
        'badge',
        'icon_type',
        'sub_category',
        'sort_order',
        'status',
        'buyer_product_status',
        'seller_product_status',
        'customer_fields',
        'metadata',
        'last_synced_at',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'sort_order' => 'integer',
        'buyer_product_status' => 'boolean',
        'seller_product_status' => 'boolean',
        'customer_fields' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function serviceOptions()
    {
        return $this->hasMany(PpobServiceOption::class);
    }

    public function getMarginPercentageAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return round(($this->profit / $this->cost_price) * 100, 1);
    }
}
