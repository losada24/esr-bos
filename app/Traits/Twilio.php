<?php

namespace App\Traits;
use Twilio\Rest\Client;

trait Twilio
{
  public function sendWhatsAppMessage($to, $message) {
      $twilioObject = new Client(config('twilio.twilio_sid'), config('twilio.twilio_auth_token'));
      $twilioObject->messages->create(
        "whatsapp:" . $to, // Número del receptor (con prefijo de país)
        [
            "from" => config('twilio.twilio_whatsapp_from'),
            "body" => $message
        ]
    );
  }
}
    