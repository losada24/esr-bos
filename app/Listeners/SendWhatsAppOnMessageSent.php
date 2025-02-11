<?php

namespace App\Listeners;

use App\Enum\RoleEnum;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Traits\Twilio;

class SendWhatsAppOnMessageSent
{
    use Twilio;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $mailMessage = $event->message;
        $to = $mailMessage->getTo();

        foreach ($to as $address) {
           $user = User::where('email', $address->getAddress())->first();

           if ($user && (
              $user->hasRole(RoleEnum::ACCOUNT_MANAGER->value) ||
              $user->hasRole(RoleEnum::ADMIN->value) ||
              $user->hasRole(RoleEnum::SUPERVISOR->value) ||
              $user->hasRole(RoleEnum::INSTALLER->value)
            )) {
              // $this->sendWhatsAppMessage('+12397632058', 'Hello from Laravel!');
            }
        }
       
    }
}
