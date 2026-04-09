<?php

use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Enum\TypeOfFinancing;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::findOrCreate(RoleEnum::OWNER_ADMIN->value);
});

function createOwnerAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::OWNER_ADMIN->value);

    return $user;
}

function createContractSignedTestOrder(Client $client, User $user): Order
{
    return Order::create([
        'client_id' => $client->id,
        'user_id' => $user->id,
        'name' => 'Client Email Preference Order',
        'order_number' => 'ORD-CONTRACT-001',
        'status' => OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
        'order_type' => OrderTypeEnum::RESIDENTIAL->value,
        'service' => ServiceEnum::INSTALLATION->value,
        'project_amount' => 10000,
    ]);
}

function contractSignedPayload(array $overrides = []): array
{
    return array_merge([
        'project_name' => 'Updated Project',
        'project_amount' => 10000,
        'job_address' => '123 Main St',
        'city' => 'Miami',
        'job_state' => 'FL',
        'job_zip' => '33101',
        'client_email_selection' => 'secondary-client@example.com',
        'name_check' => '1',
        'address_check' => '1',
        'amount_check' => '1',
        'email_check' => '1',
        'city_permits' => '0',
        'association_permits' => '0',
        'method_of_payment' => MethodOfPayment::FINANCED->value,
        'type_of_financing' => TypeOfFinancing::WELLS_FARGO->value,
        'attachments' => [
            UploadedFile::fake()->create('contract.pdf', 25, 'application/pdf'),
        ],
    ], $overrides);
}

test('contract signed stores an alternate client recipient without changing the primary client email', function () {
    Storage::fake('public');

    $user = createOwnerAdminUser();
    $client = Client::factory()->create([
        'email' => 'primary-client@example.com',
        'secondary_email' => 'secondary-client@example.com',
    ]);
    $order = createContractSignedTestOrder($client, $user);

    $response = $this
        ->actingAs($user)
        ->post(route('sales.assign_contract_signed', $order), contractSignedPayload());

    $response->assertOk()
        ->assertJsonPath('order.client_email_selection', 'secondary-client@example.com')
        ->assertJsonPath('order.contact_email', 'secondary-client@example.com')
        ->assertJsonPath('order.client_email_override', 'secondary-client@example.com')
        ->assertJsonPath('order.do_not_send_email', false);

    $order->refresh();
    $client->refresh();

    expect($order->client_email_override)->toBe('secondary-client@example.com');
    expect((bool) $order->do_not_send_email)->toBeFalse();
    expect($client->email)->toBe('primary-client@example.com');
});

test('contract signed can disable client emails for a specific order', function () {
    Storage::fake('public');

    $user = createOwnerAdminUser();
    $client = Client::factory()->create([
        'email' => 'primary-client@example.com',
        'secondary_email' => 'secondary-client@example.com',
    ]);
    $order = createContractSignedTestOrder($client, $user);

    $response = $this
        ->actingAs($user)
        ->post(route('sales.assign_contract_signed', $order), contractSignedPayload([
            'client_email_selection' => '__NONE__',
        ]));

    $response->assertOk()
        ->assertJsonPath('order.client_email_selection', '__NONE__')
        ->assertJsonPath('order.contact_email', null)
        ->assertJsonPath('order.client_email_override', null)
        ->assertJsonPath('order.do_not_send_email', true);

    $order->refresh();

    expect((bool) $order->do_not_send_email)->toBeTrue();
    expect($order->client_email_override)->toBeNull();
});
