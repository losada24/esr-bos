<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->boolean('is_bm')->default(false)->after('service_id');
            $table->decimal('cost', 10, 2)->nullable()->after('priority');
            $table->string('area')->nullable()->after('cost');
            $table->string('requester_type')->nullable()->after('area');
            $table->unsignedBigInteger('requester_id')->nullable()->after('requester_type');
            $table->string('requester_role')->nullable()->after('requester_id');
            $table->string('assignee_type')->nullable()->after('requester_role');
            $table->unsignedBigInteger('assignee_id')->nullable()->after('assignee_type');
            $table->string('assignee_role')->nullable()->after('assignee_id');
            $table->date('service_created_date')->nullable()->after('target_date');
            $table->date('service_id_requested_date')->nullable()->after('service_created_date');
            $table->date('eta_date')->nullable()->after('service_id_requested_date');
            $table->date('parts_received_date')->nullable()->after('eta_date');
            $table->date('part_delivered_date')->nullable()->after('parts_received_date');
            $table->unsignedInteger('bm_quantity')->nullable()->after('observations');
            $table->date('bm_requested_date')->nullable()->after('bm_quantity');
            $table->string('bm_picked_up_by')->nullable()->after('bm_requested_date');
            $table->date('bm_pickup_date')->nullable()->after('bm_picked_up_by');
            $table->string('bm_invoice_number')->nullable()->after('bm_pickup_date');
            $table->string('bm_invoice_status')->nullable()->after('bm_invoice_number');

            $table->index(['requester_type', 'requester_id']);
            $table->index(['assignee_type', 'assignee_id']);
        });
    }

    public function down(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->dropIndex(['requester_type', 'requester_id']);
            $table->dropIndex(['assignee_type', 'assignee_id']);
            $table->dropColumn([
                'is_bm',
                'cost',
                'area',
                'requester_type',
                'requester_id',
                'requester_role',
                'assignee_type',
                'assignee_id',
                'assignee_role',
                'service_created_date',
                'service_id_requested_date',
                'eta_date',
                'parts_received_date',
                'part_delivered_date',
                'bm_quantity',
                'bm_requested_date',
                'bm_picked_up_by',
                'bm_pickup_date',
                'bm_invoice_number',
                'bm_invoice_status',
            ]);
        });
    }
};
