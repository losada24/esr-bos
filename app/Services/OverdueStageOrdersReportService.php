<?php

namespace App\Services;

use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\RoleEnum;
use App\Enum\StatusUserEnum;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OverdueStageOrdersReportService
{
    public function build(array $filters = []): array
    {
        $now = Carbon::now();
        $configuredStatuses = collect($this->statusConfigs())->keyBy('status');
        $allStatuses = collect(OrderStatusEnum::cases())->map(fn (OrderStatusEnum $status) => $status->value)->all();
        $orderTypes = collect(OrderTypeEnum::cases())->map(fn (OrderTypeEnum $type) => $type->value)->all();
        $productLines = collect(ProductLineEnum::cases())->map(fn (ProductLineEnum $line) => $line->value)->all();
        $sellerId = (int) ($filters['seller_id'] ?? 0) ?: null;
        $overdueOnly = filter_var($filters['overdue_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $selectedStatuses = $this->resolveArrayFilter($filters['statuses'] ?? [], $allStatuses);
        $selectedOrderTypes = $this->resolveArrayFilter($filters['order_types'] ?? [], $orderTypes);
        $selectedProductLines = $this->resolveArrayFilter($filters['product_lines'] ?? [], $productLines);

        $sellers = User::role(RoleEnum::OWNER->value)
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]);

        $orders = Order::query()
            ->with([
                'owners:id,name',
                'user:id,name',
                'orderStatus' => function ($query) use ($allStatuses) {
                    $query
                        ->select('id', 'order_id', 'status', 'user_id', 'created_at')
                        ->whereIn('status', $allStatuses)
                        ->with('user:id,name')
                        ->orderBy('created_at');
                },
            ])
            ->when($sellerId, function ($query) use ($sellerId) {
                $query->whereHas('owners', function ($ownerQuery) use ($sellerId) {
                    $ownerQuery->where('users.id', $sellerId);
                });
            })
            ->when($selectedStatuses !== [], fn ($query) => $query->whereIn('status', $selectedStatuses))
            ->when($selectedOrderTypes !== [], fn ($query) => $query->whereIn('order_type', $selectedOrderTypes))
            ->when($selectedProductLines !== [], fn ($query) => $query->whereIn('product_line', $selectedProductLines))
            ->get([
                'id',
                'name',
                'status',
                'order_type',
                'product_line',
                'project_amount',
                'schedule_appointment',
                'created_at',
            ]);

        $groupStatuses = collect($this->pipelineStatusOrder())
            ->merge($allStatuses)
            ->merge($orders->pluck('status')->filter()->unique())
            ->unique()
            ->when($selectedStatuses !== [], fn (Collection $statuses) => $statuses->filter(fn (string $status) => in_array($status, $selectedStatuses, true)))
            ->values();

        $groups = $groupStatuses
            ->map(function (string $status) use ($orders, $now, $configuredStatuses, $overdueOnly) {
                $config = $configuredStatuses->get($status, [
                    'status' => $status,
                    'is_configured' => false,
                    'hours' => null,
                    'threshold_label' => 'Not configured',
                    'note' => 'No overdue threshold is configured for this status.',
                ]);

                $rows = $orders
                    ->filter(fn (Order $order) => $order->status === $status)
                    ->map(fn (Order $order) => $this->mapOrderRow($order, $config, $now))
                    ->when($overdueOnly, fn (Collection $rows) => $rows->filter(fn (array $row) => $row['is_overdue']))
                    ->sortByDesc('days_in_stage')
                    ->values();

                $sellerGroups = $rows
                    ->groupBy(fn (array $row) => $row['group_label'] ?: 'Unassigned')
                    ->map(fn (Collection $sellerRows, string $sellerName) => [
                        'seller_name' => $sellerName,
                        'count' => $sellerRows->count(),
                        'overdue_count' => $sellerRows->where('is_overdue', true)->count(),
                        'amount_total' => $sellerRows->sum('project_amount'),
                        'rows' => $sellerRows->values(),
                    ])
                    ->sortBy('seller_name')
                    ->values();

                return [
                    'status' => $status,
                    'threshold_label' => $config['threshold_label'],
                    'note' => $config['note'],
                    'is_configured' => $config['is_configured'],
                    'count' => $rows->count(),
                    'overdue_count' => $rows->where('is_overdue', true)->count(),
                    'amount_total' => $rows->sum('project_amount'),
                    'seller_groups' => $sellerGroups,
                    'rows' => $rows,
                ];
            })
            ->filter(fn (array $group) => $selectedStatuses !== [] || $group['count'] > 0)
            ->values();

        return [
            'generatedAt' => $now->toDateTimeString(),
            'sellers' => $sellers,
            'statusOptions' => collect($this->pipelineStatusOrder())->merge($allStatuses)->unique()->values(),
            'orderTypeOptions' => $orderTypes,
            'productLineOptions' => $productLines,
            'filters' => [
                'seller_id' => $sellerId,
                'overdue_only' => $overdueOnly,
                'statuses' => $selectedStatuses,
                'order_types' => $selectedOrderTypes,
                'product_lines' => $selectedProductLines,
            ],
            'totals' => [
                'statuses' => $groups->count(),
                'configured_statuses' => $groups->where('is_configured', true)->count(),
                'orders' => $groups->sum('count'),
                'overdue_orders' => $groups->sum('overdue_count'),
                'amount' => $groups->sum('amount_total'),
            ],
            'groups' => $groups,
        ];
    }

    public function buildForScheduledEmail(): array
    {
        return $this->build(['overdue_only' => true]);
    }

    public function pipelineStatusOrder(): array
    {
        return [
            OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
            OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
            OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
            OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
            OrderStatusEnum::QUALIFIED->value,
            OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value,
            OrderStatusEnum::PENDING_ASSIGNMENT->value,
            OrderStatusEnum::REQUEST_RE_SCHEDULE->value,
            OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
            OrderStatusEnum::FOLLOW_UP->value,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
            OrderStatusEnum::STAND_BY->value,
            OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value,
            OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
            OrderStatusEnum::LOST_CONTRACT->value,
            OrderStatusEnum::PENDING_FINANCING_OR_DEPOSIT->value,
            OrderStatusEnum::PENDING_HOA_APPROVAL->value,
            OrderStatusEnum::RECTIFICATION_OF_MEASURES->value,
            OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
            OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
            OrderStatusEnum::FILE_REVIEW->value,
            OrderStatusEnum::CLOSED_WON->value,
            OrderStatusEnum::ACCOUNT_RECEIPT->value,
            OrderStatusEnum::REVIEW->value,
            OrderStatusEnum::PLANNED->value,
            OrderStatusEnum::REPLANNED->value,
            OrderStatusEnum::MATERIALS_RECEIVED->value,
            OrderStatusEnum::CONFIRMED->value,
            OrderStatusEnum::RESCHEDULE->value,
            OrderStatusEnum::EXECUTION->value,
            OrderStatusEnum::ON_HOLD->value,
            OrderStatusEnum::SUPERVISION->value,
            OrderStatusEnum::INSPECTION->value,
            OrderStatusEnum::FINISH->value,
            OrderStatusEnum::FINAL_INSPECTION->value,
            OrderStatusEnum::FINAL_COLLECT->value,
            OrderStatusEnum::COMPLETE->value,
        ];
    }

    private function resolveArrayFilter(mixed $value, array $allowedValues): array
    {
        $values = is_array($value) ? $value : [$value];

        return collect($values)
            ->flatMap(fn ($item) => is_string($item) ? explode(',', $item) : [$item])
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn (string $item) => $item !== '' && in_array($item, $allowedValues, true))
            ->unique()
            ->values()
            ->all();
    }

    private function statusConfigs(): array
    {
        return [
            [
                'status' => OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
                'is_configured' => true,
                'hours' => 24,
                'threshold_label' => '24 hours',
                'note' => 'Overdue after 24 hours from when the order entered NEW REQUEST.',
            ],
            [
                'status' => OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
                'is_configured' => true,
                'hours' => 72,
                'threshold_label' => '72 hours (3 days)',
                'note' => 'Overdue after 72 hours from when the order entered REQUEST FOLLOW UP.',
            ],
            [
                'status' => OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
                'is_configured' => true,
                'hours' => 14 * 24,
                'threshold_label' => '14 days',
                'note' => 'Overdue after 14 days from when the order entered REQUEST STAND BY.',
            ],
            [
                'status' => OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
                'is_configured' => true,
                'hours' => null,
                'threshold_label' => 'Residential: 2 days. Commercial: 7 days.',
                'note' => 'Residential uses appointment date when available; otherwise status entry date. Commercial uses status entry date.',
            ],
            [
                'status' => OrderStatusEnum::FOLLOW_UP->value,
                'is_configured' => true,
                'hours' => 45 * 24,
                'threshold_label' => '45 days',
                'note' => 'Overdue is calculated from the first FOLLOW UP date, matching the board color rule.',
            ],
            [
                'status' => OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
                'is_configured' => true,
                'hours' => 45 * 24,
                'threshold_label' => '45 days',
                'note' => 'Overdue is calculated from the first FOLLOW UP date, matching the board color rule.',
            ],
            [
                'status' => OrderStatusEnum::STAND_BY->value,
                'is_configured' => true,
                'hours' => 120 * 24,
                'threshold_label' => '120 days',
                'note' => 'Overdue after 120 days from when the order entered STAND BY.',
            ],
        ];
    }

    private function mapOrderRow(Order $order, array $config, Carbon $now): array
    {
        $stageEnteredAt = $this->resolveCurrentStatusEnteredAt($order);
        [$referenceAt, $thresholdHours] = $this->resolveReference($order, $config, $stageEnteredAt);
        $isOverdue = $referenceAt !== null
            && $thresholdHours !== null
            && $referenceAt->copy()->addHours($thresholdHours)->lessThanOrEqualTo($now);
        $sellerNames = $order->owners->pluck('name')->filter()->implode(', ');
        $creatorStatusEntry = $order->orderStatus->sortBy('created_at')->first();
        $creatorName = trim((string) ($creatorStatusEntry?->user?->name ?? $order->user?->name ?? ''));
        $groupLabel = $sellerNames !== '' ? $sellerNames : ($creatorName !== '' ? $creatorName : 'Unassigned');
        $groupSource = $sellerNames !== '' ? 'seller' : 'creator';

        return [
            'id' => $order->id,
            'order_name' => $order->name,
            'order_label' => $order->name ? "#{$order->id} - {$order->name}" : "#{$order->id}",
            'status' => $order->status,
            'order_type' => $order->order_type,
            'product_line' => $order->product_line,
            'project_amount' => (float) ($order->project_amount ?? 0),
            'seller_name' => $sellerNames,
            'created_by_name' => $creatorName,
            'group_label' => $groupLabel,
            'group_source' => $groupSource,
            'days_in_stage' => $stageEnteredAt?->diffInDays($now) ?? 0,
            'created_at' => $order->created_at?->toDateTimeString(),
            'stage_entered_at' => $stageEnteredAt?->toDateTimeString(),
            'is_overdue' => $isOverdue,
        ];
    }

    private function resolveCurrentStatusEnteredAt(Order $order): ?Carbon
    {
        $statusHistoryEntry = $order->orderStatus
            ->where('status', $order->status)
            ->sortByDesc('created_at')
            ->first();

        return $statusHistoryEntry?->created_at
            ? Carbon::parse($statusHistoryEntry->created_at)
            : ($order->created_at ? Carbon::parse($order->created_at) : null);
    }

    private function resolveReference(Order $order, array $config, ?Carbon $stageEnteredAt): array
    {
        if ($config['status'] === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value) {
            $orderType = strtoupper(trim((string) $order->order_type));

            if ($orderType === OrderTypeEnum::RESIDENTIAL->value) {
                $appointmentAt = $order->schedule_appointment
                    ? Carbon::parse($order->schedule_appointment)
                    : null;

                return [$appointmentAt ?? $stageEnteredAt, 48];
            }

            if ($orderType === OrderTypeEnum::COMMERCIAL->value) {
                return [$stageEnteredAt, 168];
            }

            return [$stageEnteredAt, null];
        }

        if (in_array($config['status'], [OrderStatusEnum::FOLLOW_UP->value, OrderStatusEnum::FOLLOW_UP_PROJECTS->value], true)) {
            $followUpStartedAt = $order->orderStatus
                ->where('status', OrderStatusEnum::FOLLOW_UP->value)
                ->sortBy('created_at')
                ->first()?->created_at;

            return [
                $followUpStartedAt ? Carbon::parse($followUpStartedAt) : null,
                $config['hours'],
            ];
        }

        return [$stageEnteredAt, $config['hours']];
    }
}
