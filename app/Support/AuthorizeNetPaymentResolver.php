<?php

namespace App\Support;

use App\Exceptions\AuthorizeNetPaymentNotPayableException;
use App\Models\OrderPayment;
use App\Models\PaymentInstallment;
use App\Support\PaymentInstallmentAccounting;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthorizeNetPaymentResolver
{
    public function resolve(string $paymentType, int $paymentId, string $channel = 'web'): array
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
            ->with(['schedule.order'])
            ->findOrFail($paymentId);

        $paidAmount = (float) $installment->movements()->sum('amount');
        $summary = PaymentInstallmentAccounting::summarize((float) $installment->amount, $paidAmount);
        $amount = max(0, round((float) $summary['balance'], 2));

        if ($amount <= 0.0) {
            throw new AuthorizeNetPaymentNotPayableException("Payment installment [{$paymentId}] is already paid.");
        }

        $order = $installment->schedule?->order;
        if (!$order) {
            throw new ModelNotFoundException("Order not found for payment installment [{$paymentId}].");
        }

        return [
            'payment_type' => 'quota',
            'channel' => strtolower($channel),
            'reference' => AuthorizeNetPaymentReference::buildQuota($installment->id, $channel),
            'amount' => number_format($amount, 2, '.', ''),
            'order' => $order,
            'payment_installment' => $installment,
            'invoice_number' => $this->truncate('INST-' . $installment->id, 20),
            'description' => $this->truncate(
                trim(sprintf('%s payment for order %s', $installment->label, $order->order_number ?: $order->id)),
                255
            ),
        ];
    }

    private function resolveChangeOrder(int $paymentId, string $channel): array
    {
        $orderPayment = OrderPayment::query()
            ->with('order')
            ->findOrFail($paymentId);

        if (strtoupper((string) $orderPayment->type) !== 'CHANGE_ORDER') {
            throw new ModelNotFoundException("Order payment [{$paymentId}] is not a CHANGE_ORDER payment.");
        }

        if (strtoupper((string) $orderPayment->status) === 'PAID') {
            throw new AuthorizeNetPaymentNotPayableException("Order payment [{$paymentId}] is already paid.");
        }

        $order = $orderPayment->order;
        if (!$order) {
            throw new ModelNotFoundException("Order not found for order payment [{$paymentId}].");
        }

        return [
            'payment_type' => 'change-order',
            'channel' => strtolower($channel),
            'reference' => AuthorizeNetPaymentReference::buildChangeOrder($orderPayment->id, $channel),
            'amount' => number_format((float) $orderPayment->amount, 2, '.', ''),
            'order' => $order,
            'order_payment' => $orderPayment,
            'invoice_number' => $this->truncate('CHG-' . $orderPayment->id, 20),
            'description' => $this->truncate(
                trim(sprintf('Change order payment for order %s', $order->order_number ?: $order->id)),
                255
            ),
        ];
    }

    private function truncate(string $value, int $length): string
    {
        return mb_substr($value, 0, $length);
    }
}
