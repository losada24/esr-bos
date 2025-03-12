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
  'address_required_after_amount' => env('ADDRESS_REQUIRED_AFTER_AMOUNT'),
  'labels_images_path' => env('LABELS_IMAGES_PATH'),
  'corner_silicone' => env('CORNER_SILICONE'),
  'muntin_price_by_sqft' => env('MUNTIN_PRICE_BY_SQFT'),
  'anchors_price' => env('ANCHORS_PRICE'),
  'installation_team_expiration_documents_email' => env('INSTALLATION_TEAM__EXPIRATION_EMAILS'),
  'google_mail_api_client_id' => env('GOOGLE_MAIL_API_CLIENT_ID'),
  'google_mail_api_client_secret' => env('GOOGLE_MAIL_API_CLIENT_SECRET'),
  'google_mail_refresh_token' => env('GOOGLE_MAIL_REFRESH_TOKEN'),
];
