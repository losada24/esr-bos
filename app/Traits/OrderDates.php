<?php

namespace App\Traits;

use App\Enum\Service;
use App\Models\TypeOfHousing;
use Carbon\Carbon;

trait OrderDates {

  public function estimateETADate($payment_factory_date) {
    $payment_factory_date = Carbon::parse($payment_factory_date);
    $eta_week = $payment_factory_date->addWeeks(7);
    $end_of_eta_week = $eta_week->endOfWeek();
    $estimate_eta_date = $end_of_eta_week->previous(Carbon::FRIDAY)->format('Y-m-d');

    return $estimate_eta_date;
  }

  public function getEstimateDeliveryDate($payment_factory_date, $service, $county_id, $type_of_housing) {
    $payment_factory_date_object = Carbon::parse($payment_factory_date);
    $estimate_delivery_date = null;

    if (
      $service === Service::PICKUP->value || 
      ($service === Service::DELIVERY->value && $county_id === 1) ||
      ($service === Service::INSTALLATION->value && $type_of_housing === "1" && $county_id === "1")
    ) {
      $delivery_week = $payment_factory_date_object->addWeeks(8);
      $end_of_delivery_week = $delivery_week->endOfWeek();
      $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::MONDAY)->format('Y-m-d');
    } else if ($service === Service::DELIVERY->value) {
      $delivery_week = $payment_factory_date_object->addWeeks(8);
      $end_of_delivery_week = $delivery_week->endOfWeek();
      $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::THURSDAY)->format('Y-m-d');
    } else {
        $delivery_week = $payment_factory_date_object->addWeeks(8);
        $end_of_delivery_week = $delivery_week->endOfWeek();
        $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::WEDNESDAY)->format('Y-m-d');
    }
    
    return $estimate_delivery_date;
  }

  public function getEstimateInstallationDate($delivery_date, $service) {
    $estimate_installation_date = null;
    if ($service === Service::INSTALLATION->value) {
      $installation_date_object = Carbon::parse($delivery_date)->addDay();
      $estimate_installation_date = $installation_date_object->format('Y-m-d');
    }

    return  $estimate_installation_date;
  }
}
