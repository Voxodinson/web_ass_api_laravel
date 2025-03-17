<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tracking_number',
        'shipping_address',
        'shipping_date'
    ];

    // Relationship with Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
