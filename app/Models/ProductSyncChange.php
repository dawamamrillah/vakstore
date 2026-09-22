<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSyncChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'game_id',
        'category_id',
        'change_type',
        'old_cost_price',
        'new_cost_price',
        'old_selling_price',
        'new_selling_price',
        'old_status',
        'new_status',
        'is_reviewed',
        'reviewed_at',
    ];

    protected $casts = [
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'old_selling_price' => 'decimal:2',
        'new_selling_price' => 'decimal:2',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeUnreviewed($query)
    {
        return $query->where('is_reviewed', false);
    }

    public function scopeForGame($query, $gameId)
    {
        return $query->where('game_id', $gameId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
