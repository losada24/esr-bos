<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_phase_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_phase_id')->constrained('order_phases')->cascadeOnDelete();
            $table->foreignId('order_product_id')->constrained('order_products')->cascadeOnDelete();
            $table->decimal('qty', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_phase_id', 'order_product_id'], 'order_phase_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_phase_products');
    }
};
