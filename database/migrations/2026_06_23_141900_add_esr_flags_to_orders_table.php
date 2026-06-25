<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('esr_design')->default(false)->after('product_line');
            $table->boolean('esr_express')->default(false)->after('esr_design');
            $table->boolean('esr_reylos_glass')->default(false)->after('esr_express');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'esr_design',
                'esr_express',
                'esr_reylos_glass',
            ]);
        });
    }
};
