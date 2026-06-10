<?php

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentGatewayWebhook;
use App\Models\PaymentInstallment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function createTestOrder(array $attributes = []): Order
{
    $user = User::factory()->create();
    Schema::disableForeignKeyConstraints();

    $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId(array_merge([
        'order_number' => 'RG-TEST-1001',
        'name' => 'Authorize Test Order',
        'job_address' => '123 Test St',
        'city_permits' => false,
        'association_permits' => false,
        'equipment_rental' => false,
        'equipment_rental_price' => null,
        'additional_travel_costs' => null,
        'entry_date' => now()->toDateString(),
        'installation_date' => null,
        'notes' => null,
        'client_id' => 1,
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
        'invoice_number' => 'INV-TEST-1001',
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));

    Schema::enableForeignKeyConstraints();

    return Order::findOrFail($orderId);
}

function webhookSignature(string $payload): string
{
    $key = (string) config('authorize_net.signature_key');

    return 'sha512=' . hash_hmac('sha512', $payload, $key);
}

beforeEach(function () {
    config()->set('authorize_net.signature_key', 'test-signature-key');
});

test('processes authorize net installment webhook and creates movement', function () {
    $order = createTestOrder();
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

    $payload = json_encode([
        'notificationId' => 'notif-1',
        'eventType' => 'net.authorize.payment.authcapture.created',
        'eventDate' => '2026-03-23T12:00:00Z',
        'webhookId' => 'wh-1',
        'payload' => [
            'responseCode' => 1,
            'merchantReferenceId' => 'web_inst_' . $installment->id,
            'authAmount' => 100.00,
            'entityName' => 'transaction',
            'id' => '60020981676',
        ],
    ], JSON_UNESCAPED_SLASHES);

    $response = $this->withHeaders([
        'X-ANET-Signature' => webhookSignature($payload),
        'CONTENT_TYPE' => 'application/json',
    ])->call('POST', '/webhook/authorize-net/payments', [], [], [], [], $payload);

    $response->assertOk();

    expect(PaymentGatewayWebhook::count())->toBe(1);

    $webhook = PaymentGatewayWebhook::first();
    expect($webhook->signature_valid)->toBeTrue()
        ->and($webhook->processing_status)->toBe('PROCESSED')
        ->and($webhook->payment_installment_id)->toBe($installment->id)
        ->and($webhook->channel)->toBe('WEB');

    $installment->refresh();
    expect($installment->status)->toBe('PAID')
        ->and($installment->movements()->count())->toBe(1)
        ->and((float) $installment->movements()->first()->amount)->toBe(100.0)
        ->and($installment->movements()->first()->method)->toBe('AUTHORIZE_NET');
});

test('marks invalid signature and does not update business records', function () {
    $order = createTestOrder();
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

    $payload = json_encode([
        'notificationId' => 'notif-2',
        'eventType' => 'net.authorize.payment.authcapture.created',
        'eventDate' => '2026-03-23T12:00:00Z',
        'webhookId' => 'wh-2',
        'payload' => [
            'responseCode' => 1,
            'merchantReferenceId' => 'web_inst_' . $installment->id,
            'authAmount' => 100.00,
            'entityName' => 'transaction',
            'id' => '60020981677',
        ],
    ], JSON_UNESCAPED_SLASHES);

    $response = $this->withHeaders([
        'X-ANET-Signature' => 'sha512=invalid',
        'CONTENT_TYPE' => 'application/json',
    ])->call('POST', '/webhook/authorize-net/payments', [], [], [], [], $payload);

    $response->assertOk();

    $webhook = PaymentGatewayWebhook::first();
    expect($webhook->signature_valid)->toBeFalse()
        ->and($webhook->processing_status)->toBe('INVALID_SIGNATURE');

    $installment->refresh();
    expect($installment->status)->toBe('PENDING')
        ->and($installment->movements()->count())->toBe(0);
});

test('processes authorize net change order webhook and marks order payment as paid', function () {
    $order = createTestOrder();
    $orderPayment = OrderPayment::create([
        'order_id' => $order->id,
        'type' => 'CHANGE_ORDER',
        'amount' => 75.00,
        'status' => 'PENDING',
    ]);

    $payload = json_encode([
        'notificationId' => 'notif-3',
        'eventType' => 'net.authorize.payment.authcapture.created',
        'eventDate' => '2026-03-23T13:00:00Z',
        'webhookId' => 'wh-3',
        'payload' => [
            'responseCode' => 1,
            'merchantReferenceId' => 'web_op_' . $orderPayment->id,
            'authAmount' => 75.00,
            'entityName' => 'transaction',
            'id' => '60020981678',
        ],
    ], JSON_UNESCAPED_SLASHES);

    $response = $this->withHeaders([
        'X-ANET-Signature' => webhookSignature($payload),
        'CONTENT_TYPE' => 'application/json',
    ])->call('POST', '/webhook/authorize-net/payments', [], [], [], [], $payload);

    $response->assertOk();

    $orderPayment->refresh();
    expect($orderPayment->status)->toBe('PAID')
        ->and($orderPayment->paid_at)->not->toBeNull()
        ->and((string) $orderPayment->note)->toContain('AUTHORIZE.NET');
});
