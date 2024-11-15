<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusColorEnum;
use PhpParser\Node\Stmt\Break_;

trait OrderStatus {

  /* public function getStatus($request) {
    $status = OrderStatusEnum::PLANNED->value;
    if ($request->supervisor_id != '' && count($request->installation_teams) > 0) {
      $status = OrderStatusEnum::CONFIRMED->value;
    } else if ($request->supervisor_id != '') {
      $status = OrderStatusEnum::DELIVERY_CONFIRMED->value;
    }

    return $status;
  } */

  public function getColorByStatus($status, $service, $isInstallationEvent = false) {
    $color = '';
    switch ($status) {
      case OrderStatusEnum::PLANNED->value:
        if ($service == ServiceEnum::INSTALLATION->value) {
          if ($isInstallationEvent) {
            $color = StatusColorEnum::PLANNED_INSTALLATION_EVENT->value;
          } else {
            $color = StatusColorEnum::PLANNED_INSTALLATION->value;
          }
        } else if ($service == ServiceEnum::DELIVERY->value) {
          $color = StatusColorEnum::PLANNED_INSTALLATION->value;
        } else {
          $color = StatusColorEnum::PLANNED->value;
        }
        break;
      case OrderStatusEnum::CONFIRMED->value:
        //$color = ;
        if ($service == ServiceEnum::INSTALLATION->value) {
          if ($isInstallationEvent) {
            $color = StatusColorEnum::CONFIRMED_INSTALLATION->value;
          } else {
            $color = StatusColorEnum::CONFIRMED->value;
          }
        } else if ($service == ServiceEnum::DELIVERY->value) {
          $color = StatusColorEnum::CONFIRMED->value;
        } else {
          $color = StatusColorEnum::CONFIRMED_DELIVERY->value;
        }
        break;
      case OrderStatusEnum::DELIVERY_CONFIRMED->value:
        $color = StatusColorEnum::CONFIRMED->value;
        break;
    }

    return $color;
  }

  public function createEvent($order_id, $title, $tooltip, $start, $end, $color, $type_of_event) {
    return [
      'order_id' => $order_id,
      'title' => $title,
      'tooltip' => $tooltip,
      'start' => $start,
      'end' => $end,
      'color' => $color,
      'type_of_event' => $type_of_event,
    ];
  }

  public function getEventPopover($status, $service, $isInstallationEvent = false) {
    $popover = "";
    $statusText = "";

    if ($status == OrderStatusEnum::PLANNED->value) {
      $statusText = "ESTIMATE";
    } else {
      $statusText = "CONFIRMED";
    }

    switch ($service) {
      case ServiceEnum::PICKUP->value:
          $popover = $statusText . ' PICKUP DATE';
        break;
      case ServiceEnum::DELIVERY->value:
          $popover = $statusText . ' DELIVERY DATE';
        break;
      case ServiceEnum::INSTALLATION->value:
          if ($isInstallationEvent) {
            $popover = $statusText . ' INSTALLATION DATE';
          } else {
            $popover = $statusText . ' DELIVERY DATE';
          }
        break;
    }

    return $popover;
  }
}

/*public function getOrderProductsPopover($order) {

}*/
