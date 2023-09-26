<?php

return [
  'recaptcha_site_key' => env('RECAPTCHA_SITE_KEY'),
  'recaptcha_secret_key' => env('RECAPTCHA_SECRET_KEY'),
  'admin_emails' => explode(',', env('ADMIN_EMAILS')),
];
