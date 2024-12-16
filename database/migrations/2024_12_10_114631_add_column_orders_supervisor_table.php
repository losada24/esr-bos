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
        $table->string('supervisor_payment_status')->nullable();
        $table->date('supervisor_payment_date')->nullable();
        $table->integer('execution_planing_date')->default(0)->nullable();
        $table->decimal('supervisor_commissions', 10, 2)->default(0)->nullable();
        $table->decimal('supervisor_payment_percentage', 10, 2)->default(0)->nullable();

    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('supervisor_payment_status');
            $table->dropColumn('supervisor_payment_date');
            $table->dropColumn('execution_planing_date');
            $table->dropColumn('supervisor_commissions');
            $table->dropColumn('supervisor_payment_percentage');

        });
    }
};
