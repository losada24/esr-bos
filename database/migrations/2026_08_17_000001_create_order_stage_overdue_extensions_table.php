<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_stage_overdue_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_stage_overdue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->dateTime('stage_started_at')->nullable();
            $table->unsignedSmallInteger('business_days');
            $table->dateTime('extended_until');
            $table->text('note');
            $table->timestamps();

            $table->index(['order_id', 'status', 'extended_until'], 'oso_ext_order_status_until_idx');
            $table->index(['order_stage_overdue_id', 'extended_until'], 'oso_ext_overdue_until_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stage_overdue_extensions');
    }
};
