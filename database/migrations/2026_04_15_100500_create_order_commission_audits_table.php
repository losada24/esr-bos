<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_commission_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_commission_id')->nullable()->constrained('order_commissions')->nullOnDelete();
            $table->foreignId('order_commission_payment_id')->nullable()->constrained('order_commission_payments')->nullOnDelete();
            $table->foreignId('commission_period_id')->nullable()->constrained('commission_periods')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 120)->default('commission-module');
            $table->string('action', 120);
            $table->timestamp('changed_at');
            $table->json('changes');
            $table->timestamps();

            $table->index(['order_commission_id', 'changed_at'], 'order_commission_audits_commission_changed_idx');
            $table->index(['commission_period_id', 'changed_at'], 'order_commission_audits_period_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_commission_audits');
    }
};
