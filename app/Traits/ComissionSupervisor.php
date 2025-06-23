<?php

namespace App\Traits;


trait ComissionSupervisor
{

  public function ComissionSupervisor(float $amount): array
  {

    $commissions = [];

    if ($amount > 0) {
        $first_tier = min($amount, 50000);
        $commissions[] = [
            'percentage' => 0.30,
            'amount' => round($first_tier * 0.003, 2),
            'tier' => '0–50K',
            'tier_base_amount' => $first_tier,
        ];

        if ($amount > 50000) {
            $second_tier = min($amount - 50000, 50000);
            $commissions[] = [
                'percentage' => 0.20,
                'amount' => round($second_tier * 0.002, 2),
                'tier' => '50K–100K',
                'tier_base_amount' => $second_tier,
            ];
        }

        if ($amount > 100000) {
            $third_tier = $amount - 100000;
            $commissions[] = [
                'percentage' => 0.15,
                'amount' => round($third_tier * 0.0015, 2),
                'tier' => '100K+',
                'tier_base_amount' => $third_tier,
            ];
        }
    }

    return $commissions;
  }
}

 