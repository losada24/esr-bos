<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('parent_order_id')
                ->nullable()
                ->after('id')
                ->constrained('orders')
                ->nullOnDelete();

            $table->foreignId('root_order_id')
                ->nullable()
                ->after('parent_order_id')
                ->constrained('orders')
                ->nullOnDelete();

            $table->boolean('counts_for_owner_commission')
                ->default(true)
                ->after('root_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_order_id');
            $table->dropConstrainedForeignId('root_order_id');
            $table->dropColumn('counts_for_owner_commission');
        });
    }
};
