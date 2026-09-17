<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'publisher',
        'description',
        'image',
        'banner_image',
        'target_field_name',
        'target_secondary_field_name',
        'has_secondary_target',
        'target_placeholder',
        'target_secondary_placeholder',
        'status',
    ];

    protected $casts = [
        'has_secondary_target' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
