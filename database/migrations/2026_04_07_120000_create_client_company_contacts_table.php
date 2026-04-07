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
        Schema::create('client_company_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('company_contact_id');
            $table->boolean('is_primary')->default(false);
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'company_contact_id']);
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('company_contact_id')->references('id')->on('company_contacts')->onDelete('cascade');
            $table->foreign('deleted_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_company_contacts');
    }
};
