<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type', // game, ppob
        'icon',
        'status',
    ];

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
