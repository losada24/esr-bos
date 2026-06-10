<?php

namespace App\Support;

use App\Models\PaymentInstallment;
use App\Models\PaymentSchedule;
use Illuminate\Support\Collection;

class PaymentInstallmentPresenter
{
    public static function installment(PaymentInstallment $installment): array
    {
        $paidAmount = $installment->relationLoaded('movements')
            ? (float) $installment->movements->sum('amount')
            : (float) $installment->movements()->sum('amount');

        $summary = PaymentInstallmentAccounting::summarize((float) $installment->amount, $paidAmount);
        $movements = self::movementCollection($installment);

        $latestMovement = $movements->sortByDesc('paid_at')->first();

        return [
            'id' => $installment->id,
            'label' => $installment->label,
            'percentage' => (float) $installment->percentage,
            'amount' => (float) $installment->amount,
            'due_date' => $installment->due_date?->format('Y-m-d'),
            'status' => $summary['status'],
            'paid_amount' => $summary['paid_amount'],
            'balance' => $summary['balance'],
            'credit' => $summary['credit'],
            'paid_at' => $latestMovement['paid_at'] ?? $installment->paid_at?->toISOString(),
            'position' => $installment->position,
            'paid_by' => $latestMovement['paid_by'] ?? ($installment->paidBy
                ? ['id' => $installment->paidBy->id, 'name' => $installment->paidBy->name]
                : null),
            'movements' => $movements->values()->all(),
        ];
    }

    public static function schedule(?PaymentSchedule $schedule): ?array
    {
        if (!$schedule) {
            return null;
        }

        $installments = $schedule->installments
            ->sortBy('position')
            ->values()
            ->map(fn (PaymentInstallment $installment) => self::installment($installment));

        $paidAmount = (float) $installments->sum('paid_amount');
        $totalAmount = (float) $schedule->total_amount;

        return [
            'id' => $schedule->id,
            'schedule_type' => $schedule->schedule_type,
            'total_amount' => $totalAmount,
            'paid_amount' => round($paidAmount, 2),
            'remaining_amount' => max(0, round($totalAmount - $paidAmount, 2)),
            'credit_amount' => max(0, round($paidAmount - $totalAmount, 2)),
            'installments' => $installments->all(),
        ];
    }

    public static function movementCollection(PaymentInstallment $installment): Collection
    {
        $movements = $installment->relationLoaded('movements')
            ? $installment->movements
            : $installment->movements()->with('paidBy')->get();

        return $movements
            ->sortByDesc(function ($movement) {
                return sprintf('%s-%010d', optional($movement->paid_at)->format('YmdHis') ?? '0', $movement->id ?? 0);
            })
            ->values()
            ->map(fn ($movement) => [
                'id' => $movement->id,
                'amount' => (float) $movement->amount,
                'paid_at' => $movement->paid_at?->toISOString(),
                'method' => $movement->method,
                'note' => $movement->note,
                'paid_by' => $movement->paidBy
                    ? ['id' => $movement->paidBy->id, 'name' => $movement->paidBy->name]
                    : null,
                'created_at' => $movement->created_at?->toISOString(),
                'updated_at' => $movement->updated_at?->toISOString(),
            ]);
    }
}
