<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

test('the shared frontdesk role flag does not become a persistent user attribute', function () {
    $user = new User;
    $user->forceFill([
        'id' => 39,
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->setRelation('roles', new Collection);
    $user->setRelation('permissions', new Collection);

    $request = Request::create('/profile');
    $request->setUserResolver(fn () => $user);

    $shared = (new HandleInertiaRequests)->share($request);

    expect($shared['auth']['user']['has_frontdesk_admin_role'])->toBeFalse()
        ->and($user->getAttributes())->not->toHaveKey('has_frontdesk_admin_role');
});

test('the shared frontdesk role flag does not break password updates', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
    ]);
    DB::purge('sqlite');

    Schema::connection('sqlite')->create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    $user = User::query()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('current-password'),
    ]);
    $user->setRelation('roles', new Collection);
    $user->setRelation('permissions', new Collection);

    $response = $this
        ->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'current-password',
            'password' => 'updated-password',
            'password_confirmation' => 'updated-password',
        ]);

    $response->assertSessionHasNoErrors();

    expect(Hash::check('updated-password', $user->fresh()->password))->toBeTrue();
});
