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
      Schema::table('payment_extra_fields', function (Blueprint $table) {
        $table->dropColumn('responsible_extra_work');
        $table->dropColumn('notes');
      });
        Schema::table('installation_payments', function (Blueprint $table) {
          $table->string('responsible_extra_work')->nullable();
          $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('payment_extra_fields', function (Blueprint $table) {
        $table->string('responsible_extra_work')->nullable();
        $table->text('notes')->nullable();
      });
      Schema::table('installation_payments', function (Blueprint $table) {
        $table->dropColumn('responsible_extra_work');
        $table->dropColumn('notes');
      });
    }
};
