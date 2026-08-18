<?php

namespace App\Support;

use App\Enum\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderStageOverdue;
use App\Models\OrderStageOverdueExtension;
use Carbon\Carbon;

class OrderStageOverdueTracker
{
    public function sync(Order $order): array
    {
        $stageAge = $this->resolveStageAge($order);

        if (!$stageAge['overdue']) {
            $this->resolveActiveForOrderStatus(
                (int) $order->id,
                (string) ($order->status ?? ''),
                $stageAge['stage_started_at_raw']
            );

            return $stageAge;
        }

        $overdue = OrderStageOverdue::query()->firstOrNew([
            'order_id' => $order->id,
            'status' => $stageAge['status'],
            'stage_started_at' => $stageAge['stage_started_at_raw'],
        ]);

        if (!$overdue->exists) {
            $overdue->detected_at = now();
        }

        $overdue->fill([
            'order_status_id' => $stageAge['order_status_id'],
            'limit_business_days' => $stageAge['limit_business_days'],
            'business_days_elapsed' => $stageAge['business_days_elapsed'],
            'resolved_at' => null,
            'resolved_business_days_elapsed' => null,
            'is_active' => true,
        ])->save();

        return $stageAge;
    }

    public function activeExtensionForStageAge(Order $order, array $stageAge): ?OrderStageOverdueExtension
    {
        if (!($stageAge['overdue'] ?? false) || empty($stageAge['stage_started_at_raw'])) {
            return null;
        }

        $latestExtension = OrderStageOverdueExtension::query()
            ->with('user:id,name')
            ->where('order_id', $order->id)
            ->where('status', $stageAge['status'])
            ->where('stage_started_at', $stageAge['stage_started_at_raw'])
            ->latest('id')
            ->first();

        if (!$latestExtension || !$latestExtension->extended_until) {
            return null;
        }

        return Carbon::parse($latestExtension->extended_until)->endOfDay()->greaterThanOrEqualTo(now())
            ? $latestExtension
            : null;
    }

    public function latestExtensionForStageAge(Order $order, array $stageAge): ?OrderStageOverdueExtension
    {
        if (!($stageAge['overdue'] ?? false) || empty($stageAge['stage_started_at_raw'])) {
            return null;
        }

        return OrderStageOverdueExtension::query()
            ->with('user:id,name')
            ->where('order_id', $order->id)
            ->where('status', $stageAge['status'])
            ->where('stage_started_at', $stageAge['stage_started_at_raw'])
            ->latest('id')
            ->first();
    }

    public function extensionPayload(?OrderStageOverdueExtension $extension): ?array
    {
        if (!$extension) {
            return null;
        }

        return [
            'id' => $extension->id,
            'business_days' => (int) $extension->business_days,
            'extended_until' => optional($extension->extended_until)->toIso8601String(),
            'note' => $extension->note,
            'created_at' => optional($extension->created_at)->toIso8601String(),
            'user' => $extension->user ? [
                'id' => $extension->user->id,
                'name' => $extension->user->name,
            ] : null,
        ];
    }

    public function resolveStageAge(Order $order): array
    {
        $status = (string) ($order->status ?? '');
        $limit = $this->stageBusinessDayLimits()[$status] ?? null;
        $statusHistoryEntry = $order->relationLoaded('orderStatus')
            ? $order->orderStatus
                ->where('status', $status)
                ->sortByDesc('created_at')
                ->first()
            : $order->orderStatus()
                ->where('status', $status)
                ->latest('created_at')
                ->first();
        $startedAt = $statusHistoryEntry?->created_at ?? $order->created_at;

        if (!$startedAt) {
            return [
                'status' => $status,
                'order_status_id' => $statusHistoryEntry?->id,
                'started_at' => null,
                'started_at_iso' => null,
                'stage_started_at_raw' => null,
                'business_days_elapsed' => null,
                'limit_business_days' => $limit,
                'overdue' => false,
            ];
        }

        $startedAt = Carbon::parse($startedAt);
        $businessDaysElapsed = (int) $startedAt
            ->copy()
            ->startOfDay()
            ->diffInWeekdays(Carbon::now()->startOfDay());

        return [
            'status' => $status,
            'order_status_id' => $statusHistoryEntry?->id,
            'started_at' => $startedAt->format('M d, Y h:i A'),
            'started_at_iso' => $startedAt->toIso8601String(),
            'stage_started_at_raw' => $startedAt,
            'business_days_elapsed' => $businessDaysElapsed,
            'limit_business_days' => $limit,
            'overdue' => $limit !== null && $businessDaysElapsed > $limit,
        ];
    }

    public function resolveActiveForOrderStatus(int $orderId, string $status, ?Carbon $stageStartedAt = null): void
    {
        if ($orderId <= 0 || $status === '') {
            return;
        }

        $query = OrderStageOverdue::query()
            ->where('order_id', $orderId)
            ->where('status', $status)
            ->where('is_active', true)
            ->whereNull('resolved_at');

        if ($stageStartedAt !== null) {
            $query->where('stage_started_at', $stageStartedAt);
        }

        $query->get()->each(function (OrderStageOverdue $overdue): void {
            $resolvedBusinessDays = $overdue->stage_started_at
                ? (int) Carbon::parse($overdue->stage_started_at)
                    ->startOfDay()
                    ->diffInWeekdays(Carbon::now()->startOfDay())
                : $overdue->business_days_elapsed;

            $overdue->update([
                'resolved_at' => now(),
                'resolved_business_days_elapsed' => $resolvedBusinessDays,
                'is_active' => false,
            ]);
        });
    }

    public function stageBusinessDayLimits(): array
    {
        return [
            OrderStatusEnum::DEALER_REQUEST->value => 5,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value => 20,
            OrderStatusEnum::REVIEW->value => 3,
            OrderStatusEnum::ACCOUNT_RECEIPT->value => 7,
            OrderStatusEnum::PRODUCTION->value => 25,
            OrderStatusEnum::PENDING_GLASS_INVOICE->value => 7,
            OrderStatusEnum::PRODUCTION_SERVICES->value => 20,
            OrderStatusEnum::PRE_COORDINATION_ACCOUNTING->value => 5,
            OrderStatusEnum::PENDING_MAT_REYLOS->value => 12,
            OrderStatusEnum::PENDING_MATERIALS_EWS->value => 30,
            OrderStatusEnum::PENDING_MATERIALS->value => 12,
            OrderStatusEnum::MATERIAL_ORDER_COMPLETED->value => 7,
            OrderStatusEnum::MATERIAL_ORDER_COMPLETED_FINANCED->value => 20,
            OrderStatusEnum::STORAGE_MATERIAL->value => 60,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED_FINANCED->value => 15,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED_BACKORDER->value => 15,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED->value => 3,
            OrderStatusEnum::PENDING_PAYMENT->value => 3,
        ];
    }
}
