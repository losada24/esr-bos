<?php

namespace App\Http\Controllers;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Inertia\Response;

class OrderProcessingController extends Controller
{
    private const ORDER_PROCESSING_PAGE_SIZE = 20;

    public function index(): Response
    {
        $user = auth()->user();
        $processingStatuses = $this->processingStatuses();
        $paginatedStatuses = $this->paginatedProcessingStatuses();
        $pipelineStatusMap = $this->statusPipelineMap();
        $queryStatuses = array_keys($pipelineStatusMap);
        $nonPaginatedQueryStatuses = array_values(array_diff($queryStatuses, $paginatedStatuses));

        $ordersQuery = Order::with($this->orderProcessingRelations())
            ->whereIn('status', $nonPaginatedQueryStatuses);

        if ($this->isOwnerRestricted($user)) {
            $ordersQuery->whereHas('owners', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        $orders = $ordersQuery->get();

        $determinePipelineStatus = function (Order $order) use ($pipelineStatusMap) {
            return $this->determinePipelineStatus($order, $pipelineStatusMap);
        };

        $data = collect($processingStatuses)->map(function (string $status) use ($orders, $determinePipelineStatus, $paginatedStatuses, $user) {
            if (in_array($status, $paginatedStatuses, true)) {
                $closedWonQuery = $this->closedWonOrdersQuery($user);
                $total = (clone $closedWonQuery)->count();
                $closedWonOrders = $closedWonQuery
                    ->with($this->orderProcessingRelations())
                    ->orderByDesc('updated_at')
                    ->limit(self::ORDER_PROCESSING_PAGE_SIZE)
                    ->get();

                return [
                    'id' => $status,
                    'title' => $status,
                    'total_tasks' => $total,
                    'tasks' => $closedWonOrders->map(fn (Order $order) => $this->mapOrderToTask($order))->values(),
                ];
            }

            $ordersByStatus = $orders->filter(function (Order $order) use ($status, $determinePipelineStatus) {
                return $determinePipelineStatus($order) === $status;
            });

            return [
                'id' => $status,
                'title' => $status,
                'total_tasks' => $ordersByStatus->count(),
                'tasks' => $ordersByStatus->map(fn (Order $order) => $this->mapOrderToTask($order))->values(),
            ];
        });

        return Inertia::render('OrderProcessing/Index', [
            'data' => $data,
        ]);
    }

    public function tasks(Request $request): JsonResponse
    {
        $user = auth()->user();
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', self::ORDER_PROCESSING_PAGE_SIZE);
        $perPage = max(1, min(100, $perPage));

        if (!in_array($status, $this->processingStatuses(), true)) {
            return response()->json([
                'message' => 'Invalid status.'
            ], 422);
        }

        if (!in_array($status, $this->paginatedProcessingStatuses(), true)) {
            return response()->json([
                'message' => 'Invalid status.'
            ], 422);
        }

        $ordersQuery = $this->closedWonOrdersQuery($user);
        $total = (clone $ordersQuery)->count();
        $orders = $ordersQuery
            ->with($this->orderProcessingRelations())
            ->orderByDesc('updated_at')
            ->forPage($page, $perPage)
            ->get();

        $tasks = $orders->map(fn (Order $order) => $this->mapOrderToTask($order))->values();
        $hasMore = ($page * $perPage) < $total;

        return response()->json([
            'status' => $status,
            'tasks' => $tasks,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => $hasMore,
            'next_page' => $hasMore ? $page + 1 : null,
        ]);
    }

    private function determinePipelineStatus(Order $order, array $pipelineStatusMap): string
    {
        if ($order->status === OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value) {
            if ($order->is_supply) {
                return OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value;
            }

            return OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value;
        }

        return $pipelineStatusMap[$order->status] ?? $order->status;
    }

    private function paginatedProcessingStatuses(): array
    {
        return [
            OrderStatusEnum::CLOSED_WON->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function processingStatuses(): array
    {
        return [
            OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
            OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
            OrderStatusEnum::FILE_REVIEW->value,
            OrderStatusEnum::CLOSED_WON->value,
        ];
    }

    /**
     * Map order statuses to their corresponding column in the processing board.
     *
     * @return array<string, string>
     */
    private function statusPipelineMap(): array
    {
        return [
            OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value => OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
            OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value => OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
            OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value => OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
            OrderStatusEnum::FILE_REVIEW->value => OrderStatusEnum::FILE_REVIEW->value,
            OrderStatusEnum::CLOSED_WON->value => OrderStatusEnum::CLOSED_WON->value,
        ];
    }

    private function isOwnerRestricted(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole(RoleEnum::OWNER->value) && !$user->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::ACCOUNT_MANAGER->value,
        ]);
    }

    private function closedWonOrdersQuery(?User $user): Builder
    {
        $query = Order::query()->where('status', OrderStatusEnum::CLOSED_WON->value);

        if ($this->isOwnerRestricted($user)) {
            $query->whereHas('owners', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        return $query;
    }

    private function orderProcessingRelations(): array
    {
        return [
            'client.companyContact',
            'owners',
            'user',
            'tags',
            'orderCompanyContacts.companyContact',
        ];
    }

    private function mapOrderToTask(Order $order): array
    {
        return [
            'id' => $order->id,
            'title' => $order->name ?? 'No Title',
            'client_id' => $order->client_id,
            'date_edited' => optional($order->updated_at)->format('M d, Y h:i A'),
            'date' => optional($order->created_at)->format('M d, Y h:i A'),
            'schedule_appointment' => $order->schedule_appointment
                ? Carbon::parse($order->schedule_appointment)->format('M d, Y h:i A')
                : null,
            'schedule_appointment_iso' => $order->schedule_appointment
                ? Carbon::parse($order->schedule_appointment)->format('Y-m-d\TH:i')
                : null,
            'phone' => optional($order->client)->phone,
            'vip_clients' => (bool) (optional($order->client)->vip_clients ?? false),
            'created_by' => $order->user->name ?? null,
            'is_supply' => (bool) ($order->is_supply ?? false),
            'project_amount' => $order->project_amount ? (float) $order->project_amount : null,
            'down_payment' => $order->down_payment ? (float) $order->down_payment : null,
            'job_address' => $order->job_address,
            'city' => $order->city,
            'job_state' => $order->job_state,
            'job_zip' => $order->job_zip,
            'method_of_payment' => $order->method_of_payment,
            'type_of_financing' => $order->type_of_financing,
            'owner_ids' => $order->owners->pluck('id')->values(),
            'owners' => $order->owners->map(fn ($owner) => [
                'id' => $owner->id,
                'name' => $owner->name,
            ])->values(),
            'order_type' => $order->order_type,
            'bid_due_date' => $this->resolveBidDueDate($order),
            'tags' => ($order->tags ?? collect())->map(function ($tag) {
                return [
                    'name' => $tag->name,
                    'color' => $tag->color,
                ];
            })->values(),
        ];
    }

    private function resolveBidDueDate(Order $order): ?string
    {
        $selectedContact = $order->orderCompanyContacts
            ->firstWhere('is_selected', true)
            ?? ($order->orderCompanyContacts->count() === 1 ? $order->orderCompanyContacts->first() : null);

        $bidDueDate = $selectedContact?->companyContact?->bid_due_date ?? $order->bid_due_date;

        if ($bidDueDate instanceof \DateTimeInterface) {
            return $bidDueDate->format('Y-m-d');
        }

        return $bidDueDate ? Carbon::parse($bidDueDate)->format('Y-m-d') : null;
    }
}
