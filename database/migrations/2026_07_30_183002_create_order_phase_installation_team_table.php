<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_phase_installation_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_phase_id')->constrained('order_phases')->cascadeOnDelete();
            $table->unsignedBigInteger('installation_team_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('installation_team_id')->references('id')->on('installation_teams')->cascadeOnDelete();
            $table->unique(['order_phase_id', 'installation_team_id'], 'order_phase_team_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_phase_installation_team');
    }
};
