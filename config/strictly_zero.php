<?php

return [
    'base_url' => env('STRICTLY_ZERO_BASE_URL', 'https://api.paywithzero.net'),
    'key_hash' => env('STRICTLY_ZERO_KEY_HASH'),
    'username' => env('STRICTLY_ZERO_USERNAME'),
    'password' => env('STRICTLY_ZERO_PASSWORD'),
    'webhook_username' => env('STRICTLY_ZERO_WEBHOOK_USERNAME'),
    'webhook_password' => env('STRICTLY_ZERO_WEBHOOK_PASSWORD'),
    'payment_link_path' => env('STRICTLY_ZERO_PAYMENT_LINK_PATH', '/v1/public/202104/payment-link'),
];
