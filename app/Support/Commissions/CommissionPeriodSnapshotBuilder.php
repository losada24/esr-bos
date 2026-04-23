<?php

namespace App\Support\Commissions;

use App\Enum\CommissionPaymentKindEnum;
use App\Enum\OrderStatusEnum;
use App\Models\CommissionPeriod;
use App\Models\Order;
use App\Models\OrderCommissionPayment;
use App\Models\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CommissionPeriodSnapshotBuilder
{
    public function build(CommissionPeriod $period): array
    {
        $payments = OrderCommissionPayment::query()
            ->with([
                'commission.order.owners',
                'commission.order.orderStatus',
                'commission.order',
                'commission',
                'period',
            ])
            ->where('commission_period_id', $period->id)
            ->where('status', 'PAID')
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get();

        $beneficiaryTotals = $payments
            ->groupBy(fn (OrderCommissionPayment $payment) => $payment->commission->beneficiary_source_type . ':' . $payment->commission->beneficiary_source_id)
            ->map(function (Collection $group) {
                $first = $group->first();
                $commission = $first->commission;

                return [
                    'beneficiary_source_type' => $commission->beneficiary_source_type,
                    'beneficiary_source_id' => $commission->beneficiary_source_id,
                    'beneficiary_relation' => $commission->beneficiary_relation,
                    'beneficiary_name' => $commission->beneficiary_name_snapshot,
                    'beneficiary_email' => $commission->beneficiary_email_snapshot,
                    'total_paid' => round($group->sum('total_to_pay'), 2),
                    'payments_count' => $group->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'period' => [
                'id' => $period->id,
                'label' => $period->label,
                'start_date' => optional($period->start_date)->toDateString(),
                'end_date' => optional($period->end_date)->toDateString(),
                'status' => $period->status,
                'closed_at' => optional($period->closed_at)->toDateTimeString(),
            ],
            'summary' => [
                'payments_count' => $payments->count(),
                'orders_count' => $payments->pluck('commission.order_id')->unique()->count(),
                'commissions_count' => $payments->pluck('order_commission_id')->unique()->count(),
                'beneficiaries_count' => count($beneficiaryTotals),
                'total_paid' => round($payments->sum('total_to_pay'), 2),
                'beneficiary_totals' => $beneficiaryTotals,
            ],
            'payments' => $payments->map(function (OrderCommissionPayment $payment) {
                $commission = $payment->commission;
                $order = $commission->order;
                $accountingStatus = $order ? $this->resolveAccountingStatus($order) : null;

                return [
                    'payment_id' => $payment->id,
                    'sequence' => $payment->sequence,
                    'payment_kind' => $payment->payment_kind ?: CommissionPaymentKindEnum::REGULAR->value,
                    'status' => $payment->status,
                    'paid_at' => $this->formatDateValue($payment->paid_at),
                    'split_type' => $payment->split_type,
                    'split_value' => (float) $payment->split_value,
                    'payment_base_amount' => (float) $payment->payment_base_amount,
                    'payment_other_cost_amount' => (float) $payment->other_cost_amount,
                    'payment_total_to_pay' => (float) $payment->total_to_pay,
                    'payment_notes' => $payment->notes,
                    'accounting_status' => $accountingStatus['status'] ?? ($order?->status),
                    'accounting_status_date' => $accountingStatus['date'] ?? null,
                    'order' => [
                        'id' => $order?->id,
                        'name' => $order?->name,
                        'status' => $order?->status,
                        'order_number' => $order?->order_number,
                        'invoice_number' => $order?->invoice_number,
                        'project_payment_method' => $order?->method_of_payment,
                        'type_of_financing' => $order?->type_of_financing,
                        'project_amount' => (float) ($commission->project_amount_snapshot ?? ($order?->project_amount ?? 0)),
                        'cost_city_fee' => (float) ($order?->cost_city_fee ?? 0),
                        'owners' => $order?->owners?->pluck('name')->values()->all() ?? [],
                    ],
                    'commission' => [
                        'id' => $commission->id,
                        'status' => $commission->status,
                        'calculation_type' => $commission->calculation_type,
                        'percentage_value' => $commission->percentage_value !== null ? (float) $commission->percentage_value : null,
                        'fixed_amount' => $commission->fixed_amount !== null ? (float) $commission->fixed_amount : null,
                        'project_amount_snapshot' => (float) $commission->project_amount_snapshot,
                        'fee_amount_snapshot' => (float) $commission->fee_amount_snapshot,
                        'financing_fee_amount' => (float) $commission->financing_fee_amount,
                        'base_amount_snapshot' => (float) $commission->base_amount_snapshot,
                        'commission_amount' => (float) $commission->commission_amount,
                        'commission_other_cost_amount' => (float) $commission->other_cost_amount,
                        'commission_total_amount' => (float) $commission->total_amount,
                        'commission_paid_amount' => (float) $commission->paid_amount,
                        'commission_pending_amount' => (float) $commission->pending_amount,
                        'beneficiary_source_type' => $commission->beneficiary_source_type,
                        'beneficiary_source_id' => $commission->beneficiary_source_id,
                        'beneficiary_relation' => $commission->beneficiary_relation,
                        'beneficiary_name' => $commission->beneficiary_name_snapshot,
                        'beneficiary_email' => $commission->beneficiary_email_snapshot,
                    ],
                ];
            })->values()->all(),
        ];
    }

    private function formatDateValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    private function resolveAccountingStatus(Order $order): ?array
    {
        $status = $order->orderStatus
            ->whereIn('status', [
                OrderStatusEnum::ACCOUNT_RECEIPT->value,
                OrderStatusEnum::COMPLETE->value,
            ])
            ->sortByDesc(function (OrderStatus $orderStatus) {
                return sprintf(
                    '%010d-%010d',
                    $orderStatus->created_at?->timestamp ?? 0,
                    $orderStatus->id
                );
            })
            ->first();

        if (! $status) {
            return null;
        }

        return [
            'status' => $status->status,
            'date' => $status->created_at?->toDateTimeString(),
        ];
    }
}
