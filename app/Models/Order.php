<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method',
        'payment_status',
        'transaction_id',
        'total_amount',
        'shipping_address',
        'shipping_city',
        'shipping_zip',
        'shipping_country',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
