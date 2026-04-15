<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('beneficiary_source_type', 32);
            $table->unsignedBigInteger('beneficiary_source_id');
            $table->string('beneficiary_relation', 32);
            $table->string('beneficiary_name_snapshot');
            $table->string('beneficiary_email_snapshot')->nullable();
            $table->string('status', 32)->default('OPEN');
            $table->string('calculation_type', 32);
            $table->decimal('percentage_value', 10, 2)->nullable();
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->decimal('project_amount_snapshot', 12, 2)->default(0);
            $table->decimal('fee_amount_snapshot', 12, 2)->default(0);
            $table->decimal('base_amount_snapshot', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('other_cost_amount', 12, 2)->default(0);
            $table->text('other_cost_notes')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('pending_amount', 12, 2)->default(0);
            $table->unsignedBigInteger('next_payment_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['order_id', 'beneficiary_source_type', 'beneficiary_source_id'],
                'order_commissions_order_beneficiary_unique'
            );
            $table->index(['beneficiary_source_type', 'beneficiary_source_id'], 'order_commissions_beneficiary_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_commissions');
    }
};
