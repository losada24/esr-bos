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
        Schema::table('order_products', function (Blueprint $table) {
          $table->decimal('unit_price_with_extraworks', 10, 2)->nullable();
          $table->decimal('total_price_with_extraworks', 10, 2)->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
          $table->dropColumn('unit_price_with_extraworks');
          $table->dropColumn('total_price_with_extraworks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('unit_price_with_extraworks');
            $table->dropColumn('total_price_with_extraworks');
        });

        Schema::table('orders', function (Blueprint $table) {
          $table->decimal('unit_price_with_extraworks', 10, 2)->nullable();
          $table->decimal('total_price_with_extraworks', 10, 2)->nullable();
      });
    }
};
