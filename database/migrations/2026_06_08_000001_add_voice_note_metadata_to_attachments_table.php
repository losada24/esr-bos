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
            $table->string('mime_type')->nullable()->after('file_type');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
            $table->unsignedInteger('duration_seconds')->nullable()->after('size_bytes');
            $table->string('transcription_status')->nullable()->after('duration_seconds');
            $table->longText('transcription_text')->nullable()->after('transcription_status');
            $table->text('transcription_error')->nullable()->after('transcription_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn([
                'mime_type',
                'size_bytes',
                'duration_seconds',
                'transcription_status',
                'transcription_text',
                'transcription_error',
            ]);
        });
    }
};
