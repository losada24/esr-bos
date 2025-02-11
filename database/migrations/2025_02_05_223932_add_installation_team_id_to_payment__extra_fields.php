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
            $table->unsignedBigInteger('installation_team_id')->nullable();
            $table->foreign('installation_team_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_extra_fields', function (Blueprint $table) {
            $table->dropForeign(['installation_team_id']);
            $table->dropColumn('installation_team_id');
        });
    }
};
