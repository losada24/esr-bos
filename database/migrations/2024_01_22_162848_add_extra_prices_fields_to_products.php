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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('dealer_unit_price', 10, 2);
            $table->decimal('dealer_total_price', 10, 2);
            $table->decimal('sub_dealer_unit_price', 10, 2);
            $table->decimal('sub_dealer_total_price', 10, 2);
            $table->decimal('customer_unit_price', 10, 2);
            $table->decimal('customer_total_price', 10, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'dealer_unit_price',
                'dealer_total_price',
                'sub_dealer_unit_price',
                'sub_dealer_total_price',
                'customer_unit_price',
                'customer_total_price',
            ]);
        });
    }
};
