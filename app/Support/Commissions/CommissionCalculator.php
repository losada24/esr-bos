<?php

namespace App\Support\Commissions;

use App\Enum\CommissionCalculationTypeEnum;
use App\Enum\CommissionPaymentStatusEnum;
use App\Enum\CommissionSplitTypeEnum;
use App\Enum\CommissionStatusEnum;
use App\Models\OrderCommission;
use App\Models\OrderCommissionPayment;

class CommissionCalculator
{
    public function refreshCommission(OrderCommission $commission): OrderCommission
    {
        $commission->loadMissing('order', 'payments');

        $projectAmount = round((float) ($commission->order?->project_amount ?? 0), 2);
        $feeAmount = round((float) ($commission->fee_amount_snapshot ?? 0), 2);
        $baseAmount = round(max($projectAmount - $feeAmount, 0), 2);

        $commissionAmount = $commission->calculation_type === CommissionCalculationTypeEnum::PERCENTAGE->value
            ? round($baseAmount * ((float) ($commission->percentage_value ?? 0)) / 100, 2)
            : round((float) ($commission->fixed_amount ?? 0), 2);

        $totalAmount = round($commissionAmount + (float) ($commission->other_cost_amount ?? 0), 2);

        $commission->forceFill([
            'project_amount_snapshot' => $projectAmount,
            'fee_amount_snapshot' => $feeAmount,
            'base_amount_snapshot' => $baseAmount,
            'commission_amount' => $commissionAmount,
            'total_amount' => $totalAmount,
        ])->save();

        $payments = $commission->payments()->orderBy('sequence')->get();

        foreach ($payments as $payment) {
            if ($payment->status === CommissionPaymentStatusEnum::PAID->value) {
                continue;
            }

            $this->refreshPayment($payment, $commission);
        }

        $payments = $commission->payments()->orderBy('sequence')->get();

        $paidAmount = round(
            $payments
                ->where('status', CommissionPaymentStatusEnum::PAID->value)
                ->sum(fn (OrderCommissionPayment $payment) => (float) $payment->total_to_pay),
            2
        );

        $activePayments = $payments->where('status', '!=', CommissionPaymentStatusEnum::CANCELED->value);
        $pendingAmount = round(
            $activePayments
                ->where('status', '!=', CommissionPaymentStatusEnum::PAID->value)
                ->sum(fn (OrderCommissionPayment $payment) => (float) $payment->total_to_pay),
            2
        );
        $hasPaidPayments = $payments->contains('status', CommissionPaymentStatusEnum::PAID->value);
        $hasOpenWork = $activePayments->contains(fn (OrderCommissionPayment $payment) => $payment->status !== CommissionPaymentStatusEnum::PAID->value);

        $status = CommissionStatusEnum::OPEN->value;
        if ($activePayments->isEmpty()) {
            $status = CommissionStatusEnum::CANCELED->value;
        } elseif (! $hasOpenWork && $paidAmount > 0) {
            $status = CommissionStatusEnum::FULLY_PAID->value;
        } elseif ($hasPaidPayments) {
            $status = CommissionStatusEnum::PARTIALLY_PAID->value;
        }

        if ($commission->status === CommissionStatusEnum::CANCELED->value && $activePayments->isNotEmpty()) {
            $status = CommissionStatusEnum::CANCELED->value;
        }

        $nextPaymentId = $payments
            ->firstWhere('status', CommissionPaymentStatusEnum::REVIEW->value)?->id;

        $commission->forceFill([
            'status' => $status,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'next_payment_id' => $nextPaymentId,
        ])->save();

        return $commission->fresh(['payments', 'nextPayment']);
    }

    public function refreshPayment(OrderCommissionPayment $payment, ?OrderCommission $commission = null): OrderCommissionPayment
    {
        $commission ??= $payment->commission()->with('payments', 'order')->firstOrFail();

        $paymentBaseAmount = $payment->split_type === CommissionSplitTypeEnum::PERCENTAGE->value
            ? round((float) $commission->total_amount * ((float) ($payment->split_value ?? 0)) / 100, 2)
            : round((float) ($payment->split_value ?? 0), 2);

        $payment->forceFill([
            'payment_base_amount' => $paymentBaseAmount,
            'total_to_pay' => round($paymentBaseAmount + (float) ($payment->other_cost_amount ?? 0), 2),
        ])->save();

        return $payment->fresh();
    }
}
