<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enum\ExtraWorkUnit;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('extra_works', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price');
            $table->enum ('unit', [
                ExtraWorkUnit::EACH->value,
                ExtraWorkUnit::SIDE->value
            ]);
            $table->boolean('planned');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_works');
    }
};
