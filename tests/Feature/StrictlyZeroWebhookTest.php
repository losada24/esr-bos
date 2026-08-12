<?php

use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentGatewayWebhook;
use App\Models\PaymentInstallment;
use App\Models\PaymentIntent;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function createStrictlyZeroWebhookOrder(?Client $client = null): Order
{
    $user = User::factory()->create();
    $client ??= Client::factory()->create();
    Schema::disableForeignKeyConstraints();

    $orderId = DB::table('orders')->insertGetId([
        'order_number' => 'RG-STRICTLY-1001',
        'name' => 'Strictly Webhook Order',
        'job_address' => '123 Test St',
        'city_permits' => false,
        'association_permits' => false,
        'equipment_rental' => false,
        'equipment_rental_price' => null,
        'additional_travel_costs' => null,
        'entry_date' => now()->toDateString(),
        'installation_date' => null,
        'notes' => null,
        'client_id' => $client->id,
        'type_of_work_id' => 1,
        'type_of_housing_id' => 1,
        'installer_id' => $user->id,
        'supervisor_id' => $user->id,
        'travel_cost_id' => 1,
        'duration_of_work_id' => 1,
        'user_id' => $user->id,
        'method_of_payment' => 'CASH',
        'project_amount' => 100.00,
        'service' => 'new_order',
        'status' => 'NEW CUSTOMER REQUEST',
        'job_city' => 'Miami',
        'job_state' => 'FL',
        'job_zip' => '33101',
        'invoice_number' => 'INV-STRICTLY-1001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Schema::enableForeignKeyConstraints();

    return Order::findOrFail($orderId);
}

test('processes strictly zero payment link webhook and creates installment movement', function () {
    config()->set('strictly_zero.key_hash', 'key-hash');
    config()->set('strictly_zero.username', 'strictly-user');
    config()->set('strictly_zero.password', 'strictly-pass');
    config()->set('strictly_zero.webhook_username', 'webhook-user');
    config()->set('strictly_zero.webhook_password', 'webhook-pass');
    config()->set('strictly_zero.base_url', 'https://api.paywithzero.net');
    config()->set('strictly_zero.payment_link_path', '/v1/public/202104/payment-link');

    Http::fake([
        'https://api.paywithzero.net/v1/public/202104/payment-link/strictly-link-id' => Http::response([
            'data' => [
                'id' => 'strictly-link-id',
                'paid' => true,
                'amount' => 10000,
                'totalAmount' => 10000,
                'paidAmount' => 10000,
                'paymentId' => 'strictly-payment-id',
                'transactionId' => 'strictly-transaction-id',
            ],
        ]),
    ]);

    $client = Client::factory()->create();
    $order = createStrictlyZeroWebhookOrder($client);
    $schedule = PaymentSchedule::create([
        'order_id' => $order->id,
        'schedule_type' => 'CUSTOMIZED',
        'total_amount' => 100.00,
    ]);

    $installment = PaymentInstallment::create([
        'payment_schedule_id' => $schedule->id,
        'position' => 1,
        'label' => 'Installment 1',
        'percentage' => 100.00,
        'amount' => 100.00,
        'status' => 'PENDING',
    ]);

    PaymentIntent::create([
        'token' => 'strictly-intent-token',
        'payment_type' => 'quota',
        'payment_id' => $installment->id,
        'order_id' => $order->id,
        'amount' => 100.00,
        'channel' => 'MOBILE',
        'provider' => 'STRICTLY_ZERO',
        'provider_payment_link_id' => 'strictly-link-id',
        'provider_reference' => 'MOB-1-TEST',
        'provider_payment_url' => 'https://merchant.paywithzero.net/zpay/payment-request/strictly-link-id',
        'status' => 'PENDING',
        'expires_at' => now()->addYears(10),
    ]);

    $payload = json_encode([
        'action' => 'paymentLink',
        'data' => [
            'paid' => true,
            'amount' => 10000,
            'totalAmount' => 10000,
            'paidAmount' => 10000,
            'paymentLinkId' => 'strictly-link-id',
            'paymentId' => 'strictly-payment-id',
            'transactionId' => 'strictly-transaction-id',
        ],
    ]);

    $response = $this
        ->withBasicAuth('webhook-user', 'webhook-pass')
        ->call('POST', '/webhook/strictly-zero/payments', [], [], [], [], $payload);

    $response->assertOk()->assertSee('ok');

    $webhook = PaymentGatewayWebhook::first();
    expect($webhook)->not->toBeNull()
        ->and($webhook->provider)->toBe('STRICTLY_ZERO')
        ->and($webhook->event_type)->toBe('paymentLink')
        ->and($webhook->payload_entity_id)->toBe('strictly-link-id')
        ->and($webhook->gateway_transaction_id)->toBe('strictly-transaction-id')
        ->and($webhook->processing_status)->toBe('PROCESSED')
        ->and($webhook->payment_installment_id)->toBe($installment->id);

    $installment->refresh();
    expect($installment->status)->toBe('PAID')
        ->and($installment->movements()->count())->toBe(1);
});

