<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_schedule_id')
                ->constrained('payment_schedules')
                ->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('label', 255);
            $table->decimal('percentage', 5, 2);
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('PENDING');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['payment_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_installments');
    }
};
