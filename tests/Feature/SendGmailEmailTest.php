<?php

use App\Enum\StatusUserEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\NewUserRegistration;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Support\Facades\Bus;

function registrationEmail(string $email): NewUserRegistration
{
    return new NewUserRegistration('Test User', $email, 'password');
}

test('it does not send email to an inactive system user', function () {
    User::factory()->create([
        'email' => 'inactive@example.com',
        'status' => StatusUserEnum::INACTIVE->value,
    ]);

    $gmailService = Mockery::mock(GmailService::class);
    $gmailService->shouldNotReceive('sendEmail');

    (new SendGmailEmail('inactive@example.com', registrationEmail('inactive@example.com')))
        ->handle($gmailService);
});

test('it does not dispatch email to an inactive system user', function () {
    Bus::fake();

    User::factory()->create([
        'email' => 'inactive@example.com',
        'status' => StatusUserEnum::INACTIVE->value,
    ]);

    SendGmailEmail::dispatch('inactive@example.com', registrationEmail('inactive@example.com'))
        ->onQueue('emails');

    Bus::assertNotDispatched(SendGmailEmail::class);
});

test('it dispatches email to an active system user', function () {
    Bus::fake();

    User::factory()->create([
        'email' => 'active@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);

    SendGmailEmail::dispatch('active@example.com', registrationEmail('active@example.com'))
        ->onQueue('emails');

    Bus::assertDispatched(SendGmailEmail::class);
});

test('it sends email to an active system user', function () {
    User::factory()->create([
        'email' => 'active@example.com',
        'status' => StatusUserEnum::ACTIVE->value,
    ]);

    $mailable = registrationEmail('active@example.com');
    $gmailService = Mockery::mock(GmailService::class);
    $gmailService->shouldReceive('sendEmail')
        ->once()
        ->with('active@example.com', null, $mailable);

    (new SendGmailEmail('active@example.com', $mailable))->handle($gmailService);
});

test('it sends email when the recipient is not a system user', function () {
    $mailable = registrationEmail('external@example.com');
    $gmailService = Mockery::mock(GmailService::class);
    $gmailService->shouldReceive('sendEmail')
        ->once()
        ->with('external@example.com', null, $mailable);

    (new SendGmailEmail('external@example.com', $mailable))->handle($gmailService);
});

test('it removes inactive system users from multiple recipients', function () {
    User::factory()->create([
        'email' => 'inactive@example.com',
        'status' => StatusUserEnum::INACTIVE->value,
    ]);

    $mailable = registrationEmail('external@example.com');
    $gmailService = Mockery::mock(GmailService::class);
    $gmailService->shouldReceive('sendEmail')
        ->once()
        ->with(['external@example.com'], null, $mailable);

    (new SendGmailEmail(
        ['inactive@example.com', 'external@example.com'],
        $mailable
    ))->handle($gmailService);
});
