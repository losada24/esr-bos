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
        Schema::table('attachments', function (Blueprint $table) {
          $table->dropColumn([
            'attachable_id',
            'attachable_type',
            'file_size',
            'document_type'
          ]);
        });

        Schema::table('attachments', function (Blueprint $table) {
          $table->morphs('attachable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
          $table->dropMorphs('attachable');
          $table->unsignedBigInteger('attachable_id');
          $table->unsignedBigInteger('attachable_type');
          $table->string('file_size');
          $table->string('document_type')->nullable();
        });
    }
};
