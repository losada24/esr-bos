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
        Schema::table('client_address', function (Blueprint $table) {
          $table->dateTime('appointment_date')->nullable()->change();
          $table->string('notes', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('client_address', function (Blueprint $table) {
        $table->dateTime('appointment_date')->change();
        $table->string('notes', 500)->change();
      });
    }
};
