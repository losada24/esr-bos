<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_commissions', function (Blueprint $table) {
            $table->decimal('financing_fee_amount', 12, 2)->default(0)->after('fee_amount_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_commissions', function (Blueprint $table) {
            $table->dropColumn('financing_fee_amount');
        });
    }
};
