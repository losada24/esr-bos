<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('to_from');
            $table->dateTime('call_start_time');
            $table->unsignedInteger('call_duration_minutes')->nullable();
            $table->boolean('reminder_enabled')->default(false);
            $table->unsignedInteger('reminder_minutes_before')->nullable();
            $table->string('call_type')->default('Outbound');
            $table->string('outgoing_call_status')->default('Scheduled');
            $table->string('call_purpose')->nullable();
            $table->text('call_agenda')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'call_start_time']);
            $table->index(['order_id', 'call_start_time']);
            $table->index(['client_id', 'call_start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_calls');
    }
};
