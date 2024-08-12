<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Enum\Service;

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

  public function getColorByStatus($status) {
    $color = '';
    switch ($status) {
      case OrderStatusEnum::PLANNED->value:
        $color = '#5FE3FB';
        break;
      case OrderStatusEnum::CONFIRMED->value:
        $color = '#F4F443';
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
}
