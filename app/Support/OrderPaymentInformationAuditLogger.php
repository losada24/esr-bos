<?php

namespace App\Support;

use App\Enum\MethodOfPayment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class OrderPaymentInformationAuditLogger
{
    private const AUDITED_FIELDS = [
        'method_of_payment',
        'type_of_financing',
        'project_amount',
        'down_payment',
        'amount_to_finance',
        'payment_schedule_type',
        'payment_schedule_total_amount',
        'payment_schedule_installments',
    ];

    public static function snapshot(Order $order): array
    {
        $order->loadMissing('paymentSchedule.installments');
        $paymentSchedule = $order->paymentSchedule;

        $projectAmount = self::normalizeNumberOrNull($order->project_amount);
        $downPayment = self::normalizeNumberOrNull($order->down_payment);

        $amountToFinance = null;
        if ($order->method_of_payment === MethodOfPayment::FINANCEDCASH->value && $projectAmount !== null && $downPayment !== null) {
            $amountToFinance = self::normalizeNumber(max($projectAmount - $downPayment, 0));
        }

        return [
            'method_of_payment' => $order->method_of_payment ? (string) $order->method_of_payment : null,
            'type_of_financing' => $order->type_of_financing ? (string) $order->type_of_financing : null,
            'project_amount' => $projectAmount,
            'down_payment' => $downPayment,
            'amount_to_finance' => $amountToFinance,
            'payment_schedule_type' => $paymentSchedule?->schedule_type ? (string) $paymentSchedule->schedule_type : null,
            'payment_schedule_total_amount' => self::normalizeNumberOrNull($paymentSchedule?->total_amount),
            'payment_schedule_installments' => $paymentSchedule
                ? $paymentSchedule->installments
                    ->sortBy('position')
                    ->values()
                    ->map(fn ($item) => [
                        'position' => (int) ($item->position ?? 0),
                        'label' => trim((string) ($item->label ?? '')),
                        'percentage' => self::normalizeNumberOrNull($item->percentage),
                        'amount' => self::normalizeNumberOrNull($item->amount),
                        'due_date' => $item->due_date ? (string) $item->due_date : null,
                    ])
                    ->all()
                : [],
        ];
    }

    public static function logIfChanged(
        Order $order,
        array $beforeSnapshot,
        string $source,
        ?Request $request = null,
        ?int $userId = null
    ): void {
        if (!Schema::hasTable('order_payment_information_audits')) {
            return;
        }

        $afterSnapshot = self::snapshot($order);
        $changes = self::buildChanges($beforeSnapshot, $afterSnapshot);

        if (empty($changes)) {
            return;
        }

        $resolvedRequest = $request ?? request();
        $resolvedUserId = $userId ?? auth()->id();

        $order->paymentInformationAudits()->create([
            'user_id' => $resolvedUserId,
            'source' => $source,
            'changed_at' => now(),
            'ip_address' => $resolvedRequest?->ip(),
            'user_agent' => $resolvedRequest?->userAgent(),
            'changes' => $changes,
        ]);
    }

    private static function buildChanges(array $before, array $after): array
    {
        $changes = [];

        foreach (self::AUDITED_FIELDS as $field) {
            $beforeValue = $before[$field] ?? null;
            $afterValue = $after[$field] ?? null;

            if (!self::valuesAreEqual($beforeValue, $afterValue)) {
                $changes[$field] = [
                    'old' => $beforeValue,
                    'new' => $afterValue,
                ];
            }
        }

        return $changes;
    }

    private static function valuesAreEqual(mixed $before, mixed $after): bool
    {
        return json_encode($before) === json_encode($after);
    }

    private static function normalizeNumberOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::normalizeNumber((float) $value);
    }

    private static function normalizeNumber(float $value): float
    {
        return round($value, 2);
    }
}
