<?php

namespace App\Traits;

use App\Enum\RoleEnum;
use App\Models\Order;
use App\Models\User;
use Twilio\Rest\Client;

trait Twilio
{

  public function sendWhatsAppMessage($to, $parameters) {
    $twilioObject = new Client(config('twilio.twilio_sid'), config('twilio.twilio_auth_token'));
    $template = config('twilio.twilio_template');
    $twilioObject->messages->create(
      "whatsapp:" . $to,
      [
          "from" => 'whatsapp:' . config('twilio.twilio_sender'),
          'contentSid' => $template,
          "contentVariables" => json_encode($parameters)
      ]
    );
  }
  public function whatsapp(Order $order)
  {
    $parameters = [
      'order_number' => $order->order_number,
      'name' => $order->name,
    ];
    //env('ADMIN_PHONE');
    $adminPhones = explode(',', env('ADMIN_PHONE'));
    $adminPhones = array_merge($adminPhones, $order->installationTeams->pluck('user.phone')->toArray());
    //$supervisor = $order->supervisor->pluck('phone')->toArray();
    //$adminPhones = array_merge($adminPhones, $supervisor);
    if ($order->supervisor) { // Verifica si hay un supervisor asignado
      $adminPhones[] = $order->supervisor->phone; // Agrega el teléfono del supervisor
  }

    // Eliminar espacios en blanco alrededor de cada número (opcional)
    $adminPhones = array_map('trim', $adminPhones);
   
    foreach ($adminPhones as $phone) {
    $this->sendWhatsAppMessage($phone, $parameters);
    }
    //$order = Order::find(48);
    //Mail::to('carlos@reylosglass.com')->send(new DeliveryConfirmed($order));
    echo 'whatsapp message';
  }
}
    