<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payment_information_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 120);
            $table->timestamp('changed_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->json('changes');
            $table->timestamps();

            $table->index(['order_id', 'changed_at'], 'opia_order_changed_idx');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_information_audits');
    }
};
