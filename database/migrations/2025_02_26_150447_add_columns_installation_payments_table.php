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
      Schema::table('installation_payments', function (Blueprint $table) {
        $table->unsignedBigInteger('biweekly_id')->nullable();
        $table->foreign('biweekly_id')->references('id')->on('biweeklys');
        $table->string('payment_status')->nullable()->default('REVIEW');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('installation_payments', function (Blueprint $table) {
        $table->dropForeign(['biweekly_id']);
        $table->dropColumn('biweekly_id');
        $table->dropColumn('payment_status');
    });
    }
};
