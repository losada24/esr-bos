<?php

use App\Actions\CreateClient;
use App\Enum\ContactSourceEnum;
use App\Models\User;
use Illuminate\Http\Request;

test('creating a client persists vip fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = Request::create('/client', 'POST', [
        'name' => 'VIP Client',
        'phone' => '3055550101',
        'source' => ContactSourceEnum::DIRECT_CALL->value,
        'vip_clients' => true,
        'vip_notes' => 'Priority scheduling requested.',
    ]);

    $client = app(CreateClient::class)->handle($request);

    expect($client->vip_clients)->toBeTrue()
        ->and($client->vip_notes)->toBe('Priority scheduling requested.');

    $this->assertDatabaseHas('clients', [
        'id' => $client->id,
        'vip_clients' => true,
        'vip_notes' => 'Priority scheduling requested.',
    ]);
});
