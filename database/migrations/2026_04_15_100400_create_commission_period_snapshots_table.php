<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_period_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('data');
            $table->timestamps();

            $table->unique('commission_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_period_snapshots');
    }
};
