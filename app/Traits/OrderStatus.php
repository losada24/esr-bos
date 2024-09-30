<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use PhpParser\Node\Stmt\Break_;

trait OrderStatus {

  public function getStatus($request) {
    $status = OrderStatusEnum::PLANNED->value;
    if ($request->supervisor_id != '' && count($request->installation_teams) > 0) {
      $status = OrderStatusEnum::CONFIRMED->value;
    } else if ($request->supervisor_id != '') {
      $status = OrderStatusEnum::DELIVERY_CONFIRMED->value;
    }

    return $status;
  }

  public function getColorByStatus($status, $service, $isInstallationEvent = false) {
    $color = '';
    switch ($status) {
      case OrderStatusEnum::PLANNED->value:
        if ($service == ServiceEnum::INSTALLATION->value) {
          if ($isInstallationEvent) {
            $color = '#0a7bd1';
          } else {
            $color = '#5FE3FB';
          }
        } else if ($service == ServiceEnum::DELIVERY->value) {
          $color = '#5FE3FB';
        } else {
          $color = '#9333ff';
        }
        break;
      case OrderStatusEnum::CONFIRMED->value:
        //$color = ;
        if ($service == ServiceEnum::INSTALLATION->value) {
          if ($isInstallationEvent) {
            $color = '#ffb533';
          } else {
            $color = '#F4F443';
          }
        } else if ($service == ServiceEnum::DELIVERY->value) {
          $color = '#F4F443';
        } else {
          $color = '#FF8D33';
        }
        break;
      case OrderStatusEnum::DELIVERY_CONFIRMED->value:
        $color = '#F4F443';
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
