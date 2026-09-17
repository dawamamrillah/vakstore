<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'user_id',
        'product_id',
        'ppob_service_option_id',
        'provider_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'target',
        'target_secondary',
        'nickname',
        'cost_price',
        'selling_price',
        'discount',
        'admin_fee',
        'total',
        'profit',
        'voucher_code',
        'payment_status', // pending, paid, failed, expired, refunded
        'transaction_status', // pending, processing, success, failed, refunded
        'provider_reference',
        'serial_number',
        'failure_reason',
        'idempotency_key',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ppobServiceOption()
    {
        return $this->belongsTo(PpobServiceOption::class, 'ppob_service_option_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function digiflazzTransactions()
    {
        return $this->hasMany(DigiflazzTransaction::class);
    }

    public function latestDigiflazzTransaction()
    {
        return $this->hasOne(DigiflazzTransaction::class)->latestOfMany();
    }

    public function isSuccess(): bool
    {
        return $this->transaction_status === 'success';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
