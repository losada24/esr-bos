<?php

namespace App\Support\Commissions;

use App\Enum\CommissionPaymentStatusEnum;
use App\Enum\CommissionPeriodStatusEnum;
use App\Models\CommissionPeriod;
use App\Models\CommissionPeriodSnapshot;
use App\Models\OrderCommissionPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CloseCommissionPeriod
{
    public function __construct(
        private readonly CommissionCalculator $calculator,
        private readonly CommissionPeriodSnapshotBuilder $snapshotBuilder
    ) {
    }

    public function handle(CommissionPeriod $period): CommissionPeriod
    {
        return DB::transaction(function () use ($period) {
            $period->refresh();

            if ($period->status === CommissionPeriodStatusEnum::CLOSED->value) {
                return $period->load('snapshot');
            }

            $payments = OrderCommissionPayment::query()
                ->whereNull('commission_period_id')
                ->whereIn('status', [
                    CommissionPaymentStatusEnum::REVIEW->value,
                    CommissionPaymentStatusEnum::PAID->value,
                ])
                ->with('commission')
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                $before = $payment->only(['status', 'commission_period_id', 'paid_at']);

                $payment->update([
                    'commission_period_id' => $period->id,
                    'status' => CommissionPaymentStatusEnum::PAID->value,
                    'paid_at' => $payment->paid_at ?: now()->toDateString(),
                    'updated_by' => Auth::id(),
                ]);

                $this->calculator->refreshCommission($payment->commission);

                CommissionAuditLogger::log(
                    'payment.closed_in_period',
                    [
                        'before' => $before,
                        'after' => $payment->fresh()->only(['status', 'commission_period_id', 'paid_at']),
                    ],
                    $payment->commission,
                    $payment,
                    $period
                );
            }

            $snapshotData = $this->snapshotBuilder->build($period);

            CommissionPeriodSnapshot::updateOrCreate(
                ['commission_period_id' => $period->id],
                [
                    'created_by' => Auth::id(),
                    'data' => $snapshotData,
                ]
            );

            $period->update([
                'status' => CommissionPeriodStatusEnum::CLOSED->value,
                'closed_at' => now(),
                'closed_by' => Auth::id(),
            ]);

            CommissionAuditLogger::log(
                'period.closed',
                [
                    'payments_closed' => $payments->count(),
                    'snapshot_summary' => $snapshotData['summary'] ?? [],
                ],
                period: $period
            );

            return $period->fresh(['snapshot', 'payments']);
        });
    }
}
