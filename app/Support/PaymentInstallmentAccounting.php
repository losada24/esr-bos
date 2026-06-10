<?php

namespace App\Support;

use App\Enum\PaymentInstallmentStatusEnum;

class PaymentInstallmentAccounting
{
    private const EPSILON = 0.01;

    public static function summarize(float $scheduledAmount, float $paidAmount): array
    {
        $scheduledAmount = round($scheduledAmount, 2);
        $paidAmount = round($paidAmount, 2);

        $balance = max(0, round($scheduledAmount - $paidAmount, 2));
        $credit = max(0, round($paidAmount - $scheduledAmount, 2));

        return [
            'paid_amount' => $paidAmount,
            'balance' => $balance,
            'credit' => $credit,
            'status' => self::statusFor($scheduledAmount, $paidAmount),
        ];
    }

    public static function statusFor(float $scheduledAmount, float $paidAmount): string
    {
        if ($paidAmount <= self::EPSILON) {
            return PaymentInstallmentStatusEnum::PENDING->value;
        }

        if ($scheduledAmount <= self::EPSILON) {
            return PaymentInstallmentStatusEnum::OVERPAID->value;
        }

        if ($paidAmount + self::EPSILON < $scheduledAmount) {
            return PaymentInstallmentStatusEnum::PARTIAL->value;
        }

        if (abs($paidAmount - $scheduledAmount) <= self::EPSILON) {
            return PaymentInstallmentStatusEnum::PAID->value;
        }

        return PaymentInstallmentStatusEnum::OVERPAID->value;
    }
}
