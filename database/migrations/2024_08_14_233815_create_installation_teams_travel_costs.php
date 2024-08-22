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
        Schema::table('installation_teams', function (Blueprint $table) {
            $table->dropColumn('work_area');
        });

        Schema::create('installation_teams_travel_costs', function (Blueprint $table) {
             $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('installation_team_id');
            $table->unsignedBigInteger('travel_cost_id');
            $table->softDeletes();

            $table->foreign('installation_team_id')->references('id')->on('installation_teams');
            $table->foreign('travel_cost_id')->references('id')->on('travel_costs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_teams_travel_costs');
        Schema::table('installation_teams', function (Blueprint $table) {
            $table->string('work_area');
        });
    }
};
