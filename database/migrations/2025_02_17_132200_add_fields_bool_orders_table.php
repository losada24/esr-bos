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
        $table->boolean('pre_inspection')->nullable()->default(false);
        $table->boolean('inspection')->nullable()->default(false);
        $table->boolean('walk_trough')->nullable()->default(false);
        $table->boolean('partial_payment_installation')->nullable()->default(false);
        $table->boolean('final_payment_installation')->nullable()->default(false);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn('pre_inspection');
        $table->dropColumn('inspection');
        $table->dropColumn('walk_trough');
        $table->dropColumn('partial_payment_installation');
        $table->dropColumn('final_payment_installation');
    });
    }
};
