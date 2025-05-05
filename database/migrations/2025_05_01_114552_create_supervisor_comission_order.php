<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supervisor_comission_orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('percentage', 5, 2); // 0.30, 0.20, 0.15
            $table->decimal('amount', 10, 2); // comisión por ese porcentaje
            $table->unsignedBigInteger('order_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_comission_orders');
    }
};
