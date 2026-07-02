<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->where('status', 'PENDING MATERIALS')
            ->update(['status' => 'PENDING MATERIALS DEALER ESR']);

        DB::table('order_status')
            ->where('status', 'PENDING MATERIALS')
            ->update(['status' => 'PENDING MATERIALS DEALER ESR']);
    }

    public function down(): void
    {
        DB::table('orders')
            ->where('status', 'PENDING MATERIALS DEALER ESR')
            ->update(['status' => 'PENDING MATERIALS']);

        DB::table('order_status')
            ->where('status', 'PENDING MATERIALS DEALER ESR')
            ->update(['status' => 'PENDING MATERIALS']);
    }
};
