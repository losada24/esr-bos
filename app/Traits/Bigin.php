<?php

namespace App\Traits;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

trait Bigin {
  public $accountServer = 'https://accounts.zoho.com';
  public $apiServer = 'https://www.zohoapis.com';

  public function getAccessToken($code) {
    $tokenEndPoint = '/oauth/v2/token';
    $response = Http::asForm()->post($this->accountServer . $tokenEndPoint, [
      'client_id' => config('bigin.client_id'),
      'client_secret' => config('bigin.client_secret'),
      'code' => $code,
      'redirect_uri' => route('bigin.callback'),
      'grant_type' => 'authorization_code'
    ]);

    if ($response->successful() && !isset($response->json()['error'])) {
      $tokenData = $response->json();
      Setting::updateOrCreate(
        ['name' => 'BIGIN_TOKEN'],
        ['label' => 'Bigin Token', 'value' => $tokenData['access_token']]
      );

      Setting::updateOrCreate(
        ['name' => 'BIGIN_REFRESH_TOKEN'],
        ['label' => 'Bigin Refresh Token', 'value' => $tokenData['refresh_token']]
      );

      $actualDate = Carbon::now();
      $actualDate->addSeconds($tokenData['expires_in']);

      Setting::updateOrCreate(
        ['name' => 'BIGIN_TOKEN_EXPIRES_IN'],
        ['label' => 'Bigin Token Expiration', 'value' => $actualDate->toDateTimeString()]
      );
    }
  }

  public function refreshToken() {
    $refreshToken = Setting::where('name', 'BIGIN_REFRESH_TOKEN')->first()->value;
    $tokenEndPoint = '/oauth/v2/token';
    $response = Http::asForm()->post($this->accountServer . $tokenEndPoint, [
      'client_id' => config('bigin.client_id'),
      'client_secret' => config('bigin.client_secret'),
      'refresh_token' => $refreshToken,
      'grant_type' => 'refresh_token'
    ]);

    $result = '';
    if ($response->successful()) {
      $tokenData = $response->json();
      $result = $tokenData['access_token'];
      Setting::updateOrCreate(
        ['name' => 'BIGIN_TOKEN'],
        ['label' => 'Bigin Token', 'value' => $result]
      );

      $actualDate = Carbon::now();
      $actualDate->addSeconds($tokenData['expires_in']);

      Setting::updateOrCreate(
        ['name' => 'BIGIN_TOKEN_EXPIRES_IN'],
        ['label' => 'Bigin Token Expiration', 'value' => $actualDate->toDateTimeString()]
      );
    }

    return $result;
  }

  public function createContact($contact) {
    $token = Setting::where('name', 'BIGIN_TOKEN')->first()->value;
    $tokenExpiration = Carbon::createFromFormat('Y-m-d H:i:s', Setting::where('name', 'BIGIN_TOKEN_EXPIRES_IN')->first()->value);
    
    if ($tokenExpiration->isPast()) {
      $token = $this->refreshToken();
    }

    $contactEndPoint = '/bigin/v2/Contacts';
    $response = Http::withHeaders([
      'Authorization' => 'Zoho-oauthtoken ' . $token,
      'Content-Type' => 'application/json'
    ])->post($this->apiServer . $contactEndPoint, 
      [
        'data' => [
          $contact
        ]
      ]);

    $result = '';
    if ($response->successful()) {
      $result = $response->json();
    }

    return $result;
  }
}
