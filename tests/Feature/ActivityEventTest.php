<?php

use App\Enum\RoleEnum;
use App\Jobs\SendGmailEmail;
use App\Models\CrmEvent;
use App\Models\CrmEventOccurrenceEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Queue::fake();
});

test('owner can create recurring weekly event', function () {
    Role::findOrCreate(RoleEnum::OWNER->value);

    $owner = User::factory()->create();
    $owner->assignRole(RoleEnum::OWNER->value);

    $startsAt = Carbon::parse('2026-08-18 10:00:00', config('app.timezone'));
    $endsAt = $startsAt->copy()->addHour();

    $response = $this
        ->actingAs($owner)
        ->postJson(route('activities.events.store'), [
            'host_id' => $owner->id,
            'order_id' => null,
            'client_id' => null,
            'title' => 'Weekly event',
            'starts_at' => $startsAt->toISOString(),
            'ends_at' => $endsAt->toISOString(),
            'status' => 'Scheduled',
            'is_repeating' => true,
            'recurrence_frequency' => 'weekly',
            'recurrence_interval' => 1,
            'recurrence_weekday' => 2,
            'recurrence_month_day' => null,
            'recurrence_ends_at' => null,
            'reminder_enabled' => true,
            'reminder_minutes_before' => 1440,
            'participants' => ['external@example.com'],
            'send_invitation' => false,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('event.is_repeating', true)
        ->assertJsonPath('event.recurrence_frequency', 'weekly')
        ->assertJsonPath('event.recurrence_weekday', 2);

    expect(CrmEvent::query()
        ->where('host_id', $owner->id)
        ->where('title', 'Weekly event')
        ->where('is_repeating', true)
        ->where('recurrence_frequency', 'weekly')
        ->exists())->toBeTrue();
});

test('recurring event reminders are sent once per occurrence', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-17 10:00:00', config('app.timezone')));

    $host = User::factory()->create();
    $event = CrmEvent::create([
        'host_id' => $host->id,
        'title' => 'Tuesday recurring event',
        'starts_at' => Carbon::parse('2026-08-18 10:00:00', config('app.timezone')),
        'ends_at' => Carbon::parse('2026-08-18 11:00:00', config('app.timezone')),
        'status' => 'Scheduled',
        'is_repeating' => true,
        'recurrence_frequency' => 'weekly',
        'recurrence_interval' => 1,
        'recurrence_weekday' => 2,
        'reminder_enabled' => true,
        'reminder_minutes_before' => 1440,
        'online_meeting' => false,
        'participants' => ['one@example.com', 'two@example.com'],
    ]);

    $this->artisan('crm-events:send-recurring-reminders')->assertSuccessful();
    $this->artisan('crm-events:send-recurring-reminders')->assertSuccessful();

    Queue::assertPushed(SendGmailEmail::class, 2);

    expect(CrmEventOccurrenceEmail::query()
        ->where('crm_event_id', $event->id)
        ->count())->toBe(1);

    Carbon::setTestNow();
});

test('owner can create event without assigned order for external participant', function () {
    Role::findOrCreate(RoleEnum::OWNER->value);

    $owner = User::factory()->create();
    $owner->assignRole(RoleEnum::OWNER->value);

    $startsAt = now()->addDay()->setTime(10, 0);
    $endsAt = (clone $startsAt)->addHour();

    $response = $this
        ->actingAs($owner)
        ->postJson(route('activities.events.store'), [
            'host_id' => $owner->id,
            'order_id' => null,
            'client_id' => null,
            'title' => 'External event',
            'starts_at' => $startsAt->toISOString(),
            'ends_at' => $endsAt->toISOString(),
            'status' => 'Scheduled',
            'is_repeating' => false,
            'reminder_enabled' => true,
            'reminder_minutes_before' => 15,
            'location' => null,
            'online_meeting' => false,
            'meeting_link' => null,
            'participants' => ['external@example.com'],
            'send_invitation' => false,
            'description' => 'Meeting with external participant.',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('event.host_id', $owner->id)
        ->assertJsonPath('event.order_id', null)
        ->assertJsonPath('event.participants.0', 'external@example.com');

    expect(CrmEvent::query()
        ->where('host_id', $owner->id)
        ->whereNull('order_id')
        ->where('title', 'External event')
        ->exists())->toBeTrue();
});
