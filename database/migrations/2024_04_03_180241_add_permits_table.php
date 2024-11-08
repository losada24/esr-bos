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
        Schema::create('permits', function (Blueprint $table) {
          $table->id();
          $table->unsignedBigInteger('user_id');
          $table->unsignedBigInteger('order_id');
          $table->string('job_address', 500);
          $table->string('permit_number')->nullable();
          $table->dateTime('reylos_reviewed')->nullable();
          $table->dateTime('eng_received')->nullable();
          $table->dateTime('eng_reviewed')->nullable();
          $table->dateTime('drawing_project')->nullable();
          $table->dateTime('submitted')->nullable();
          $table->dateTime('permit_fee_paid')->nullable();
          $table->dateTime('pick_up_permit')->nullable();
          $table->string('notes');
          $table->timestamps();
          $table->softDeletes();


          $table->foreign('order_id')
            ->references('id')
            ->on('orders')
            ->cascadeOnDelete();
          $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
