<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_commission_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_commission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->string('status', 32)->default('OPEN');
            $table->string('split_type', 32);
            $table->decimal('split_value', 12, 2)->default(0);
            $table->decimal('payment_base_amount', 12, 2)->default(0);
            $table->decimal('other_cost_amount', 12, 2)->default(0);
            $table->text('other_cost_notes')->nullable();
            $table->decimal('total_to_pay', 12, 2)->default(0);
            $table->foreignId('commission_period_id')->nullable()->constrained('commission_periods')->nullOnDelete();
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'commission_period_id'], 'order_commission_payments_status_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_commission_payments');
    }
};
