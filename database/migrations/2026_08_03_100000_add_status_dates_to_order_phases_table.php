<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_phases', function (Blueprint $table) {
            $table->date('inspection_date')->nullable()->after('installation_end_date');
            $table->date('finish_date')->nullable()->after('inspection_date');
            $table->date('service_date')->nullable()->after('finish_date');
            $table->date('pending_collect')->nullable()->after('service_date');
            $table->date('final_inspection_date')->nullable()->after('pending_collect');
            $table->date('complete_date')->nullable()->after('final_inspection_date');

            $table->index(['status', 'inspection_date']);
            $table->index(['status', 'finish_date']);
            $table->index(['status', 'service_date']);
            $table->index(['status', 'pending_collect']);
            $table->index(['status', 'final_inspection_date']);
            $table->index(['status', 'complete_date']);
        });
    }

    public function down(): void
    {
        Schema::table('order_phases', function (Blueprint $table) {
            $table->dropIndex(['status', 'inspection_date']);
            $table->dropIndex(['status', 'finish_date']);
            $table->dropIndex(['status', 'service_date']);
            $table->dropIndex(['status', 'pending_collect']);
            $table->dropIndex(['status', 'final_inspection_date']);
            $table->dropIndex(['status', 'complete_date']);

            $table->dropColumn([
                'inspection_date',
                'finish_date',
                'service_date',
                'pending_collect',
                'final_inspection_date',
                'complete_date',
            ]);
        });
    }
};
