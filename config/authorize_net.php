<?php

$environment = env('AUTHORIZE_NET_ENV', 'sandbox');
$isProduction = $environment === 'production';

return [
    'environment' => $environment,
    'api_login_id' => env('AUTHORIZE_NET_API_LOGIN_ID'),
    'transaction_key' => env('AUTHORIZE_NET_TRANSACTION_KEY'),
    'signature_key' => env('AUTHORIZE_NET_SIGNATURE_KEY'),
    'merchant_name' => env('AUTHORIZE_NET_MERCHANT_NAME', env('APP_NAME', 'Reylos Glass')),
    'api_url' => env(
        'AUTHORIZE_NET_API_URL',
        $isProduction
            ? 'https://api2.authorize.net/xml/v1/request.api'
            : 'https://apitest.authorize.net/xml/v1/request.api'
    ),
    'form_url' => env(
        'AUTHORIZE_NET_FORM_URL',
        $isProduction
            ? 'https://accept.authorize.net/payment/payment'
            : 'https://test.authorize.net/payment/payment'
    ),
];
