<?php

namespace App\Support;

class PaymentScheduleCalculator
{
    public static function withAmounts(array $items, float $totalAmount): array
    {
        $normalized = [];
        $runningTotal = 0.0;
        $count = count($items);

        foreach ($items as $index => $item) {
            $percentage = round((float) ($item['percentage'] ?? 0), 2);
            $amount = $index === $count - 1
                ? round($totalAmount - $runningTotal, 2)
                : round($totalAmount * ($percentage / 100), 2);

            $runningTotal += $amount;

            $normalized[] = [
                'label' => trim((string) ($item['label'] ?? '')),
                'percentage' => $percentage,
                'amount' => $amount,
            ];
        }

        return $normalized;
    }
}
