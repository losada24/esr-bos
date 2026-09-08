<?php

use App\Contracts\GoogleCalendarGateway;
use App\Http\Controllers\ActivityController;
use App\Jobs\SendGmailEmail;
use App\Mail\CrmEventInvitation;
use App\Models\CrmEvent;
use App\Models\CrmEventGoogleCalendarSync;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Support\GoogleCalendarEventFactory;
use App\Support\GoogleCalendarInternalDomainMatcher;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');
    config()->set('google_calendar.enabled', true);
    config()->set('google_calendar.internal_domains', ['reylosglass.com', 'esrimpact.com']);
    config()->set('google_calendar.timezone', 'America/New_York');
    config()->set('google_calendar.event_source', 'ESR BOS');
    DB::purge('sqlite');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->unique();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('crm_events', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('host_id');
        $table->unsignedBigInteger('order_id')->nullable();
        $table->unsignedBigInteger('client_id')->nullable();
        $table->string('title');
        $table->dateTime('starts_at');
        $table->dateTime('ends_at');
        $table->string('status')->default('Scheduled');
        $table->boolean('is_repeating')->default(false);
        $table->string('recurrence_frequency')->nullable();
        $table->unsignedTinyInteger('recurrence_interval')->nullable();
        $table->unsignedTinyInteger('recurrence_weekday')->nullable();
        $table->unsignedTinyInteger('recurrence_month_day')->nullable();
        $table->date('recurrence_ends_at')->nullable();
        $table->boolean('reminder_enabled')->default(false);
        $table->unsignedInteger('reminder_minutes_before')->nullable();
        $table->string('location')->nullable();
        $table->boolean('online_meeting')->default(false);
        $table->string('meeting_link')->nullable();
        $table->json('participants')->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('crm_event_google_calendar_syncs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('crm_event_id');
        $table->string('user_email', 320);
        $table->string('google_event_id', 1024);
        $table->string('status', 32)->default('pending');
        $table->text('last_error')->nullable();
        $table->timestamp('synced_at')->nullable();
        $table->timestamps();
        $table->unique(['crm_event_id', 'user_email']);
    });
});

test('only exact corporate domains are internal', function () {
    $matcher = new GoogleCalendarInternalDomainMatcher(['reylosglass.com', 'esrimpact.com']);

    expect($matcher->isInternal('person@reylosglass.com'))->toBeTrue()
        ->and($matcher->isInternal('PERSON@ESRIMPACT.COM'))->toBeTrue()
        ->and($matcher->isInternal('person@reylosglass.com.example.org'))->toBeFalse()
        ->and($matcher->isInternal('person@fake-reylosglass.com'))->toBeFalse()
        ->and($matcher->isInternal('person@example.com'))->toBeFalse();
});

test('split environment variables build valid service account credentials', function () {
    config()->set('google_calendar.credentials', null);
    config()->set('google_calendar.service_account', [
        'type' => 'service_account',
        'project_id' => 'calendar-project',
        'private_key_id' => 'private-key-id',
        'private_key' => '-----BEGIN PRIVATE KEY-----\\nTEST\\n-----END PRIVATE KEY-----\\n',
        'client_email' => 'calendar-service@calendar-project.iam.gserviceaccount.com',
        'client_id' => '123456789',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
    ]);

    $service = new GoogleCalendarService(new GoogleCalendarEventFactory('America/New_York', 'ESR BOS'));
    $method = new ReflectionMethod($service, 'credentials');
    $method->setAccessible(true);
    $credentials = $method->invoke($service);

    expect($credentials['type'])->toBe('service_account')
        ->and($credentials['project_id'])->toBe('calendar-project')
        ->and($credentials['client_email'])->toBe('calendar-service@calendar-project.iam.gserviceaccount.com')
        ->and($credentials['private_key'])->toContain("BEGIN PRIVATE KEY-----\nTEST\n-----END PRIVATE KEY");
});

test('creation email dispatch reaches everyone and only externals get manual calendar options', function () {
    Queue::fake();
    $host = new User(['name' => 'Host', 'email' => 'host@reylosglass.com']);
    $event = new CrmEvent([
        'title' => 'Audience test',
        'starts_at' => Carbon::parse('2026-09-10 10:00:00', 'America/New_York'),
        'ends_at' => Carbon::parse('2026-09-10 11:00:00', 'America/New_York'),
        'participants' => ['one@reylosglass.com', 'two@esrimpact.com', 'outside@example.com'],
    ]);
    $event->id = 100;
    $event->setRelation('host', $host);

    $method = new ReflectionMethod(ActivityController::class, 'sendEventInvitationsIfRequested');
    $method->setAccessible(true);
    $method->invoke(app(ActivityController::class), $event, true);

    Queue::assertPushed(SendGmailEmail::class, 3);
    $mailByRecipient = Queue::pushed(SendGmailEmail::class)
        ->mapWithKeys(function (SendGmailEmail $job) {
            $reflection = new ReflectionClass($job);
            $email = $reflection->getProperty('email');
            $email->setAccessible(true);
            $mailable = $reflection->getProperty('mailable');
            $mailable->setAccessible(true);

            return [$email->getValue($job) => $mailable->getValue($job)];
        });

    foreach (['one@reylosglass.com', 'two@esrimpact.com'] as $email) {
        /** @var CrmEventInvitation $mailable */
        $mailable = $mailByRecipient->get($email);
        $content = $mailable->content();
        $html = View::make($content->view, $content->with)->render();

        expect($mailable->attachments())->toBe([])
            ->and($content->with['googleCalendarUrl'])->toBeNull()
            ->and($html)->not->toContain('Add to Google Calendar');
    }

    /** @var CrmEventInvitation $externalMail */
    $externalMail = $mailByRecipient->get('outside@example.com');
    $externalContent = $externalMail->content();
    $externalHtml = View::make($externalContent->view, $externalContent->with)->render();

    expect($externalMail->attachments())->toHaveCount(1)
        ->and($externalContent->with['googleCalendarUrl'])->toStartWith('https://www.google.com/calendar/render')
        ->and($externalHtml)->toContain('Add to Google Calendar');
});

test('calendar synchronization is dispatched to the existing server queue', function () {
    Queue::fake();
    $event = new CrmEvent;
    $event->id = 321;

    $method = new ReflectionMethod(ActivityController::class, 'queueGoogleCalendarSync');
    $method->setAccessible(true);
    $method->invoke(app(ActivityController::class), $event);

    Queue::assertPushed(\App\Jobs\SyncCrmEventToGoogleCalendars::class, function ($job) {
        return $job->crmEventId === 321 && $job->queue === 'emails' && $job->afterCommit === true;
    });
});

test('calendar payload is deterministic suffixed and never contains attendees', function () {
    $event = new CrmEvent([
        'title' => 'Planning Session',
        'starts_at' => Carbon::parse('2026-09-10 10:00:00', 'America/New_York'),
        'ends_at' => Carbon::parse('2026-09-10 11:00:00', 'America/New_York'),
        'reminder_enabled' => true,
        'reminder_minutes_before' => 30,
        'online_meeting' => true,
        'meeting_link' => 'https://meet.google.com/example',
    ]);
    $event->id = 99;
    $factory = new GoogleCalendarEventFactory('America/New_York', 'ESR BOS');
    $payload = $factory->payload($event);

    expect($payload['summary'])->toBe('Planning Session — ESR BOS')
        ->and($payload)->not->toHaveKey('attendees')
        ->and($payload['reminders']['overrides'][0]['minutes'])->toBe(30)
        ->and($factory->googleTitle('Planning Session — ESR BOS'))->toBe('Planning Session — ESR BOS')
        ->and($factory->deterministicId(99))->toMatch('/^[0-9a-v]{5,1024}$/')
        ->and($event->title)->toBe('Planning Session');
});

test('sync creates updates removes users cancels and keeps one tracking row per user', function () {
    $firstHost = User::create(['name' => 'Host', 'email' => 'host@reylosglass.com']);
    $secondHost = User::create(['name' => 'Replacement', 'email' => 'replacement@esrimpact.com']);
    $event = CrmEvent::create([
        'host_id' => $firstHost->id,
        'title' => 'Initial title',
        'starts_at' => Carbon::parse('2026-09-10 10:00:00', 'America/New_York'),
        'ends_at' => Carbon::parse('2026-09-10 11:00:00', 'America/New_York'),
        'status' => 'Scheduled',
        'participants' => ['member@esrimpact.com', 'outside@example.com'],
    ]);
    $gateway = new class implements GoogleCalendarGateway
    {
        public array $upserts = [];

        public array $deletes = [];

        public function upsert(CrmEvent $event, string $userEmail, string $googleEventId): void
        {
            $this->upserts[] = compact('userEmail', 'googleEventId') + ['title' => $event->title];
        }

        public function delete(string $userEmail, string $googleEventId): void
        {
            $this->deletes[] = compact('userEmail', 'googleEventId');
        }
    };
    $matcher = new GoogleCalendarInternalDomainMatcher(['reylosglass.com', 'esrimpact.com']);
    $factory = new GoogleCalendarEventFactory('America/New_York', 'ESR BOS');
    $job = new \App\Jobs\SyncCrmEventToGoogleCalendars($event->id);

    $job->handle($gateway, $matcher, $factory);
    $job->handle($gateway, $matcher, $factory);

    expect(CrmEventGoogleCalendarSync::query()->count())->toBe(2)
        ->and(CrmEventGoogleCalendarSync::query()->where('user_email', 'outside@example.com')->exists())->toBeFalse()
        ->and(CrmEventGoogleCalendarSync::query()->pluck('google_event_id')->unique()->count())->toBe(1);

    $event->update([
        'title' => 'Updated title',
        'starts_at' => Carbon::parse('2026-09-11 12:00:00', 'America/New_York'),
        'ends_at' => Carbon::parse('2026-09-11 13:30:00', 'America/New_York'),
        'participants' => ['new.member@reylosglass.com', 'outside@example.com'],
    ]);
    $job->handle($gateway, $matcher, $factory);

    expect(CrmEventGoogleCalendarSync::query()->where('user_email', 'member@esrimpact.com')->value('status'))
        ->toBe(CrmEventGoogleCalendarSync::STATUS_DELETED)
        ->and(CrmEventGoogleCalendarSync::query()->where('user_email', 'new.member@reylosglass.com')->value('status'))
        ->toBe(CrmEventGoogleCalendarSync::STATUS_SYNCED)
        ->and($event->fresh()->title)->toBe('Updated title');

    $event->update(['host_id' => $secondHost->id]);
    $job->handle($gateway, $matcher, $factory);
    expect(CrmEventGoogleCalendarSync::query()->where('user_email', 'host@reylosglass.com')->value('status'))
        ->toBe(CrmEventGoogleCalendarSync::STATUS_DELETED);

    $event->update(['status' => 'Cancelled']);
    $job->handle($gateway, $matcher, $factory);

    expect(CrmEventGoogleCalendarSync::query()->where('status', '!=', CrmEventGoogleCalendarSync::STATUS_DELETED)->count())
        ->toBe(0)
        ->and(collect($gateway->upserts)->pluck('userEmail')->contains('outside@example.com'))->toBeFalse();
});
