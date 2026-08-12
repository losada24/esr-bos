<?php

namespace App\Support;

use App\Exceptions\StrictlyZeroPaymentNotPayableException;
use App\Models\OrderPayment;
use App\Models\PaymentInstallment;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StrictlyZeroPaymentResolver
{
    public function resolve(string $paymentType, int $paymentId, string $channel = 'mobile'): array
    {
        return match ($paymentType) {
            'quota' => $this->resolveQuota($paymentId, $channel),
            'change-order' => $this->resolveChangeOrder($paymentId, $channel),
            default => throw new ModelNotFoundException("Unsupported payment type [{$paymentType}]"),
        };
    }

    private function resolveQuota(int $paymentId, string $channel): array
    {
        $installment = PaymentInstallment::query()
            ->with(['schedule.order.client'])
            ->findOrFail($paymentId);

        $paidAmount = (float) $installment->movements()->sum('amount');
        $summary = PaymentInstallmentAccounting::summarize((float) $installment->amount, $paidAmount);
        $amount = max(0, round((float) $summary['balance'], 2));

        if ($amount <= 0.0) {
            throw new StrictlyZeroPaymentNotPayableException("Payment installment [{$paymentId}] is already paid.");
        }

        $order = $installment->schedule?->order;
        if (!$order) {
            throw new ModelNotFoundException("Order not found for payment installment [{$paymentId}].");
        }

        return [
            'payment_type' => 'quota',
            'channel' => strtolower($channel),
            'amount' => number_format($amount, 2, '.', ''),
            'order' => $order,
            'payment_installment' => $installment,
            'description' => trim(sprintf('%s payment for order %s', $installment->label, $order->order_number ?: $order->id)),
        ];
    }

    private function resolveChangeOrder(int $paymentId, string $channel): array
    {
        $orderPayment = OrderPayment::query()
            ->with('order.client')
            ->findOrFail($paymentId);

        if (strtoupper((string) $orderPayment->type) !== 'CHANGE_ORDER') {
            throw new ModelNotFoundException("Order payment [{$paymentId}] is not a CHANGE_ORDER payment.");
        }

        if (strtoupper((string) $orderPayment->status) === 'PAID') {
            throw new StrictlyZeroPaymentNotPayableException("Order payment [{$paymentId}] is already paid.");
        }

        $order = $orderPayment->order;
        if (!$order) {
            throw new ModelNotFoundException("Order not found for order payment [{$paymentId}].");
        }

        return [
            'payment_type' => 'change-order',
            'channel' => strtolower($channel),
            'amount' => number_format((float) $orderPayment->amount, 2, '.', ''),
            'order' => $order,
            'order_payment' => $orderPayment,
            'description' => trim(sprintf('Change order payment for order %s', $order->order_number ?: $order->id)),
        ];
    }
}
