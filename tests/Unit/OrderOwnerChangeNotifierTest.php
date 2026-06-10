<?php

use App\Jobs\SendGmailEmail;
use App\Mail\OrderOwnerAdditionalOwnerAssigned;
use App\Mail\OrderOwnerAssigned;
use App\Mail\OrderOwnerUnassigned;
use App\Models\Client;
use App\Models\DurationOfWork;
use App\Models\Order;
use App\Models\TravelCost;
use App\Models\TypeOfHousing;
use App\Models\TypeOfWork;
use App\Models\User;
use App\Support\OrderOwnerChangeNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function createOwnerNotificationOrder(): Order
{
    $client = Client::factory()->create();
    $installer = User::factory()->create(['email' => 'installer+' . uniqid() . '@example.com']);
    $supervisor = User::factory()->create(['email' => 'supervisor+' . uniqid() . '@example.com']);
    $creator = User::factory()->create(['email' => 'creator+' . uniqid() . '@example.com']);

    $typeOfWork = TypeOfWork::create(['name' => 'Windows']);
    $typeOfHousing = TypeOfHousing::create(['name' => 'Residential']);
    $travelCost = TravelCost::create(['name' => 'Local', 'price' => 0]);
    $durationOfWork = DurationOfWork::create(['name' => 'One day', 'price' => 0, 'number_of_day' => 1]);

    return Order::unguarded(fn () => Order::create([
        'order_number' => 'ORD-' . uniqid(),
        'name' => 'Owner Notification Test Order',
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
    ]));
}

function dispatchedEmailMatches(SendGmailEmail $job, string $expectedEmail, string $expectedMailable): bool
{
    $reflection = new ReflectionClass($job);

    $emailProperty = $reflection->getProperty('email');
    $emailProperty->setAccessible(true);
    $email = $emailProperty->getValue($job);

    $mailableProperty = $reflection->getProperty('mailable');
    $mailableProperty->setAccessible(true);
    $mailable = $mailableProperty->getValue($job);

    return $email === $expectedEmail && $mailable instanceof $expectedMailable;
}

beforeEach(function () {
    Bus::fake();
    Event::fake();
});

test('it sends reassigned and removed emails when the owner list is replaced', function () {
    $order = createOwnerNotificationOrder();
    $oldOwner = User::factory()->create(['name' => 'Old Owner', 'email' => 'old-owner@example.com']);
    $newOwner = User::factory()->create(['name' => 'New Owner', 'email' => 'new-owner@example.com']);

    $order->owners()->sync([$oldOwner->id]);

    $notifier = app(OrderOwnerChangeNotifier::class);

    $order->owners()->sync([$newOwner->id]);
    $notifier->notify($order, [$oldOwner->id], [$newOwner->id]);

    Bus::assertDispatchedTimes(SendGmailEmail::class, 2);
    Bus::assertDispatched(SendGmailEmail::class, fn (SendGmailEmail $job) => dispatchedEmailMatches($job, 'new-owner@example.com', OrderOwnerAssigned::class));
    Bus::assertDispatched(SendGmailEmail::class, fn (SendGmailEmail $job) => dispatchedEmailMatches($job, 'old-owner@example.com', OrderOwnerUnassigned::class));
});

test('it notifies the retained owner when another owner is added', function () {
    $order = createOwnerNotificationOrder();
    $existingOwner = User::factory()->create(['name' => 'Existing Owner', 'email' => 'existing-owner@example.com']);
    $newOwner = User::factory()->create(['name' => 'New Owner', 'email' => 'new-owner@example.com']);

    $order->owners()->sync([$existingOwner->id]);

    $notifier = app(OrderOwnerChangeNotifier::class);

    $order->owners()->sync([$existingOwner->id, $newOwner->id]);
    $notifier->notify($order, [$existingOwner->id], [$existingOwner->id, $newOwner->id]);

    Bus::assertDispatchedTimes(SendGmailEmail::class, 2);
    Bus::assertDispatched(SendGmailEmail::class, fn (SendGmailEmail $job) => dispatchedEmailMatches($job, 'new-owner@example.com', OrderOwnerAssigned::class));
    Bus::assertDispatched(SendGmailEmail::class, fn (SendGmailEmail $job) => dispatchedEmailMatches($job, 'existing-owner@example.com', OrderOwnerAdditionalOwnerAssigned::class));
});

test('it does not send emails when the owner set does not change', function () {
    $order = createOwnerNotificationOrder();
    $firstOwner = User::factory()->create(['email' => 'first-owner@example.com']);
    $secondOwner = User::factory()->create(['email' => 'second-owner@example.com']);

    $order->owners()->sync([$firstOwner->id, $secondOwner->id]);

    $notifier = app(OrderOwnerChangeNotifier::class);
    $notifier->notify($order, [$firstOwner->id, $secondOwner->id], [$secondOwner->id, $firstOwner->id, $firstOwner->id]);

    Bus::assertNotDispatched(SendGmailEmail::class);
});
