<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'website',
        'description',
        'store_locations', // Add 'store_locations' to the fillable array
    ];

    // Cast the 'phone' field to an array
    protected $casts = [
        'phone' => 'array', // Cast phone as an array
    ];
}