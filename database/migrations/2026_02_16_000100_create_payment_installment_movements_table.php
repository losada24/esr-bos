<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_installment_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_installment_id')
                ->constrained('payment_installments')
                ->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->dateTime('paid_at');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['payment_installment_id', 'paid_at'], 'pim_installment_paid_at_idx');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_installment_movements');
    }
};
