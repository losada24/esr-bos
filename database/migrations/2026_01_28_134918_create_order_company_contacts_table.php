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
        Schema::create('order_company_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('company_contact_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('source_id');
            $table->boolean('is_selected')->default(false);
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['order_id', 'company_contact_id']);

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('company_contact_id')->references('id')->on('company_contacts')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('source_id')->references('id')->on('sources')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_company_contacts');
    }
};
