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
        Schema::dropIfExists('order_attachments');
        
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attachable_id');
            $table->unsignedBigInteger('attachable_type');
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('file_size');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('order_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('filename');
            $table->string('file_path');
            $table->string('file_type');
            $table->string('file_size');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_id')->references('id')->on('orders');
        });

        Schema::dropIfExists('attachments');
    }
};
