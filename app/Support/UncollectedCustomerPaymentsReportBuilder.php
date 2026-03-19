<?php

namespace App\Support;

use App\Enum\ServiceEnum;
use App\Models\Order;
use Illuminate\Support\Collection;

class UncollectedCustomerPaymentsReportBuilder
{
    public static function build(Collection $biweeklys, ?Collection $serviceByOrderId = null): array
    {
        $serviceByOrderId ??= Order::query()
            ->whereIn('id', $biweeklys
                ->flatMap(fn ($biweekly) => collect(data_get($biweekly, 'data', []))->pluck('id'))
                ->filter()
                ->unique()
                ->values())
            ->pluck('service', 'id');

        $uncollected = collect();
        $finalPaymentPending = collect();

        foreach ($biweeklys as $uncollectBiweekly) {
            $items = collect(data_get($uncollectBiweekly, 'data', []));

            foreach ($items as $uncollectItem) {
                $orderId = data_get($uncollectItem, 'id');
                $service = data_get($uncollectItem, 'service') ?? $serviceByOrderId->get($orderId);

                if (is_string($service) && trim($service) === ServiceEnum::SERVICE->value) {
                    continue;
                }

                $payments = collect(data_get($uncollectItem, 'installation_payments', []));
                $lastPayment = $payments->last();

                if (!$lastPayment) {
                    continue;
                }

                $percent = (int) data_get($lastPayment, 'percentage_payment', 0);
                $partialPending = (int) data_get($uncollectItem, 'partial_payment_installation', 0) === 0;
                $finalPending = (int) data_get($uncollectItem, 'final_payment_installation', 0) === 0;

                if (($percent >= 1 && $percent <= 100 && $partialPending && $finalPending) || ($percent === 20 && $partialPending && $finalPending)) {
                    $uncollected->push($uncollectItem);
                }

                if ($percent === 20 && $finalPending && !$partialPending) {
                    $finalPaymentPending->push($uncollectItem);
                }

                if ($percent === 100 && $finalPending && !$partialPending) {
                    $finalPaymentPending->push($uncollectItem);
                }
            }
        }

        return [
            'uncollected' => $uncollected,
            'final_payment_pending' => $finalPaymentPending,
        ];
    }
}
