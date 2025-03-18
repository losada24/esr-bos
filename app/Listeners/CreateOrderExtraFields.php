<?php

namespace App\Listeners;

use App\Enum\OrderStatusEnum;
use App\Enum\PaymentStatusEnum;
use App\Events\OrderCreated;
use App\Models\InstallationPayment;
use App\Models\PaymentExtraField;

class CreateOrderExtraFields
{
  /**
   * Handle the event.
   */
  public function handle(OrderCreated $event): void
  {
    $order = $event->order;
    $order->loadMissing(['installationTeams', 'paymentExtraFields']);
    $orderExtraFields = $order->paymentExtraFields()->count() ?? 0;

    $status = $order->status;
    $wt = $order->walk_trough;
    $pi = $order->pre_inspection;

    foreach ($order->installationTeams as $team) {
      if ($orderExtraFields == 0 && $status == OrderStatusEnum::EXECUTION->value) {
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
          'responsible_extra_work' => '',
          'notes' => '',
        ]);
      }

      if ($status == OrderStatusEnum::INSPECTION->value && $pi == 1) {

        $percentage_payment = 80.00; // Porcentaje fijo
        $installer_payment = ($order->getGrandTotalPrice() * $percentage_payment) / 100; // Cálculo del 80%

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

      if ($status == OrderStatusEnum::COMPLETE->value && $wt == 1) {
        // Buscar pagos previos realizados (ya pagados)
        $ultimo_pagos = InstallationPayment::where('order_id', $order->id)
          ->where('installation_team_id', $team->user_id)
          ->where('payment_status', PaymentStatusEnum::PAID->value)
          ->get();

        $ultimo_pago_prev = InstallationPayment::where('order_id', $order->id)
          ->where('installation_team_id', $team->user_id)
          ->where('payment_status', PaymentStatusEnum::PAID->value)
          ->orderBy('created_at', 'desc')
          ->first();
        // Buscar pagos previos pendientes
        $pago_pendiente = InstallationPayment::where('order_id', $order->id)
          ->where('installation_team_id', $team->user_id)
          ->where('payment_status', PaymentStatusEnum::REVIEW->value)
          ->first();
          
        // Monto total de la orden
        $total_orden = $order->getGrandTotalPrice();
        $porcentaje_total = 100;
        $monto_pagado = $ultimo_pagos->sum('installer_payment');
        $porcentaje_pagado = $ultimo_pagos->sum('percentage_payment');

        // Calcular lo que falta por pagar
        $porcentaje_restante = $porcentaje_total - $porcentaje_pagado;
        $monto_restante = $total_orden - $monto_pagado;
        //dd($total_orden, $porcentaje_total, $monto_pagado, $porcentaje_pagado, $porcentaje_restante, $monto_restante);


        //dd($ultimo_pago_prev,$pago_pendiente,(int)$pago_pendiente->installer_payment);
        if ($ultimo_pago_prev) {
          // Buscar el primer pago en estado "review" después del último "paid"
          $pago_en_review = InstallationPayment::where('order_id', $order->id)
            ->where('installation_team_id', $team->user_id)
            ->where('payment_status', PaymentStatusEnum::REVIEW->value)
            ->where('created_at', '>', $ultimo_pago_prev->created_at)
            ->orderBy('created_at', 'asc')
            ->first();

          //dd($total_orden, $porcentaje_total, $monto_pagado, $porcentaje_pagado, $porcentaje_restante, $monto_restante);
          // Actualizar el primer pago encontrado
          if ($pago_en_review) {
            $pago_en_review->update(['installer_payment' => $monto_restante, 'percentage_payment' => $porcentaje_restante]);
          }
        } else if (!$ultimo_pago_prev && $pago_pendiente && (int)$pago_pendiente->installer_payment > 0) {
          // Si existe un pago pendiente, se actualiza con el 100%
          $pago_pendiente->update([
            'installer_payment' => $total_orden,
            'percentage_payment' => $porcentaje_total,
          ]);
        } else {
          // Si no hay pagos previos, registrar el 100% del pago
          if ($pago_pendiente){
          $pago_pendiente->update([
            'order_id' => $order->id,
            'installation_team_id' => $team->user_id,
            'installer_payment' => $total_orden,
            'percentage_payment' => $porcentaje_total,
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
}
