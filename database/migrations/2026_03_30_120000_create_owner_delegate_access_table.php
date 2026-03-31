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
        Schema::create('owner_delegate_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viewer_owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['viewer_owner_id', 'target_owner_id'], 'owner_delegate_access_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_delegate_access');
    }
};
