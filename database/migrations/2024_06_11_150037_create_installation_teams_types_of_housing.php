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
        Schema::create('installation_teams_types_of_housing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('installation_team_id');
            $table->unsignedBigInteger('type_of_housing_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('installation_team_id')->references('id')->on('installation_teams');
            $table->foreign('type_of_housing_id')->references('id')->on('types_of_housing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_teams_types_of_housing');
    }
};
