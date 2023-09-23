<?php

namespace App\Traits;

use App\Models\Referred;

trait Reports {

  function GetReferralsByMonths() {
    $referrals = Referred::selectRaw('count(*) as count, MONTH(created_at) as month')
      ->whereYear('created_at', date('Y'))
      ->groupBy('month')
      ->get()
      ->toArray();

    $months = array_column($referrals, 'month');
    $counts = array_column($referrals, 'count');
    sort($months);
    
    return [
      'months' => array_map(function ($month) {
        $dateObject = \DateTime::createFromFormat('!m', $month);
        return $dateObject->format('M');
      }, $months),
      'counts' => $counts,
      'year' => date('Y')
    ];
  }

  function GetReferralsByStatus() {
    $referrals = Referred::selectRaw('count(*) as count, status')
      ->groupBy('status')
      ->orderBy('count', 'desc')
      ->get()
      ->toArray();

    return $referrals;
  }
}