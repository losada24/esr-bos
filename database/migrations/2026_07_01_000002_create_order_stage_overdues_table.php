<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_stage_overdues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_status_id')->nullable()->constrained('order_status')->nullOnDelete();
            $table->string('status');
            $table->dateTime('stage_started_at')->nullable();
            $table->unsignedSmallInteger('limit_business_days');
            $table->unsignedInteger('business_days_elapsed')->default(0);
            $table->dateTime('detected_at');
            $table->dateTime('resolved_at')->nullable();
            $table->unsignedInteger('resolved_business_days_elapsed')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['order_id', 'status', 'is_active']);
            $table->index(['status', 'is_active']);
            $table->index('detected_at');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stage_overdues');
    }
};
