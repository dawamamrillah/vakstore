<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigiflazzTransaction extends Model
{
    use HasFactory;

    protected $table = 'digiflazz_transactions';

    protected $fillable = [
        'transaction_id',
        'invoice_number',
        'buyer_sku_code',
        'customer_no',
        'ref_id',
        'request_payload',
        'response_payload',
        'callback_payload',
        'supplier_status',
        'rc',
        'message',
        'serial_number',
        'request_sent_at',
        'callback_received_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'callback_payload' => 'array',
        'request_sent_at' => 'datetime',
        'callback_received_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
