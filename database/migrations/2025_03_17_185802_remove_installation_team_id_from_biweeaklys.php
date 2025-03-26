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
        Schema::table('biweeklys', function (Blueprint $table) {
            $table->dropForeign(['installation_team_id']);
            $table->dropColumn('installation_team_id');
            $table->dropColumn('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biweeklys', function (Blueprint $table) {
          $table->unsignedBigInteger('installation_team_id');
          $table->foreign('installation_team_id')->references('id')->on('users');
          $table->string('payment_method')->nullable();
        });
    }
};
