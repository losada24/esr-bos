<?php

namespace App\Support\Commissions;

use App\Enum\CommissionPaymentKindEnum;
use App\Models\OrderCommissionAudit;
use App\Models\OrderCommissionPayment;
use Carbon\Carbon;

class CommissionHistoryPresenter
{
    public function serializeAudit(OrderCommissionAudit $audit): array
    {
        $changes = $audit->changes ?? [];
        $commission = $audit->commission ?? $audit->payment?->commission;
        $payment = $audit->payment;
        $period = $audit->period;
        $order = $commission?->order;
        $changeSets = $this->extractChangedFields($changes['before'] ?? null, $changes['after'] ?? null);
        $details = $changeSets['details'];
        $targetLabel = $this->resolveTargetLabel($audit, $changes);

        return [
            'id' => $audit->id,
            'changed_at' => $this->formatDateTimeValue($audit->changed_at),
            'user_name' => $audit->user?->name ?? 'System',
            'action' => $audit->action,
            'action_label' => $this->actionLabel($audit->action),
            'target_label' => $targetLabel,
            'order_id' => $order?->id,
            'order_name' => $order?->name,
            'commission_id' => $commission?->id ?? $audit->order_commission_id,
            'payment_id' => $payment?->id ?? $audit->order_commission_payment_id,
            'payment_sequence' => $payment?->sequence ?? ($changes['before']['sequence'] ?? $changes['after']['sequence'] ?? null),
            'period_id' => $period?->id ?? $audit->commission_period_id,
            'period_label' => $period?->label ?? ($changes['before']['label'] ?? $changes['after']['label'] ?? null),
            'beneficiary_name' => $commission?->beneficiary_name_snapshot ?? ($changes['before']['beneficiary_name_snapshot'] ?? $changes['after']['beneficiary_name_snapshot'] ?? null),
            'source' => $audit->source,
            'summary' => $this->summaryForAudit($audit, $details, $targetLabel, $changes),
            'details' => $details,
            'advanced_details' => $changeSets['advanced_details'],
        ];
    }

    public function serializePaidPayment(OrderCommissionPayment $payment): array
    {
        $commission = $payment->commission;
        $order = $commission?->order;

        return [
            'payment_id' => $payment->id,
            'commission_id' => $commission?->id,
            'payment_sequence' => $payment->sequence,
            'payment_kind' => $payment->payment_kind ?: CommissionPaymentKindEnum::REGULAR->value,
            'paid_at' => $this->formatDateValue($payment->paid_at),
            'period_id' => $payment->period?->id,
            'period_label' => $payment->period?->label,
            'order_id' => $order?->id,
            'order_name' => $order?->name,
            'order_number' => $order?->order_number,
            'invoice_number' => $order?->invoice_number,
            'order_status' => $order?->status,
            'beneficiary_name' => $commission?->beneficiary_name_snapshot,
            'beneficiary_relation' => $commission?->beneficiary_relation,
            'project_amount' => (float) (($commission?->project_amount_snapshot ?? null) ?? ($order?->project_amount ?? 0)),
            'commission_fee' => (float) ($commission?->fee_amount_snapshot ?? 0),
            'financing_fee_amount' => (float) ($commission?->financing_fee_amount ?? 0),
            'commission_base' => (float) ($commission?->base_amount_snapshot ?? 0),
            'commission_total' => (float) ($commission?->total_amount ?? 0),
            'payment_base_amount' => (float) ($payment->payment_base_amount ?? 0),
            'payment_other_cost_amount' => (float) ($payment->other_cost_amount ?? 0),
            'payment_total' => (float) ($payment->total_to_pay ?? 0),
            'payment_status' => $payment->status,
        ];
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'commission.created' => 'Commission Created',
            'commission.restored' => 'Commission Restored',
            'commission.updated' => 'Commission Updated',
            'commission.deleted' => 'Commission Deleted',
            'payment.created' => 'Payment Created',
            'payment.updated' => 'Payment Updated',
            'payment.deleted' => 'Payment Deleted',
            'payment.bulk_paid' => 'Payment Paid',
            'payment.assigned_to_period' => 'Payment Added To Period',
            'payment.removed_from_period' => 'Payment Removed From Period',
            'payment.closed_in_period' => 'Payment Paid In Period',
            'period.created' => 'Period Created',
            'period.updated' => 'Period Updated',
            'period.closed' => 'Period Closed',
            'period.reopened' => 'Period Reopened',
            'period.deleted' => 'Period Deleted',
            default => str($action)->replace('.', ' ')->title()->toString(),
        };
    }

    private function resolveTargetLabel(OrderCommissionAudit $audit, array $changes): string
    {
        if ($audit->order_commission_payment_id !== null || isset($changes['before']['sequence']) || isset($changes['after']['sequence'])) {
            $sequence = $audit->payment?->sequence ?? $changes['before']['sequence'] ?? $changes['after']['sequence'] ?? null;

            return $sequence !== null ? 'Payment #' . $sequence : 'Payment';
        }

        if ($audit->commission_period_id !== null || str_starts_with($audit->action, 'period.')) {
            return 'Commission Period';
        }

        return 'Commission';
    }

    private function summaryForAudit(OrderCommissionAudit $audit, array $details, string $targetLabel, array $changes): string
    {
        $before = $changes['before'] ?? [];
        $after = $changes['after'] ?? [];

        if (array_key_exists('status', $before) && array_key_exists('status', $after) && $before['status'] !== $after['status']) {
            return sprintf(
                '%s status changed from %s to %s',
                $targetLabel,
                (string) $before['status'],
                (string) $after['status']
            );
        }

        return match ($audit->action) {
            'commission.created' => 'Commission created',
            'commission.restored' => 'Commission restored',
            'commission.deleted' => 'Commission deleted',
            'payment.created' => 'Payment created',
            'payment.deleted' => 'Payment deleted',
            'payment.bulk_paid' => 'Payment marked as paid',
            'payment.assigned_to_period' => 'Payment added to commission period',
            'payment.removed_from_period' => 'Payment removed from commission period',
            'payment.closed_in_period' => 'Payment marked as paid when the period was closed',
            'period.created' => 'Commission period created',
            'period.closed' => 'Commission period closed',
            'period.reopened' => 'Commission period reopened',
            'period.deleted' => 'Commission period deleted',
            default => ! empty($details)
                ? 'Updated fields: ' . collect($details)->pluck('field')->implode(', ')
                : $this->actionLabel($audit->action),
        };
    }

    private function extractChangedFields(mixed $before, mixed $after): array
    {
        if (! is_array($before) || ! is_array($after)) {
            return [
                'details' => [],
                'advanced_details' => [],
            ];
        }

        $allKeys = collect(array_keys($before))
            ->merge(array_keys($after))
            ->unique()
            ->reject(fn (string $key) => in_array($key, ['created_at', 'updated_at', 'deleted_at'], true))
            ->values();

        $changes = $allKeys
            ->filter(function (string $key) use ($before, $after) {
                return $this->displayValue($before[$key] ?? null) !== $this->displayValue($after[$key] ?? null);
            })
            ->map(function (string $key) use ($before, $after) {
                $beforeValue = $before[$key] ?? null;
                $afterValue = $after[$key] ?? null;

                return [
                    'field' => $this->fieldLabel($key),
                    'before' => $this->displayValue($beforeValue),
                    'after' => $this->displayValue($afterValue),
                    'is_advanced' => $this->isAdvancedValue($beforeValue) || $this->isAdvancedValue($afterValue),
                ];
            })
            ->values();

        return [
            'details' => $changes
                ->reject(fn (array $change) => $change['is_advanced'])
                ->map(fn (array $change) => [
                    'field' => $change['field'],
                    'before' => $change['before'],
                    'after' => $change['after'],
                ])
                ->take(8)
                ->values()
                ->all(),
            'advanced_details' => $changes
                ->filter(fn (array $change) => $change['is_advanced'])
                ->map(fn (array $change) => [
                    'field' => $change['field'],
                    'before' => $change['before'],
                    'after' => $change['after'],
                ])
                ->take(8)
                ->values()
                ->all(),
        ];
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'status' => 'Status',
            'paid_at' => 'Paid At',
            'commission_period_id' => 'Commission Period',
            'order_commission_id' => 'Commission',
            'order_commission_payment_id' => 'Payment',
            'payment_base_amount' => 'Payment',
            'other_cost_amount' => 'Other Cost',
            'total_to_pay' => 'Total Payment',
            'payment_kind' => 'Payment Type',
            'split_type' => 'Split Type',
            'split_value' => 'Split Value',
            'beneficiary_name_snapshot' => 'Beneficiary Name',
            'beneficiary_relation' => 'Beneficiary Relation',
            'fee_amount_snapshot' => 'Commission Fee',
            'base_amount_snapshot' => 'Base',
            'percentage_value' => 'Percentage',
            'fixed_amount' => 'Fixed Amount',
            'total_amount' => 'Total Commission',
            'pending_amount' => 'Pending Amount',
            'paid_amount' => 'Paid Amount',
            default => str($field)->replace('_', ' ')->title()->toString(),
        };
    }

    private function isAdvancedValue(mixed $value): bool
    {
        return is_array($value) || is_object($value);
    }

    private function displayValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    private function formatDateTimeValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateTimeString();
        }

        return Carbon::parse((string) $value)->toDateTimeString();
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
}
