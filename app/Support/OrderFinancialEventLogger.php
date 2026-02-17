<?php

namespace App\Support;

use App\Models\Order;

class OrderFinancialEventLogger
{
    public static function log(
        Order $order,
        string $eventType,
        string $summary,
        array $details = [],
        ?int $userId = null
    ): void {
        $resolvedUserId = $userId ?? auth()->id();

        $order->financialEvents()->create([
            'user_id' => $resolvedUserId,
            'event_type' => $eventType,
            'summary' => $summary,
            'details' => empty($details) ? null : $details,
        ]);
    }
}
