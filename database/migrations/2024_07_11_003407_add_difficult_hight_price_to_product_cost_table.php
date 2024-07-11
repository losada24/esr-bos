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
        Schema::table('product_costs', function (Blueprint $table) {
            $table->decimal('difficult_hight_price')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_cost', function (Blueprint $table) {
            $table->dropColumn('difficult_hight_price');
        });
    }
};
