<?php

namespace App\Services;

use App\Models\Setting;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Hamcrest\Core\Set;
use Illuminate\Support\Facades\View;

class GmailService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('custom.google_mail_api_client_id'));
        $this->client->setClientSecret(config('custom.google_mail_api_client_secret'));
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

        // Verifica si el token ha expirado y lo actualiza
        $this->initializeAccessToken();
    }

    private function initializeAccessToken()
    {
        $accessToken = Setting::where('name', 'GOOGLE_MAIL_ACCESS_TOKEN')->first()->value;

        if (!$accessToken) {
            $accessToken = $this->refreshAccessToken();
        }

        $this->client->setAccessToken($accessToken); // Ahora el cliente usará este token
    }

    private function refreshAccessToken()
    {
        $refreshToken = config('custom.google_mail_refresh_token');

        if ($refreshToken) {
            $newAccessToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);

            if (!empty($newAccessToken['access_token'])) {
              $accessToken = Setting::where('name', 'GOOGLE_MAIL_ACCESS_TOKEN')->first();
              $accessToken->update([
                'value' => $newAccessToken['access_token']
              ]);

                // Asignar el nuevo token al cliente
              $this->client->setAccessToken($newAccessToken['access_token']);

              return $newAccessToken['access_token'];
            }
        }

        return null;
    }

    public function sendEmail($to, $subject, $mailable)
    {
      if ($this->client->isAccessTokenExpired()) {
        $this->refreshAccessToken();
      }

        $this->service = new Gmail($this->client);
        $content = $mailable->content();
        $htmlContent = View::make($content->view, $content->with)->render();

        $email = "From: " . env('GOOGLE_EMAIL') . "\r\n";
        $email .= "To: $to\r\n";
        $email .= "Subject: $subject\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $email .= $htmlContent;

        $base64Email = base64_encode($email);
        $base64Email = str_replace(['+', '/', '='], ['-', '_', ''], $base64Email);

        $message = new Message();
        $message->setRaw($base64Email);

        return $this->service->users_messages->send('me', $message);
    }
}
