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
        Schema::create('payment_extra_fields', function (Blueprint $table) {
            $table->id();
            $table->string('responsible_extra_work')->nullable();
            $table->text('notes')->nullable();
            $table->string('documents_submitted')->nullable();
            $table->boolean('collected_payment')->nullable()->default(false);
            $table->string('installer_payment_status')->nullable()->default('OPEN');
            $table->unsignedBigInteger('order_id');
            $table->softDeletes();
            $table->timestamps();
  
            $table->foreign('order_id')->references('id')->on('orders');
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_extra_fields');
    }
};
