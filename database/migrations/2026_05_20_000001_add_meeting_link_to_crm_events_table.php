<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_events', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_events', 'meeting_link')) {
                $table->string('meeting_link')->nullable()->after('online_meeting');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_events', function (Blueprint $table) {
            if (Schema::hasColumn('crm_events', 'meeting_link')) {
                $table->dropColumn('meeting_link');
            }
        });
    }
};
