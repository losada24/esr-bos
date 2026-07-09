<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->string('request_origin')->default('SERVICE')->after('creation_source');
            $table->index(['request_origin', 'service_source']);
        });

        DB::table('service_controls')
            ->join('orders', 'orders.id', '=', 'service_controls.order_id')
            ->where(function ($query) {
                $query
                    ->where('orders.service_origin', 'OWNER')
                    ->orWhere('orders.esr_service', true);
            })
            ->update(['service_controls.request_origin' => 'OWNER']);
    }

    public function down(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->dropIndex(['request_origin', 'service_source']);
            $table->dropColumn('request_origin');
        });
    }
};
