<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('pending_financing_or_deposit')->nullable()->after('association_permits');
            $table->boolean('pending_hoa_approval')->nullable()->after('pending_financing_or_deposit');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'pending_financing_or_deposit',
                'pending_hoa_approval',
            ]);
        });
    }
};
