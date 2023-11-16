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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('featured_image')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
          $table->unsignedBigInteger('company_id')->nullable();
          $table->foreign('company_id')->references('id')->on('companies');
        });

        Schema::table('clients', function (Blueprint $table) {
          $table->unsignedBigInteger('company_id')->nullable();
          $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
          $table->dropForeign(['company_id']);
          $table->dropColumn('company_id');
        });

        Schema::table('clients', function (Blueprint $table) {
          $table->dropForeign(['company_id']);
          $table->dropColumn('company_id');
        });

        Schema::dropIfExists('companies');
    }
};
