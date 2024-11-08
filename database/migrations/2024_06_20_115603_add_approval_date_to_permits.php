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
        Schema::table('permits', function (Blueprint $table) {
            $table->dateTime('approval_date')->nullable();
            $table->boolean('closed_permit')->default(false);
            $table->string('additional_process')->nullable();
            $table->unsignedBigInteger('permit_id')->nullable();

            $table->foreign('permit_id')
                ->references('id')
                ->on('permits')
                ->cascadeOnDelete();

            $table->dropColumn('drawing_project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permits', function (Blueprint $table) {
            $table->dropColumn('approval_date');
            $table->dropColumn('closed_permit');
            $table->dropColumn('additional_process');
            $table->dropColumn('permit_id');

            $table->dateTime('drawing_project')->nullable();
        });
    }
};
