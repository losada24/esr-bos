<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_events', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_events', 'recurrence_frequency')) {
                $table->string('recurrence_frequency')->nullable()->after('is_repeating');
            }

            if (!Schema::hasColumn('crm_events', 'recurrence_interval')) {
                $table->unsignedTinyInteger('recurrence_interval')->nullable()->after('recurrence_frequency');
            }

            if (!Schema::hasColumn('crm_events', 'recurrence_weekday')) {
                $table->unsignedTinyInteger('recurrence_weekday')->nullable()->after('recurrence_interval');
            }

            if (!Schema::hasColumn('crm_events', 'recurrence_month_day')) {
                $table->unsignedTinyInteger('recurrence_month_day')->nullable()->after('recurrence_weekday');
            }

            if (!Schema::hasColumn('crm_events', 'recurrence_ends_at')) {
                $table->date('recurrence_ends_at')->nullable()->after('recurrence_month_day');
            }

            $table->index(['is_repeating', 'recurrence_frequency']);
        });
    }

    public function down(): void
    {
        Schema::table('crm_events', function (Blueprint $table) {
            $table->dropIndex(['is_repeating', 'recurrence_frequency']);
            $table->dropColumn([
                'recurrence_frequency',
                'recurrence_interval',
                'recurrence_weekday',
                'recurrence_month_day',
                'recurrence_ends_at',
            ]);
        });
    }
};
