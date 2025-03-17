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
    Schema::table('products', function (Blueprint $table) {
        $table->json('images')->nullable()->change(); // Make images nullable
    });
}

public function down()
{
    Schema::table('products', function (Blueprint $table) {
        $table->json('images')->nullable(false)->change(); // Revert to not-null if needed
    });
}

};
