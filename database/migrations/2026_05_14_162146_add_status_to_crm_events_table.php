<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_events', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_events', 'status')) {
                $table->string('status')->default('Scheduled')->after('ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_events', function (Blueprint $table) {
            if (Schema::hasColumn('crm_events', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
