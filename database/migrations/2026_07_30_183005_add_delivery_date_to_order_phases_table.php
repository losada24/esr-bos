<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_phases', function (Blueprint $table) {
            $table->date('delivery_date')->nullable()->after('status');
            $table->index(['delivery_date']);
        });
    }

    public function down(): void
    {
        Schema::table('order_phases', function (Blueprint $table) {
            $table->dropIndex(['delivery_date']);
            $table->dropColumn('delivery_date');
        });
    }
};
