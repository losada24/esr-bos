<?php

namespace App\Support;

use App\Exceptions\StrictlyZeroPaymentNotPayableException;
use App\Models\Order;
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
            'city-fee' => $this->resolveCityFee($paymentId, $channel),
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

    private function resolveCityFee(int $paymentId, string $channel): array
    {
        $order = Order::query()
            ->with(['client', 'cityFeePayment'])
            ->findOrFail($paymentId);

        if (!$order->city_permits) {
            throw new StrictlyZeroPaymentNotPayableException("Order [{$paymentId}] does not require city permits.");
        }

        $amount = round((float) ($order->cost_city_fee ?? 0), 2);
        if ($amount <= 0.0) {
            throw new StrictlyZeroPaymentNotPayableException("Order [{$paymentId}] does not have a city fee amount.");
        }

        $orderPayment = $order->cityFeePayment;
        if ($orderPayment && strtoupper((string) $orderPayment->status) === 'PAID') {
            throw new StrictlyZeroPaymentNotPayableException("City fee payment for order [{$paymentId}] is already paid.");
        }

        if (!$orderPayment) {
            $orderPayment = $order->orderPayments()->create([
                'type' => 'CITY_FEE',
                'amount' => $amount,
                'note' => 'City Fee',
                'status' => 'PENDING',
            ]);
        } else {
            $orderPayment->forceFill([
                'amount' => $amount,
                'note' => $orderPayment->note ?: 'City Fee',
                'status' => $orderPayment->status ?: 'PENDING',
            ])->save();
        }

        return [
            'payment_type' => 'city-fee',
            'payment_id_for_intent' => $orderPayment->id,
            'channel' => strtolower($channel),
            'amount' => number_format($amount, 2, '.', ''),
            'order' => $order,
            'order_payment' => $orderPayment,
            'description' => trim(sprintf('City fee payment for order %s', $order->order_number ?: $order->id)),
        ];
    }
}
