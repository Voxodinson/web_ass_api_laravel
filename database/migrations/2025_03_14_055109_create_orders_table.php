<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payment_method');  // e.g., Credit Card, PayPal
            $table->string('payment_status')->default('pending');  // Payment status (e.g., pending, completed)
            $table->string('transaction_id')->nullable();  // Transaction ID from payment provider
            $table->decimal('total_amount', 10, 2);  // Total order amount
            $table->string('shipping_address');  // Shipping address
            $table->string('shipping_city');
            $table->string('shipping_zip');
            $table->string('shipping_country');
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
