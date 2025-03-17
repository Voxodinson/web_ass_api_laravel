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
            $table->id();  // Auto-incrementing primary key
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Foreign key to the users table
            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('transaction_id');
            $table->decimal('total_amount', 10, 2);
            $table->string('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_zip');
            $table->string('shipping_country');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};