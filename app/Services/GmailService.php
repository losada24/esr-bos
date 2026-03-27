<?php

namespace App\Services;

use App\Models\Setting;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\View;
use ReflectionClass;
use ReflectionFunction;
use Illuminate\Support\Facades\Storage;

class GmailService
{
    protected $client;
    protected $service;

    private function encodeHeaderValue(string $value): string
    {
      return preg_match('/[^\x20-\x7E]/', $value)
        ? '=?UTF-8?B?' . base64_encode($value) . '?='
        : $value;
    }

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('custom.google_mail_api_client_id'));
        $this->client->setClientSecret(config('custom.google_mail_api_client_secret'));
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->client->setScopes(['https://www.googleapis.com/auth/gmail.send']);
        $this->initializeAccessToken();
    }

    private function initializeAccessToken()
    {
        $accessToken = Setting::where('name', 'GOOGLE_MAIL_ACCESS_TOKEN')->first()->value;

        if (!$accessToken) {
            $accessToken = $this->refreshAccessToken();
        }

        $this->client->setAccessToken($accessToken);
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
        
      $boundary = uniqid('boundary_');
      $this->service = new Gmail($this->client);
      $content = $mailable->content();
      $envelope = $mailable->envelope();
      $htmlContent = View::make($content->view, $content->with)->render();
        
      if ($subject === null) {
        $subject = $envelope->subject;
      }

      if (is_array($to)) {
        $to = implode(', ', $to);
      }

      $encodedSubject = $this->encodeHeaderValue((string) $subject);

      $email = "From: " . config('app.name') . "\r\n";
      $email .= "To: $to\r\n";
      $email .= "Subject: $encodedSubject\r\n";
      $email .= "MIME-Version: 1.0\r\n";
      $email .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
      $email .= "--$boundary\r\n";
      $email .= "Content-Type: text/html; charset=UTF-8\r\n";
      $email .= "$htmlContent\r\n";

      /*foreach ($mailable->attachments() as $attachment) {
        $attachmentPath = $this->getAttachmentPath($attachment);
        if (file_exists($attachmentPath)) {
          $attachmentData = base64_encode(file_get_contents($attachmentPath));
          $attachmentData = chunk_split($attachmentData, 76, "\r\n");
          $filename = basename($attachmentPath);
          $mimeType = mime_content_type($attachmentPath);

          $email .= "--$boundary\r\n";
          $email .= "Content-Type: $mimeType; name=\"$filename\"\r\n";
          $email .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
          $email .= "Content-Transfer-Encoding: base64\r\n\r\n";
          $email .= "$attachmentData\r\n";
        }
      }*/
      foreach ($mailable->attachments() as $attachment) {
        $attachmentPath = $this->getAttachmentPath($attachment);

          if (Storage::disk('public')->exists($attachmentPath)) {
              $fileContents = Storage::disk('public')->get($attachmentPath);
              $attachmentData = base64_encode($fileContents);
              $attachmentData = chunk_split($attachmentData, 76, "\r\n");

              $filename = basename($attachmentPath);

              try {
                $mimeType = Storage::disk('public')->getDriver()->getMimetype($attachmentPath) ?? 'application/octet-stream';
              } catch (\Throwable  $e) {
                $extension = pathinfo($attachmentPath, PATHINFO_EXTENSION);
                $mimeType = match (strtolower($extension)) {
                    'pdf' => 'application/pdf',
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    default => 'application/octet-stream',
                };
              }

              $email .= "--$boundary\r\n";
              $email .= "Content-Type: $mimeType; name=\"$filename\"\r\n";
              $email .= "Content-Disposition: attachment; filename=\"$filename\"\r\n";
              $email .= "Content-Transfer-Encoding: base64\r\n\r\n";
              $email .= "$attachmentData\r\n";
          }
      }

      $email .= "--$boundary--";

      $base64Email = base64_encode($email);
      $base64Email = str_replace(['+', '/', '='], ['-', '_', ''], $base64Email);

      $message = new Message();
      $message->setRaw($base64Email);

      return $this->service->users_messages->send('me', $message);
    }

    public function getAttachmentPath($attachment)
    {
      $reflection = new ReflectionClass($attachment);
      $property = $reflection->getProperty('resolver');
      $property->setAccessible(true);
      $resolver = $property->getValue($attachment);
      $useVariables = (new ReflectionFunction($resolver))->getStaticVariables();
      
      return $useVariables['path'];
    }
}
