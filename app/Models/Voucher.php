<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type', // percentage, fixed
        'value',
        'minimum_transaction',
        'maximum_discount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'category_id',
        'game_id',
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_transaction' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_per_user' => 'integer',
        'used_count' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function isValidForAmount(float $amount): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->start_at && now()->lt($this->start_at)) {
            return false;
        }
        if ($this->end_at && now()->gt($this->end_at)) {
            return false;
        }
        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) {
            return false;
        }
        if ($amount < (float) $this->minimum_transaction) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if (! $this->isValidForAmount($amount)) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $discount = ($this->value / 100) * $amount;
            if ($this->maximum_discount && $discount > (float) $this->maximum_discount) {
                $discount = (float) $this->maximum_discount;
            }

            return round($discount, 2);
        }

        return min((float) $this->value, $amount);
    }
}
