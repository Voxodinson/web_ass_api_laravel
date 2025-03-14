<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'price', 'stock', 'sizes', 'color', 'brand', 'category', 'images', 'rating'
    ];

    protected $casts = [
        'sizes' => 'array',
        'images' => 'array',
        'rating' => 'float',
    ];
}
