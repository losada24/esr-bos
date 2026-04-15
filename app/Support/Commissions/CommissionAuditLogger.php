<?php

namespace App\Support\Commissions;

use App\Models\CommissionPeriod;
use App\Models\OrderCommission;
use App\Models\OrderCommissionAudit;
use App\Models\OrderCommissionPayment;
use Illuminate\Support\Facades\Auth;

class CommissionAuditLogger
{
    public static function log(
        string $action,
        array $changes,
        ?OrderCommission $commission = null,
        ?OrderCommissionPayment $payment = null,
        ?CommissionPeriod $period = null,
        string $source = 'commission-module'
    ): void {
        OrderCommissionAudit::create([
            'order_commission_id' => $commission?->id,
            'order_commission_payment_id' => $payment?->id,
            'commission_period_id' => $period?->id,
            'user_id' => Auth::id(),
            'source' => $source,
            'action' => $action,
            'changed_at' => now(),
            'changes' => $changes,
        ]);
    }
}
