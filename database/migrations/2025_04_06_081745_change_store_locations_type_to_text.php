<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
       * Run the migrations.
    */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('store_locations')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // If you need to revert, change it back to json (if that was the original intent)
            // $table->json('store_locations')->nullable()->change();
        });
    }
};
