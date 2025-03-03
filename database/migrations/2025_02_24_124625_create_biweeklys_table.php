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
        Schema::create('biweeklys', function (Blueprint $table) {
            $table->id();
            $table->date('start_biweekly_period');
            $table->date('end_biweekly_period');
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('installation_team_id');
            $table->timestamps();
            $table->softDeletes();

          $table->foreign('installation_team_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biweeklys');
    }
};
