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
      Schema::table('history_pending_payments', function (Blueprint $table) {
        $table->string('type_history')->nullable()->default('INSTALLER');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('history_pending_payments', function (Blueprint $table) {
        $table->dropColumn('type_history');
    });
    }
};
