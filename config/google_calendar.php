<?php

$credentials = env('GOOGLE_CALENDAR_CREDENTIALS_JSON');
if (! is_string($credentials) || trim($credentials) === '') {
    $credentials = env('GOOGLE_CALENDAR_CREDENTIALS');
}

return [
    'enabled' => filter_var(env('GOOGLE_CALENDAR_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'credentials' => $credentials,
    'service_account' => [
        'type' => 'service_account',
        'project_id' => env('GOOGLE_CALENDAR_PROJECT_ID'),
        'private_key_id' => env('GOOGLE_CALENDAR_PRIVATE_KEY_ID'),
        'private_key' => env('GOOGLE_CALENDAR_PRIVATE_KEY'),
        'client_email' => env('GOOGLE_CALENDAR_CLIENT_EMAIL'),
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    ],
    'internal_domains' => array_values(array_filter(array_map(
        static fn (string $domain) => strtolower(trim($domain)),
        explode(',', (string) env('GOOGLE_CALENDAR_INTERNAL_DOMAINS', 'reylosglass.com,esrimpact.com'))
    ))),
    'timezone' => env('GOOGLE_CALENDAR_TIMEZONE', 'America/New_York'),
    'event_source' => env('GOOGLE_CALENDAR_EVENT_SOURCE', 'ESR BOS'),
];
