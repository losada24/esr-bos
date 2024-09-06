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
        Schema::table('order_products_extra_works', function (Blueprint $table) {
            $table->dropColumn('number_of_sides');
            $table->integer('amount')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_products_extra_works', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->integer('number_of_sides')->default(0)->nullable();
        });
    }
};
