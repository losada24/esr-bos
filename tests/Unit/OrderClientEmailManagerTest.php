<?php

use App\Models\Client;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use App\Models\Source;
use App\Models\User;
use App\Support\OrderClientEmailDeliveryLogger;
use App\Support\OrderClientEmailManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it resolves the client recipient using order preferences', function () {
    $client = Client::factory()->create([
        'email' => 'primary-client@example.com',
        'secondary_email' => 'secondary-client@example.com',
    ]);
    $user = User::factory()->create();

    $order = Order::create([
        'client_id' => $client->id,
        'user_id' => $user->id,
        'name' => 'Recipient preference order',
        'order_number' => 'ORD-EMAIL-001',
        'status' => 'DRAFT',
    ]);

    $manager = app(OrderClientEmailManager::class);

    expect($manager->resolveRecipient($order))->toBe('primary-client@example.com');
    expect($manager->selectionForOrder($order))->toBe(OrderClientEmailManager::PRIMARY_SELECTION);

    $order->client_email_override = 'secondary-client@example.com';
    $order->save();

    expect($manager->resolveRecipient($order->fresh('client')))->toBe('secondary-client@example.com');
    expect($manager->selectionForOrder($order->fresh('client')))->toBe('secondary-client@example.com');

    $order->do_not_send_email = true;
    $order->save();

    expect($manager->resolveRecipient($order->fresh('client')))->toBeNull();
    expect($manager->selectionForOrder($order->fresh('client')))->toBe(OrderClientEmailManager::NONE_SELECTION);
});

test('it builds unique client email options from associated records', function () {
    $client = Client::factory()->create([
        'email' => 'primary-client@example.com',
        'secondary_email' => 'secondary-client@example.com',
    ]);

    $selectedCompany = CompanyContact::create([
        'name' => 'Selected Company',
        'email' => 'selected-company@example.com',
    ]);

    $associatedCompany = CompanyContact::create([
        'name' => 'Associated Company',
        'email' => 'associated-company@example.com',
    ]);

    $duplicateEmailCompany = CompanyContact::create([
        'name' => 'Duplicate Company',
        'email' => 'secondary-client@example.com',
    ]);

    $client->companyContacts()->attach($associatedCompany->id, ['is_primary' => true]);
    $client->companyContacts()->attach($duplicateEmailCompany->id, ['is_primary' => false]);

    $manager = app(OrderClientEmailManager::class);
    $options = $manager->optionsForContext($client->fresh('companyContacts'), $selectedCompany);

    expect(collect($options)->pluck('value')->all())->toBe([
        'primary-client@example.com',
        'secondary-client@example.com',
        'selected-company@example.com',
        'associated-company@example.com',
    ]);

    expect(collect($options)->firstWhere('value', 'primary-client@example.com')['is_primary'])->toBeTrue();
});

test('it falls back to the primary commercial pair client email when the order client is not set', function () {
    $client = Client::factory()->create([
        'email' => 'primary-commercial@example.com',
    ]);
    $company = CompanyContact::create([
        'name' => 'Commercial Company',
        'email' => 'commercial-company@example.com',
    ]);
    $source = Source::create([
        'name' => 'Commercial Source',
    ]);
    $user = User::factory()->create();

    $order = Order::create([
        'client_id' => null,
        'user_id' => $user->id,
        'name' => 'Commercial fallback order',
        'order_number' => 'ORD-EMAIL-002',
        'status' => 'DRAFT',
        'order_type' => 'COMMERCIAL',
    ]);

    OrderCompanyContact::create([
        'order_id' => $order->id,
        'company_contact_id' => $company->id,
        'client_id' => $client->id,
        'source_id' => $source->id,
        'is_selected' => false,
    ]);

    $manager = app(OrderClientEmailManager::class);

    expect($manager->resolveRecipient($order->fresh()))->toBe('primary-commercial@example.com');
    expect($manager->optionsForOrder($order->fresh()))
        ->toBeArray()
        ->and(collect($manager->optionsForOrder($order->fresh()))->pluck('value')->all())
        ->toContain('primary-commercial@example.com', 'commercial-company@example.com');
});

test('it validates the client email selection against a client and company context', function () {
    $client = Client::factory()->create([
        'email' => 'primary-client@example.com',
        'secondary_email' => 'secondary-client@example.com',
    ]);
    $company = CompanyContact::create([
        'name' => 'Selected Company',
        'email' => 'selected-company@example.com',
    ]);

    $manager = app(OrderClientEmailManager::class);

    expect($manager->validateSelectionForContext($client, OrderClientEmailManager::PRIMARY_SELECTION, $company))->toBeNull();
    expect($manager->validateSelectionForContext($client, 'selected-company@example.com', $company))->toBeNull();
    expect($manager->validateSelectionForContext($client, 'invalid@example.com', $company))
        ->toBe('The selected client email is not available for this order.');
});

test('it logs a timeline event when the client email delivery changes', function () {
    $client = Client::factory()->create([
        'email' => 'primary-client@example.com',
        'secondary_email' => 'secondary-client@example.com',
    ]);
    $user = User::factory()->create();

    $order = Order::create([
        'client_id' => $client->id,
        'user_id' => $user->id,
        'name' => 'Email delivery timeline order',
        'order_number' => 'ORD-EMAIL-003',
        'status' => 'DRAFT',
    ]);

    $deliveryLogger = app(OrderClientEmailDeliveryLogger::class);
    $manager = app(OrderClientEmailManager::class);
    $beforeState = $deliveryLogger->capture($order);

    $manager->applySelection($order, 'secondary-client@example.com');
    $order->save();

    $deliveryLogger->logIfChanged($order->fresh('client'), $beforeState);

    $event = $order->fresh('financialEvents.user')->financialEvents()->latest('id')->first();

    expect($event)->not->toBeNull();
    expect($event->event_type)->toBe('CLIENT_EMAIL_DELIVERY_UPDATED');
    expect($event->summary)->toBe('Client email delivery updated');
    expect($event->details['before_delivery'])->toBe('Primary client email (primary-client@example.com)');
    expect($event->details['after_delivery'])->toBe('secondary-client@example.com');
});
