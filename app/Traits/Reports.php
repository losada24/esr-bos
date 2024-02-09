<?php

namespace App\Traits;

use App\Models\Order;

trait Reports {

  public function GetEstimatesByMonths() {
    $estimates = Order::selectRaw('count(*) as count, MONTH(created_at) as month')
      ->reports()
      ->whereYear('created_at', date('Y'))
      ->groupBy('month')
      ->orderBy('month', 'asc')
      ->get()
      ->toArray();

    $months = array_column($estimates, 'month');
    $counts = array_column($estimates, 'count');
    
    return [
      'months' => array_map(function ($month) {
        $dateObject = \DateTime::createFromFormat('!m', $month);
        return $dateObject->format('M');
      }, $months),
      'counts' => $counts,
      'year' => date('Y')
    ];
  }

  function GetOrdersByStatus() {
    $orders = Order::selectRaw('count(*) as count, status')
      ->reports()
      ->groupBy('status')
      ->orderBy('count', 'desc')
      ->get()
      ->toArray();

    return $orders;
  }
}
