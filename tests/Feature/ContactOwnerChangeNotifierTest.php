<?php

use App\Enum\RoleEnum;
use App\Enum\StatusUserEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\ContactOwnerAssigned;
use App\Models\Client;
use App\Models\User;
use App\Support\ContactOwnerChangeNotifier;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;

function contactNotificationJobData(SendGmailEmail $job): array
{
    $reflection = new ReflectionClass($job);

    $emailProperty = $reflection->getProperty('email');
    $emailProperty->setAccessible(true);

    $mailableProperty = $reflection->getProperty('mailable');
    $mailableProperty->setAccessible(true);

    return [
        $emailProperty->getValue($job),
        $mailableProperty->getValue($job),
    ];
}

function createContactNotificationClient(array $attributes = []): Client
{
    return Client::create(array_merge([
        'name' => 'Notification Contact',
        'phone' => '3055550101',
        'email' => 'notification-contact@example.com',
        'source' => 'DIRECT CALL',
    ], $attributes));
}

beforeEach(function () {
    Bus::fake();
    Role::findOrCreate(RoleEnum::OWNER->value);
    Role::findOrCreate(RoleEnum::OWNER_ADMIN->value);
});

test('it emails the assigned owner and every owner admin for a new contact', function () {
    $owner = User::factory()->create([
        'name' => 'Assigned Owner',
        'email' => 'assigned-owner@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);
    $owner->assignRole(RoleEnum::OWNER->value);

    $firstAdmin = User::factory()->create([
        'name' => 'First Admin',
        'email' => 'first-admin@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);
    $firstAdmin->assignRole(RoleEnum::OWNER_ADMIN->value);

    $secondAdmin = User::factory()->create([
        'name' => 'Second Admin',
        'email' => 'second-admin@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);
    $secondAdmin->assignRole(RoleEnum::OWNER_ADMIN->value);

    $client = createContactNotificationClient([
        'name' => 'Complete Contact',
        'user_id' => $owner->id,
    ]);

    app(ContactOwnerChangeNotifier::class)->notify(
        $client,
        null,
        $owner->id,
        true
    );

    Bus::assertDispatchedTimes(SendGmailEmail::class, 3);

    foreach ([
        'assigned-owner@example.com',
        'first-admin@example.com',
        'second-admin@example.com',
    ] as $expectedEmail) {
        Bus::assertDispatched(SendGmailEmail::class, function (SendGmailEmail $job) use ($expectedEmail) {
            [$email, $mailable] = contactNotificationJobData($job);

            return $email === $expectedEmail
                && $mailable instanceof ContactOwnerAssigned
                && $job->queue === 'emails';
        });
    }
});

test('it does not duplicate the email when the assigned owner is also an owner admin', function () {
    $ownerAdmin = User::factory()->create([
        'email' => 'owner-admin@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);
    $ownerAdmin->assignRole([
        RoleEnum::OWNER->value,
        RoleEnum::OWNER_ADMIN->value,
    ]);

    $client = createContactNotificationClient(['user_id' => $ownerAdmin->id]);

    app(ContactOwnerChangeNotifier::class)->notify(
        $client,
        null,
        $ownerAdmin->id,
        true
    );

    Bus::assertDispatchedTimes(SendGmailEmail::class, 1);
});

test('it sends no email when an existing contact keeps the same owner', function () {
    $owner = User::factory()->create([
        'email' => 'same-owner@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);
    $owner->assignRole(RoleEnum::OWNER->value);

    $client = createContactNotificationClient(['user_id' => $owner->id]);

    app(ContactOwnerChangeNotifier::class)->notify(
        $client,
        $owner->id,
        $owner->id
    );

    Bus::assertNotDispatched(SendGmailEmail::class);
});

test('the contact email renders the complete contact details', function () {
    $owner = User::factory()->create([
        'name' => 'Contact Owner',
        'email' => 'contact-owner@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);

    $creator = User::factory()->create([
        'name' => 'Contact Creator',
        'email' => 'creator@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);

    $client = createContactNotificationClient([
        'name' => 'Rendered Contact',
        'phone' => '3055550101',
        'email' => 'contact@example.com',
        'user_id' => $owner->id,
        'created_by_user_id' => $creator->id,
        'contact_type' => 'RESIDENTIAL CONTACT',
        'source' => 'DIRECT CALL',
        'vip_clients' => true,
        'vip_notes' => 'Priority contact',
    ]);

    $html = (new ContactOwnerAssigned($client, 'Recipient Name', true))->render();

    expect($html)
        ->toContain('Rendered Contact')
        ->toContain('3055550101')
        ->toContain('contact@example.com')
        ->toContain('Contact Owner')
        ->toContain('Contact Creator')
        ->toContain('Priority contact')
        ->toContain('Open contact');
});
