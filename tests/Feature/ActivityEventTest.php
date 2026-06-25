<?php

use App\Enum\RoleEnum;
use App\Models\CrmEvent;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Queue::fake();
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
