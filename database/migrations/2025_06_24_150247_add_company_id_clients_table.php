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
      Schema::table('clients', function (Blueprint $table) {
        $table->unsignedBigInteger('company_contact_id')->nullable();

        $table->foreign('company_contact_id')->references('id')->on('company_contacts');
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('clients', function (Blueprint $table) {
        // Primero eliminamos la foreign key
        $table->dropForeign(['company_contact_id']);

        // Luego eliminamos la columna
        $table->dropColumn('company_contact_id');
    });
    }
};
