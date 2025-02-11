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
        Schema::create('installation_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('installer_payment', 10, 2)->default(0)->nullable();
            $table->decimal('percentage_payment', 10, 2)->default(0)->nullable();
            $table->date('payment_date')->nullable();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('installation_team_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('installation_team_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_payments');
    }
};
