<?php

use App\Enum\RoleEnum;
use App\Models\Order;
use App\Models\PaymentInstallment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createHostedPaymentOrder(): Order
{
    $user = User::factory()->create();
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
