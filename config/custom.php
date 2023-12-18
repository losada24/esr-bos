<?php

return [
  'recaptcha_site_key' => env('RECAPTCHA_SITE_KEY'),
  'recaptcha_secret_key' => env('RECAPTCHA_SECRET_KEY'),
  'admin_emails' => explode(',', env('ADMIN_EMAILS')),
  'work_bill' => env('WORK_BILL'),
  'rent_bill' => env('RENT_BILL'),
  'electricity_bill' => env('ELECTRICITY_BILL'),
  'internet_bill' => env('INTERNET_BILL'),
  'other_bill' => env('OTHER_BILL'),
  'screen_price_by_sqft' => env('SCREEN_PRICE_BY_SQFT'),
  'packing' => env('PACKING'),
];
