<?php

namespace App\Traits;

use App\Enum\ServiceEnum;
use App\Models\Order;
use Carbon\Carbon;

trait OrderDates {

  public function estimateETADate($payment_factory_date) {
    $payment_factory_date = Carbon::parse($payment_factory_date);
    if ($payment_factory_date->dayOfWeek === Carbon::SUNDAY) {
      $payment_factory_date = $payment_factory_date->addDay(); // Agregar un día para obtener la semana correcta
     }
    $eta_week = $payment_factory_date->addWeeks(7);
    $end_of_eta_week = $eta_week->endOfWeek();
    $estimate_eta_date = $end_of_eta_week->previous(Carbon::FRIDAY)->format('Y-m-d');
    
    return $estimate_eta_date;
  }

  public function getEstimateDeliveryByEtaDate($estimate_eta_date) {
    $temp_eta_date = Carbon::parse($estimate_eta_date);
    
    $eta_date = $temp_eta_date->addDays(10);
    if ($eta_date->isWeekend()) {
      $eta_date->next(Carbon::MONDAY);
    }
    
    return $eta_date->format('Y-m-d');
  }

  public function getEstimateDeliveryDate($payment_factory_date, $service, $county_id, $type_of_housing, $hasPermit = false) {
    $payment_factory_date_object = Carbon::parse($payment_factory_date);
    $estimate_delivery_date = null;

    if (
      $service === ServiceEnum::PICKUP->value || 
      ($service === ServiceEnum::DELIVERY->value && $county_id === 1) ||
      ($service === ServiceEnum::INSTALLATION->value && $type_of_housing === "1" && $county_id === "1")
    ) {
      if ($hasPermit) {
        $delivery_week = $payment_factory_date_object->addWeeks(9);
        $end_of_delivery_week = $delivery_week->endOfWeek();
        $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::MONDAY)->format('Y-m-d');
      } else {
        $delivery_week = $payment_factory_date_object->addWeeks(9);
        $end_of_delivery_week = $delivery_week->endOfWeek();
        $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::FRIDAY)->format('Y-m-d');
      }
    } else if ($service === ServiceEnum::DELIVERY->value) {
      $delivery_week = $payment_factory_date_object->addWeeks(9);
      $end_of_delivery_week = $delivery_week->endOfWeek();
      $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::THURSDAY)->format('Y-m-d');
    } else {
        $delivery_week = $payment_factory_date_object->addWeeks(9);
        $end_of_delivery_week = $delivery_week->endOfWeek();
        $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::WEDNESDAY)->format('Y-m-d');
    }
    
    return $estimate_delivery_date;
  }

  public function getEstimateInstallationDate($delivery_date, $service, $hasPermit) {
    $estimate_installation_date = null;
    
    if ($service === ServiceEnum::INSTALLATION->value) {
      $installation_date_object = Carbon::parse($delivery_date)->addDay();
      $booleanValue = filter_var($hasPermit, FILTER_VALIDATE_BOOLEAN);

      if ($booleanValue) {

        $estimate_installation_date = $this->calculateInstallationDateFromDelivery($installation_date_object);
      }
      else {
        // Buscar el próximo sábado con menos de 10 órdenes
        do {
            $installation_date_object = $installation_date_object->next(Carbon::SATURDAY);
            $saturdayOrdersCount = Order::where(function ($query) use ($installation_date_object) {
                $query->where('installation_date', '<=', $installation_date_object)
                    ->where('installation_end_date', '>=', $installation_date_object);
            })->count();
        } while ($saturdayOrdersCount >= 10);
        $estimate_installation_date = $installation_date_object->format('Y-m-d');
      }
    }

    return  $estimate_installation_date;
  }

  /*public function calculateInstallationDateFromDelivery($delivery_date) {
  
    $installation_date = Carbon::parse($delivery_date)->addDay();
    $installationForDateCount = Order::where('installation_date', $installation_date)
      ->orWhere('installation_end_date', $installation_date)
      ->orWhere(function ($query) use ($installation_date) {
        $query->where('installation_date', '<', $installation_date)
          ->where('installation_end_date', '>', $installation_date);
      })
      ->count();

      while ($installationForDateCount < 10) {
        $installation_date->addDay();
        $installationForDateCount = Order::where('installation_date', $installation_date)
          ->orWhere('installation_end_date', $installation_date)
          ->orWhere(function ($query) use ($installation_date) {
            $query->where('installation_date', '<', $installation_date)
              ->where('installation_end_date', '>', $installation_date);
          })
          ->count();
      }

      return $installation_date->format('Y-m-d');
  }*/

  public function calculateInstallationDateFromDelivery($delivery_date)
  {
      $max_orders_per_day = 10;
      do {
          // Contar cuántas órdenes afectan esta fecha
          $installationForDateCount = Order::where(function ($query) use ($delivery_date) {
              $query->where('installation_date', '<=', $delivery_date) // Comienza antes o el mismo día
                  ->where('installation_end_date', '>=', $delivery_date); // Termina después o el mismo día
          })->count();

          // Si hay más de las permitidas, pasar al siguiente día
          if ($installationForDateCount >= $max_orders_per_day) {
              $delivery_date->addDay();
              if ($delivery_date->dayOfWeek === Carbon::SATURDAY) {
                  $delivery_date->addDays(2); // Agregar un día para obtener la semana correcta
              } else if ($delivery_date->dayOfWeek === Carbon::SUNDAY) {
                  $delivery_date->addDay(); // Agregar un día para obtener la semana correcta
              }
          }

      } while ($installationForDateCount >= $max_orders_per_day);

      return $delivery_date->format('Y-m-d');
  }
}

