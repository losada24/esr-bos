<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_commission_payments', function (Blueprint $table) {
            $table->string('payment_kind', 32)
                ->default('REGULAR')
                ->after('status');
        });

        DB::table('order_commission_payments')
            ->whereNull('payment_kind')
            ->update(['payment_kind' => 'REGULAR']);
    }

    public function down(): void
    {
        Schema::table('order_commission_payments', function (Blueprint $table) {
            $table->dropColumn('payment_kind');
        });
    }
};
