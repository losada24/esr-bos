<?php

use App\Enum\RoleEnum;
use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\PaymentInstallment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createHostedPaymentOrder(?Client $client = null): Order
{
    $user = User::factory()->create();
    $client ??= Client::factory()->create();
    Schema::disableForeignKeyConstraints();

    $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId([
        'order_number' => 'RG-TEST-2001',
        'name' => 'Hosted Payment Order',
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
        'invoice_number' => 'INV-TEST-2001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Schema::enableForeignKeyConstraints();

    return Order::findOrFail($orderId);
}

test('admin can open authorize net hosted payment redirect page for quota', function () {
    config()->set('authorize_net.api_login_id', 'login');
    config()->set('authorize_net.transaction_key', 'key');
    config()->set('authorize_net.form_url', 'https://test.authorize.net/payment/payment');

    Http::fake([
        '*' => Http::response([
            'token' => 'test-hosted-token',
            'messages' => [
                'resultCode' => 'Ok',
                'message' => [
                    ['code' => 'I00001', 'text' => 'Successful.'],
                ],
            ],
        ]),
    ]);

    $admin = User::factory()->create();
    Role::findOrCreate(RoleEnum::ADMIN->value);
    $admin->assignRole(RoleEnum::ADMIN->value);

    $order = createHostedPaymentOrder();
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

    $response = $this->actingAs($admin)
        ->get('/payments/authorize-net/quota/' . $installment->id);

    $response->assertOk()
        ->assertSee('test-hosted-token')
        ->assertSee('https://test.authorize.net/payment/payment', false);
});

test('mobile customer can request a temporary payment link', function () {
    config()->set('authorize_net.payment_intent_ttl_minutes', 15);

    $customer = User::factory()->create();
    Role::findOrCreate(RoleEnum::CUSTOMER->value);
    $customer->assignRole(RoleEnum::CUSTOMER->value);

    $client = Client::factory()->create([
        'mobile_user_id' => $customer->id,
    ]);

    $order = createHostedPaymentOrder($client);
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

    Sanctum::actingAs($customer, ['*']);

    $response = $this->postJson('/api/mobile/payment-link', [
        'payment_type' => 'quota',
        'payment_id' => $installment->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.payment_type', 'quota')
        ->assertJsonPath('data.payment_id', $installment->id)
        ->assertJsonPath('data.channel', 'MOBILE');

    $intent = PaymentIntent::first();
    expect($intent)->not->toBeNull()
        ->and($intent->payment_type)->toBe('quota')
        ->and($intent->payment_id)->toBe($installment->id)
        ->and($intent->channel)->toBe('MOBILE')
        ->and($intent->status)->toBe('PENDING');

    $response->assertJsonPath(
        'data.payment_url',
        route('authorize-net.payments.intent.show', ['token' => $intent->token])
    );
});

test('temporary payment link opens the hosted payment form', function () {
    config()->set('authorize_net.api_login_id', 'login');
    config()->set('authorize_net.transaction_key', 'key');
    config()->set('authorize_net.form_url', 'https://test.authorize.net/payment/payment');

    Http::fake([
        '*' => Http::response([
            'token' => 'intent-hosted-token',
            'messages' => [
                'resultCode' => 'Ok',
                'message' => [
                    ['code' => 'I00001', 'text' => 'Successful.'],
                ],
            ],
        ]),
    ]);

    $customer = User::factory()->create();
    Role::findOrCreate(RoleEnum::CUSTOMER->value);
    $customer->assignRole(RoleEnum::CUSTOMER->value);

    $client = Client::factory()->create([
        'mobile_user_id' => $customer->id,
    ]);

    $order = createHostedPaymentOrder($client);
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

    $intent = PaymentIntent::create([
        'token' => 'temporary-payment-token',
        'payment_type' => 'quota',
        'payment_id' => $installment->id,
        'order_id' => $order->id,
        'amount' => 100.00,
        'channel' => 'MOBILE',
        'status' => 'PENDING',
        'expires_at' => now()->addMinutes(15),
        'created_by_user_id' => $customer->id,
    ]);

    $response = $this->get('/payments/authorize-net/intent/' . $intent->token);

    $response->assertOk()
        ->assertSee('intent-hosted-token')
        ->assertSee('https://test.authorize.net/payment/payment', false);

    $intent->refresh();
    expect($intent->status)->toBe('USED')
        ->and($intent->used_at)->not->toBeNull();
});
