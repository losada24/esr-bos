<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->json('service_type_json')->nullable()->after('service_type');
        });

        DB::table('service_controls')
            ->whereNotNull('service_type')
            ->orderBy('id')
            ->select(['id', 'service_type'])
            ->chunkById(100, function ($serviceControls) {
                foreach ($serviceControls as $serviceControl) {
                    $value = $serviceControl->service_type;
                    $decoded = json_decode((string) $value, true);

                    DB::table('service_controls')
                        ->where('id', $serviceControl->id)
                        ->update([
                            'service_type_json' => json_encode(
                                is_array($decoded) ? array_values($decoded) : [$value],
                                JSON_THROW_ON_ERROR
                            ),
                        ]);
                }
            });

        Schema::table('service_controls', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });

        Schema::table('service_controls', function (Blueprint $table) {
            $table->renameColumn('service_type_json', 'service_type');
        });
    }

    public function down(): void
    {
        Schema::table('service_controls', function (Blueprint $table) {
            $table->string('service_type_string')->nullable()->after('service_type');
        });

        DB::table('service_controls')
            ->whereNotNull('service_type')
            ->orderBy('id')
            ->select(['id', 'service_type'])
            ->chunkById(100, function ($serviceControls) {
                foreach ($serviceControls as $serviceControl) {
                    $decoded = json_decode((string) $serviceControl->service_type, true);
                    $value = is_array($decoded) ? ($decoded[0] ?? null) : $serviceControl->service_type;

                    DB::table('service_controls')
                        ->where('id', $serviceControl->id)
                        ->update(['service_type_string' => $value]);
                }
            });

        Schema::table('service_controls', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });

        Schema::table('service_controls', function (Blueprint $table) {
            $table->renameColumn('service_type_string', 'service_type');
        });
    }
};
