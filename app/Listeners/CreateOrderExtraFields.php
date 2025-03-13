<?php

namespace App\Listeners;

use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Events\OrderCreated;
use App\Models\InstallationPayment;
use App\Models\PaymentExtraField;
use App\Traits\Snapshot;
use Faker\Provider\ar_EG\Payment;

class CreateOrderExtraFields
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {   
        $order = $event->order;
        $order->loadMissing(['installationTeams', 'paymentExtraFields']); 
        $installer = $order->installationTeams->count();
        $orderExtraFields = $order->paymentExtraFields()->count() ?? 0;

        $status = $order->status;
      
        foreach ($event->order->installationTeams as $team) {
              if ($installer > 0 && $orderExtraFields == 0 && $status == OrderStatusEnum::EXECUTION->value) {
                  dd('entro');
                PaymentExtraField::create([
                    'installation_team_id' => $team->user_id,
                    'installer_payment_status' => 'OPEN',
                    'order_id' => $order->id,
                ]);
                InstallationPayment::create([
                    'order_id' => $order->id,
                    'installation_team_id' => $team->user_id,
                    'installer_payment' => 0.00,
                    'percentage_payment' => 0.00,
                    'payment_date' => null,
                    'extra_work' => 0.00,
                    'extra_discount' => 0.00,
                    'other_cost_installer' => 0.00,
                    'biweekly_id' => null,
                    'payment_status' => PaymentStatusEnum::REVIEW->value,
                   'responsible_extra_work'=> '',
                   'notes' => '',

                ]);
            }
       
        if ($status == OrderStatusEnum::INSPECTION->value) {
          
              $percentage_payment = 80.00; // Porcentaje fijo
              $installer_payment = ($order->getGrandTotalPrice() * $percentage_payment) / 100; // Cálculo del 80%
               //dd( $installer_payment);
      
              // Buscar si ya existe un registro para este equipo de instalación
              $installationPayment = InstallationPayment::where('order_id', $order->id)
                  ->where('installation_team_id', $team->user_id)
                  ->first();
      
              if ($installationPayment) {
                  // Si ya existe, actualizarlo
                  $installationPayment->update([
                      'installer_payment' => $installer_payment,
                      'percentage_payment' => $percentage_payment,
                      'payment_status' => PaymentStatusEnum::REVIEW->value,
                  ]);
              } else {
                  // Si no existe, crearlo
                  InstallationPayment::create([
                      'order_id' => $order->id,
                      'installation_team_id' => $team->user_id,
                      'installer_payment' => $installer_payment,
                      'percentage_payment' => $percentage_payment,
                      'payment_date' => null,
                      'extra_work' => 0.00,
                      'extra_discount' => 0.00,
                      'other_cost_installer' => 0.00,
                      'biweekly_id' => null,
                      'payment_status' => PaymentStatusEnum::REVIEW->value,
                      'responsible_extra_work' => '',
                      'notes' => '',
                  ]);
              }
            }
      }
    } 
        
}
