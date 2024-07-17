<?php

namespace App\Traits;

use Carbon\Carbon;

trait OrderDates {

  public function getEstimateDeliveryDate($payment_factory_date) {
    $delivery_date_object = Carbon::parse($payment_factory_date);
    $delivery_week = $delivery_date_object->addWeeks(8);
    $end_of_delivery_week = $delivery_week->endOfWeek();
    $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::FRIDAY)->format('Y-m-d');

    return $estimate_delivery_date;
  }

  public function getEstimateInstallationDate($payment_factory_date) {
    $installation_date_object = Carbon::parse($payment_factory_date);
    $installation_week = $installation_date_object->addWeeks(8);
    $end_of_installation_week = $installation_week->endOfWeek();
    $estimate_installation_date = $end_of_installation_week->previous(Carbon::FRIDAY)->format('Y-m-d');

    return  $estimate_installation_date;
  }
}
