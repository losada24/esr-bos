<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_commissions', function (Blueprint $table) {
            $table->foreign('next_payment_id')
                ->references('id')
                ->on('order_commission_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_commissions', function (Blueprint $table) {
            $table->dropForeign(['next_payment_id']);
        });
    }
};
