<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'Service in Review')
            ->update(['status' => 'SERVICE IN REVIEW']);

        DB::table('order_status')
            ->where('status', 'Service in Review')
            ->update(['status' => 'SERVICE IN REVIEW']);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('status', 'SERVICE IN REVIEW')
            ->update(['status' => 'Service in Review']);

        DB::table('order_status')
            ->where('status', 'SERVICE IN REVIEW')
            ->update(['status' => 'Service in Review']);
    }
};
