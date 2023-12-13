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
        Schema::table('orders', function (Blueprint $table) {
          $table->decimal('tax_amount', 10, 2)->default(0.00);
          $table->float('tax_rate')->default(0.00);
          $table->decimal('installation', 10, 2)->default(0.00);
          $table->decimal('permit', 10, 2)->default(0.00);
          $table->decimal('other', 10, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
              'tax_amount',
              'tax_rate',
              'installation',
              'permit',
              'other',
            ]);
        });
    }
};
