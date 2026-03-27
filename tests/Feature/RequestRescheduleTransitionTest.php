<?php

use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Models\Client;
use App\Models\DurationOfWork;
use App\Models\Order;
use App\Models\SaleForm;
use App\Models\TravelCost;
use App\Models\TypeOfHousing;
use App\Models\TypeOfWork;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Event::fake();
    Queue::fake();
});

function createSalesAdmin(): User
{
    Role::findOrCreate(RoleEnum::ADMIN->value);

    $user = User::factory()->create();
    $user->assignRole(RoleEnum::ADMIN->value);

    return $user;
}

function createSalesUserWithRoles(array $roles): User
{
    foreach ($roles as $role) {
        Role::findOrCreate($role);
    }

    $user = User::factory()->create();
    $user->assignRole($roles);

    return $user;
}

function createSalesOrder(?string $status = null): Order
{
    $status ??= OrderStatusEnum::REQUEST_RE_SCHEDULE->value;

    $client = Client::factory()->create();
    $owner = User::factory()->create(['email' => 'owner+' . uniqid() . '@example.com']);
    $installer = User::factory()->create(['email' => 'installer+' . uniqid() . '@example.com']);
    $supervisor = User::factory()->create(['email' => 'supervisor+' . uniqid() . '@example.com']);
    $creator = User::factory()->create(['email' => 'creator+' . uniqid() . '@example.com']);

    $typeOfWork = TypeOfWork::create(['name' => 'Windows']);
    $typeOfHousing = TypeOfHousing::create(['name' => 'Residential']);
    $travelCost = TravelCost::create(['name' => 'Local', 'price' => 0]);
    $durationOfWork = DurationOfWork::create(['name' => 'One day', 'price' => 0, 'number_of_day' => 1]);

    $order = Order::unguarded(fn () => Order::create([
        'order_number' => 'ORD-' . uniqid(),
        'name' => 'Test Order',
        'job_address' => '123 Test St',
        'entry_date' => now()->toDateString(),
        'client_id' => $client->id,
        'type_of_work_id' => $typeOfWork->id,
        'type_of_housing_id' => $typeOfHousing->id,
        'installer_id' => $installer->id,
        'supervisor_id' => $supervisor->id,
        'travel_cost_id' => $travelCost->id,
        'duration_of_work_id' => $durationOfWork->id,
        'user_id' => $creator->id,
        'method_of_payment' => MethodOfPayment::CASH->value,
        'service' => ServiceEnum::INSTALLATION->value,
        'contract_signing_date' => now()->toDateString(),
        'payment_factory_date' => now()->toDateString(),
        'delivery_date' => now()->toDateString(),
        'installation_date' => now()->toDateString(),
        'status' => $status,
        'order_type' => OrderTypeEnum::RESIDENTIAL->value,
    ]));

    $order->owners()->attach($owner->id);

    SaleForm::create([
        'order_id' => $order->id,
        'sale' => false,
        'installation' => false,
        'permit' => false,
        'replacement' => false,
        'new_construction' => false,
        'financing' => false,
        'screen' => false,
        'design' => false,
        'mountin' => false,
        'bar' => false,
        'shutter_hole' => false,
        'floor_cutting' => false,
        'interior_finish' => false,
        'floor' => '',
        'frame_color' => '',
        'glass_color' => '',
        'glass_type' => '',
        'glass_coating' => '',
        'door_quantity' => 0,
        'window_quantity' => 0,
    ]);

    return $order;
}

test('request re-schedule orders cannot move to another sales status through the generic status endpoint', function () {
    $admin = createSalesAdmin();
    $order = createSalesOrder();

    $response = $this
        ->actingAs($admin)
        ->postJson(route('frontdesk.updateStatus', ['order' => $order->id]), [
            'status' => OrderStatusEnum::FOLLOW_UP->value,
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::REQUEST_RE_SCHEDULE->value);
});

test('request re-schedule orders cannot move to stand by', function () {
    $admin = createSalesAdmin();
    $order = createSalesOrder();

    $response = $this
        ->actingAs($admin)
        ->postJson(route('sales.assign_stand_by', ['order' => $order->id]), [
            'note' => 'Trying to move it elsewhere.',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::REQUEST_RE_SCHEDULE->value);
});

test('request re-schedule orders can move back to estimate and appointment schedule', function () {
    $admin = createSalesAdmin();
    $order = createSalesOrder();

    $response = $this
        ->actingAs($admin)
        ->postJson(route('sales.assign_estimate', ['order' => $order->id]), [
            'schedule_appointment' => now()->addDay()->toDateTimeString(),
            'owner_ids' => [],
        ]);

    $response->assertOk();
    $response->assertJsonPath('order.status', OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value);
});

test('owner without admin roles cannot move an order to estimate and appointment schedule', function () {
    $owner = createSalesUserWithRoles([RoleEnum::OWNER->value]);
    $order = createSalesOrder();
    $order->owners()->sync([$owner->id]);

    $response = $this
        ->actingAs($owner)
        ->postJson(route('sales.assign_estimate', ['order' => $order->id]), [
            'schedule_appointment' => now()->addDay()->toDateTimeString(),
            'owner_ids' => [$owner->id],
        ]);

    $response
        ->assertStatus(403)
        ->assertJsonPath('message', 'You are not allowed to move this order to ESTIMATE & APPT SCHEDULE.');

    expect($order->fresh()->status)->toBe(OrderStatusEnum::REQUEST_RE_SCHEDULE->value);
});

test('owner admin can move an order to estimate and appointment schedule even if also owner', function () {
    $ownerAdmin = createSalesUserWithRoles([RoleEnum::OWNER->value, RoleEnum::OWNER_ADMIN->value]);
    $order = createSalesOrder();
    $order->owners()->sync([$ownerAdmin->id]);

    $response = $this
        ->actingAs($ownerAdmin)
        ->postJson(route('sales.assign_estimate', ['order' => $order->id]), [
            'schedule_appointment' => now()->addDay()->toDateTimeString(),
            'owner_ids' => [$ownerAdmin->id],
        ]);

    $response->assertOk();
    $response->assertJsonPath('order.status', OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value);
});
