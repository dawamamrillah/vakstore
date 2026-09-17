<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'base_url',
        'api_key',
        'api_secret',
        'status',
    ];

    protected $hidden = [
        'api_secret',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getMaskedApiKeyAttribute(): string
    {
        if (! $this->api_key) {
            return '••••••••••••';
        }
        $len = strlen($this->api_key);
        if ($len <= 6) {
            return '••••'.substr($this->api_key, -2);
        }

        return '••••••••'.substr($this->api_key, -4);
    }
}
