<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('notification_id', 120)->unique();
            $table->string('webhook_id', 120)->nullable();
            $table->string('event_type', 120);
            $table->timestamp('event_date')->nullable();
            $table->text('signature_header')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('source_ip', 45)->nullable();
            $table->json('headers_json')->nullable();
            $table->longText('raw_body');
            $table->json('payload_json')->nullable();
            $table->string('payload_entity_name', 80)->nullable();
            $table->string('payload_entity_id', 120)->nullable();
            $table->string('gateway_transaction_id', 120)->nullable();
            $table->string('merchant_reference_id', 120)->nullable();
            $table->string('channel', 50)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('response_code', 20)->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_installment_id')->nullable()->constrained('payment_installments')->nullOnDelete();
            $table->foreignId('order_payment_id')->nullable()->constrained('order_payments')->nullOnDelete();
            $table->string('matched_by', 50)->nullable();
            $table->string('processing_status', 40)->default('RECEIVED');
            $table->text('processing_error')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('event_type');
            $table->index('gateway_transaction_id');
            $table->index('merchant_reference_id');
            $table->index('processing_status');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_webhooks');
    }
};
