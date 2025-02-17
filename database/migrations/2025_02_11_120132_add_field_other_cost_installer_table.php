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
        $table->decimal('other_cost_installer', 10, 2)->nullable();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('payment_extra_fields', function (Blueprint $table) {
        $table->dropColumn('other_cost_installer');
      });
    }
};
