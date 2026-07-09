<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->unsignedInteger('production_output_overdue_days')->nullable()->after('parts_received_date');
            $table->timestamp('production_output_overdue_resolved_at')->nullable()->after('production_output_overdue_days');
        });
    }

    public function down(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->dropColumn([
                'production_output_overdue_days',
                'production_output_overdue_resolved_at',
            ]);
        });
    }
};
