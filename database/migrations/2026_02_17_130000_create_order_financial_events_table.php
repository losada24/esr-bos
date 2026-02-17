<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_financial_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 120);
            $table->string('summary', 255);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at'], 'ofe_order_created_idx');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_financial_events');
    }
};
