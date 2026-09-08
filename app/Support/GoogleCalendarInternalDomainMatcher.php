<?php

namespace App\Support;

class GoogleCalendarInternalDomainMatcher
{
    /** @var array<string, true> */
    private array $domains;

    public function __construct(?array $domains = null)
    {
        $this->domains = collect($domains ?? config('google_calendar.internal_domains', []))
            ->filter(fn ($domain) => is_string($domain) && trim($domain) !== '')
            ->mapWithKeys(fn (string $domain) => [strtolower(trim($domain)) => true])
            ->all();
    }

    public function isInternal(?string $email): bool
    {
        if (! is_string($email)) {
            return false;
        }

        $normalized = strtolower(trim($email));
        if (! filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $separator = strrpos($normalized, '@');
        if ($separator === false) {
            return false;
        }

        return isset($this->domains[substr($normalized, $separator + 1)]);
    }
}
