<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->string('provider', 50)->nullable()->after('channel');
            $table->string('provider_payment_link_id', 120)->nullable()->after('provider');
            $table->string('provider_payment_request_id', 120)->nullable()->after('provider_payment_link_id');
            $table->string('provider_reference', 120)->nullable()->after('provider_payment_request_id');
            $table->text('provider_payment_url')->nullable()->after('provider_reference');
            $table->string('provider_status', 80)->nullable()->after('provider_payment_url');
            $table->json('provider_metadata')->nullable()->after('provider_status');

            $table->index('provider');
            $table->index('provider_payment_link_id');
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payment_intents', function (Blueprint $table) {
            $table->dropIndex(['provider']);
            $table->dropIndex(['provider_payment_link_id']);
            $table->dropIndex(['provider_reference']);
            $table->dropColumn([
                'provider',
                'provider_payment_link_id',
                'provider_payment_request_id',
                'provider_reference',
                'provider_payment_url',
                'provider_status',
                'provider_metadata',
            ]);
        });
    }
};
