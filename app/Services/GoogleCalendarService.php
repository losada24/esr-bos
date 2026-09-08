<?php

namespace App\Services;

use App\Contracts\GoogleCalendarGateway;
use App\Models\CrmEvent;
use App\Support\GoogleCalendarEventFactory;
use Google\Client;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Exception as GoogleServiceException;
use InvalidArgumentException;

class GoogleCalendarService implements GoogleCalendarGateway
{
    public function __construct(private readonly GoogleCalendarEventFactory $eventFactory) {}

    public function upsert(CrmEvent $event, string $userEmail, string $googleEventId): void
    {
        $service = $this->serviceFor($userEmail);
        $payload = $this->eventFactory->payload($event);
        $payload['id'] = $googleEventId;
        $googleEvent = new GoogleCalendarEvent($payload);

        try {
            $service->events->update('primary', $googleEventId, $googleEvent, ['sendUpdates' => 'none']);
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() !== 404) {
                throw $exception;
            }

            try {
                $service->events->insert('primary', $googleEvent, ['sendUpdates' => 'none']);
            } catch (GoogleServiceException $insertException) {
                if ((int) $insertException->getCode() !== 409) {
                    throw $insertException;
                }

                $service->events->update('primary', $googleEventId, $googleEvent, ['sendUpdates' => 'none']);
            }
        }
    }

    public function delete(string $userEmail, string $googleEventId): void
    {
        try {
            $this->serviceFor($userEmail)
                ->events
                ->delete('primary', $googleEventId, ['sendUpdates' => 'none']);
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() !== 404 && (int) $exception->getCode() !== 410) {
                throw $exception;
            }
        }
    }

    private function serviceFor(string $userEmail): GoogleCalendar
    {
        $credentials = $this->credentials();
        $client = new Client;
        $client->setAuthConfig($credentials);
        $client->setScopes([GoogleCalendar::CALENDAR_EVENTS]);
        $client->setSubject($userEmail);

        return new GoogleCalendar($client);
    }

    private function credentials(): array
    {
        $json = config('google_calendar.credentials');
        if (is_string($json) && trim($json) !== '') {
            $credentials = json_decode($json, true);
            if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                throw new InvalidArgumentException('Google Calendar credentials JSON is invalid.');
            }

            return $this->normalizeCredentials($credentials);
        }

        $credentials = config('google_calendar.service_account', []);
        if (
            ! is_array($credentials)
            || empty($credentials['project_id'])
            || empty($credentials['client_email'])
            || empty($credentials['private_key_id'])
            || empty($credentials['private_key'])
        ) {
            throw new InvalidArgumentException(
                'Google Calendar service account variables are incomplete. Verify PROJECT_ID, CLIENT_EMAIL, PRIVATE_KEY_ID and PRIVATE_KEY.'
            );
        }

        return $this->normalizeCredentials($credentials);
    }

    private function normalizeCredentials(array $credentials): array
    {
        $credentials['private_key'] = str_replace('\\n', "\n", (string) $credentials['private_key']);

        return array_filter($credentials, static fn ($value) => $value !== null && $value !== '');
    }
}
