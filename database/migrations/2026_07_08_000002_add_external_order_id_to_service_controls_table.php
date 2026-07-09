<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->string('external_order_id')->nullable()->after('service_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->dropColumn('external_order_id');
        });
    }
};
