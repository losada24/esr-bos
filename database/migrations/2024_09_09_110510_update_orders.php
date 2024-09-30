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
    { Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('equipment_rental_price');
        $table->decimal('cost_city_fee', 10, 2)->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products_extra_works', function (Blueprint $table) {
            $table->dropColumn('cost_city_fee');
            $table->decimal('equipment_rental_price')->nullable();
        });
    }
};
