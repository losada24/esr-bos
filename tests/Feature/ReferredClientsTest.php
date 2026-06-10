<?php

use App\Enum\RoleEnum;
use App\Models\Client;
use App\Models\Referral;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function createReferralForUser(User $user, string $name, string $email): Referral
{
    $referral = Referral::query()->create([
        'name' => $name,
        'email' => $email,
        'phone' => '3050000000',
        'type' => 'User',
        'user_id' => $user->id,
    ]);

    Client::query()->create([
        'name' => $name . ' Client',
        'email' => 'client+' . $referral->id . '@example.com',
        'phone' => '7860000000',
        'referral_id' => $referral->id,
    ]);

    return $referral;
}

test('frontdesk admins can see referrals from all users', function () {
    Role::findOrCreate(RoleEnum::FRONTDESK_ADMIN->value);

    $frontdeskAdmin = User::factory()->create();
    $frontdeskAdmin->assignRole(RoleEnum::FRONTDESK_ADMIN->value);

    $firstOwner = User::factory()->create(['email' => 'first-owner@example.com']);
    $secondOwner = User::factory()->create(['email' => 'second-owner@example.com']);

    $firstReferral = createReferralForUser($firstOwner, 'First Referrer', 'first-referrer@example.com');
    $secondReferral = createReferralForUser($secondOwner, 'Second Referrer', 'second-referrer@example.com');

    $response = $this
        ->actingAs($frontdeskAdmin)
        ->withHeader('X-Inertia', 'true')
        ->get(route('user.referred-clients'));

    $response->assertOk();
    $response->assertJsonPath('component', 'User/ReferredClients');
    $response->assertJsonPath('props.can_view_all_referrals', true);

    $referralIds = collect($response->json('props.referrals.data'))
        ->pluck('id')
        ->all();

    expect($referralIds)->toContain($firstReferral->id, $secondReferral->id);
});

test('non privileged users only see their own referrals', function () {
    $authenticatedUser = User::factory()->create(['email' => 'owner@example.com']);
    $otherUser = User::factory()->create(['email' => 'other-owner@example.com']);

    $ownReferral = createReferralForUser($authenticatedUser, 'Own Referrer', 'own-referrer@example.com');
    $otherReferral = createReferralForUser($otherUser, 'Other Referrer', 'other-referrer@example.com');

    $response = $this
        ->actingAs($authenticatedUser)
        ->withHeader('X-Inertia', 'true')
        ->get(route('user.referred-clients'));

    $response->assertOk();
    $response->assertJsonPath('props.can_view_all_referrals', false);

    $referralIds = collect($response->json('props.referrals.data'))
        ->pluck('id')
        ->all();

    expect($referralIds)->toContain($ownReferral->id);
    expect($referralIds)->not->toContain($otherReferral->id);
});
