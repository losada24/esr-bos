<?php

use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Event::fake();
    Queue::fake();
    Role::findOrCreate(RoleEnum::ADMIN->value);
});

function createFinancingJobsiteAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::ADMIN->value);

    return $user;
}

function createFinancedEsrOrder(array $overrides = []): Order
{
    $client = Client::factory()->create();
    $creator = User::factory()->create();

    return Order::create(array_merge([
        'client_id' => $client->id,
        'user_id' => $creator->id,
        'name' => 'Financed ESR Order',
        'order_number' => 'ESR-' . uniqid(),
        'status' => OrderStatusEnum::REVIEW->value,
        'order_type' => OrderTypeEnum::RESIDENTIAL->value,
        'product_line' => 'ESR',
        'method_of_payment' => MethodOfPayment::FINANCED->value,
        'project_amount' => 10000,
    ], $overrides));
}

test('a qualifying financed ESR order cannot enter account receipt without its financing jobsite address', function () {
    $admin = createFinancingJobsiteAdmin();
    $order = createFinancedEsrOrder();

    $response = $this
        ->actingAs($admin)
        ->postJson(route('frontdesk.updateStatus', ['order' => $order->id]), [
            'status' => OrderStatusEnum::ACCOUNT_RECEIPT->value,
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['financing_jobsite_address']);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::REVIEW->value);
});

test('a qualifying financed ESR order can enter account receipt after its financing jobsite address is saved', function () {
    $admin = createFinancingJobsiteAdmin();
    $order = createFinancedEsrOrder([
        'financing_jobsite_address' => '456 Financing Jobsite Ave, Miami, FL 33101',
    ]);

    $response = $this
        ->actingAs($admin)
        ->postJson(route('frontdesk.updateStatus', ['order' => $order->id]), [
            'status' => OrderStatusEnum::ACCOUNT_RECEIPT->value,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('order.status', OrderStatusEnum::ACCOUNT_RECEIPT->value);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::ACCOUNT_RECEIPT->value);
});

test('the delivery address can be copied as the financing jobsite address', function () {
    $admin = createFinancingJobsiteAdmin();
    $order = createFinancedEsrOrder([
        'job_address' => '123 Delivery St',
        'city' => 'Miami',
        'job_state' => 'FL',
        'job_zip' => '33101',
    ]);

    $response = $this
        ->actingAs($admin)
        ->postJson(route('frontdesk.updateStatus', ['order' => $order->id]), [
            'status' => OrderStatusEnum::ACCOUNT_RECEIPT->value,
            'financing_jobsite_same_as_delivery' => true,
        ]);

    $response->assertOk();
    expect($order->fresh()->financing_jobsite_address)->toBe('123 Delivery St, Miami, FL, 33101');
});

test('a different Google-selected address is saved as the financing jobsite address', function () {
    $admin = createFinancingJobsiteAdmin();
    $order = createFinancedEsrOrder([
        'job_address' => '123 Delivery St',
    ]);

    $response = $this
        ->actingAs($admin)
        ->postJson(route('frontdesk.updateStatus', ['order' => $order->id]), [
            'status' => OrderStatusEnum::ACCOUNT_RECEIPT->value,
            'financing_jobsite_same_as_delivery' => false,
            'financing_jobsite_address' => '789 Different Jobsite Ave, Miami, FL 33130',
        ]);

    $response->assertOk();
    expect($order->fresh()->financing_jobsite_address)->toBe('789 Different Jobsite Ave, Miami, FL 33130');
});

test('the financing jobsite address is not required when the financed amount is below ten thousand', function () {
    $admin = createFinancingJobsiteAdmin();
    $order = createFinancedEsrOrder([
        'project_amount' => 9999.99,
    ]);

    $response = $this
        ->actingAs($admin)
        ->postJson(route('frontdesk.updateStatus', ['order' => $order->id]), [
            'status' => OrderStatusEnum::ACCOUNT_RECEIPT->value,
        ]);

    $response->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::ACCOUNT_RECEIPT->value);
});

test('cash and financed orders do not use the financed-only address requirement', function () {
    $admin = createFinancingJobsiteAdmin();
    $order = createFinancedEsrOrder([
        'method_of_payment' => MethodOfPayment::FINANCEDCASH->value,
        'project_amount' => 15000,
        'down_payment' => 5000,
    ]);

    $response = $this
        ->actingAs($admin)
        ->postJson(route('frontdesk.updateStatus', ['order' => $order->id]), [
            'status' => OrderStatusEnum::ACCOUNT_RECEIPT->value,
        ]);

    $response->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::ACCOUNT_RECEIPT->value);
});
