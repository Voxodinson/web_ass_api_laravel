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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();  // Auto-incrementing primary key
            $table->foreignId('order_id')->constrained()->onDelete('cascade');  // Foreign key to orders table
            $table->integer('product_id');  // Assuming product_id is an integer
            $table->integer('quantity');
            $table->string('color');
            $table->string('size');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
