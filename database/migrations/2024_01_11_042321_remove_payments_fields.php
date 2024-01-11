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
        Schema::table('payments', function (Blueprint $table) {
          $table->dropColumn('email');
          $table->dropColumn('first_name');
          $table->dropColumn('last_name');
          $table->dropColumn('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
          $table->string('email')->nullable();
          $table->string('first_name')->nullable();
          $table->string('last_name')->nullable();
          $table->string('phone_number')->nullable();
        });
    }
};
