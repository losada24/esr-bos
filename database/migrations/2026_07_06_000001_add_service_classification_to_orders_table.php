<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('service_origin')->nullable()->after('esr_service');
            $table->boolean('is_post_sale_service')->default(false)->after('service_origin');

            $table->index(['service_origin', 'is_post_sale_service']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['service_origin', 'is_post_sale_service']);
            $table->dropColumn(['service_origin', 'is_post_sale_service']);
        });
    }
};
