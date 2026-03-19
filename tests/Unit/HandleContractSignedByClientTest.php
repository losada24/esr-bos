<?php

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Events\OrderStatusChanged;
use App\Jobs\SendGmailEmail;
use App\Listeners\HandleContractSignedByClient;
use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    config()->set('custom.mobile_onboarding_enabled', true);
});

test('it creates mobile onboarding when an order reaches review', function () {
    Bus::fake();

    $client = Client::factory()->create([
        'name' => 'Review Client',
        'email' => 'review-client@example.com',
    ]);

    $order = new Order();
    $order->setRelation('client', $client);

    $listener = new HandleContractSignedByClient();
    $listener->handle(new OrderStatusChanged($order, OrderStatusEnum::REVIEW->value));

    $user = User::where('email', 'review-client@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user?->hasRole(RoleEnum::CUSTOMER->value))->toBeTrue();

    $client->refresh();
    expect($client->mobile_user_id)->toBe($user?->id);

    Bus::assertDispatched(SendGmailEmail::class);
});

test('it does not create mobile onboarding when status is contract signed by client', function () {
    Bus::fake();

    $client = Client::factory()->create([
        'name' => 'Contract Client',
        'email' => 'contract-client@example.com',
    ]);

    $order = new Order();
    $order->setRelation('client', $client);

    $listener = new HandleContractSignedByClient();
    $listener->handle(new OrderStatusChanged($order, OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value));

    expect(User::where('email', 'contract-client@example.com')->exists())->toBeFalse();

    $client->refresh();
    expect($client->mobile_user_id)->toBeNull();

    Bus::assertNotDispatched(SendGmailEmail::class);
});
