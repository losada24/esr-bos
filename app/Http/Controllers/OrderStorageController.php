<?php

namespace App\Http\Controllers;

use App\Enum\ContactSourceEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\RoleEnum;
use App\Enum\StatusUserEnum;
use App\Models\Order;
use App\Models\Tag;
use App\Models\User;
use App\Support\OrderBoardFilter;
use App\Support\OrderPipelineSort;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderStorageController extends Controller
{
    private const ORDER_STORAGE_PAGE_SIZE = 20;

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $sort = OrderPipelineSort::resolveFromRequest($request);
        $filters = $request->only([
            'filter_field',
            'filter_value',
            'filter_value_secondary',
            'filter_op',
            'filter_value_min',
            'filter_value_max'
        ]);
        $filters['filters'] = $request->input('filters', []);
        $filters['filter_match'] = $request->input('filter_match', 'and');
        if (is_string($filters['filters'])) {
            $decoded = json_decode($filters['filters'], true);
            $filters['filters'] = is_array($decoded) ? $decoded : [];
        }
        $filterRows = is_array($filters['filters']) ? $filters['filters'] : [];
        $filterMatch = (string) ($filters['filter_match'] ?? 'and');
        $hasMultiFilters = count($filterRows) > 0;
        $useEsrAmounts = OrderBoardFilter::hasEsrProductLineFilter($filters);

        $storageStatuses = $this->storageStatuses();
        $paginatedStatuses = $this->paginatedStorageStatuses();

        $data = collect($storageStatuses)->map(function (string $status) use ($user, $paginatedStatuses, $filters, $filterRows, $filterMatch, $hasMultiFilters, $sort, $useEsrAmounts) {
            $ordersQuery = $this->storageOrdersForStatusQuery($status, $user);
            $ordersQuery = $hasMultiFilters
                ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
                : OrderBoardFilter::apply($ordersQuery, $filters);
            $totalProjectAmount = OrderBoardFilter::totalAmount($ordersQuery, $useEsrAmounts);

            if (in_array($status, $paginatedStatuses, true)) {
                $total = (clone $ordersQuery)->count();
                OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
                $orders = $ordersQuery
                    ->with($this->orderStorageRelations())
                    ->withCount('childOrders')
                    ->limit(self::ORDER_STORAGE_PAGE_SIZE)
                    ->get();
            } else {
                OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
                $orders = $ordersQuery
                    ->with($this->orderStorageRelations())
                    ->withCount('childOrders')
                    ->get();
                $total = $orders->count();
            }

            return [
                'id' => $status,
                'title' => $status,
                'total_tasks' => $total,
                'total_project_amount' => $totalProjectAmount,
                'tasks' => $orders->map(fn (Order $order) => $this->mapOrderToTask($order))->values(),
            ];
        });

        $ownerOptions = User::role(RoleEnum::OWNER->value)
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name');

        if ($this->isOwnerRestricted($user)) {
            $ownerOptions->whereIn('id', $user->accessibleOwnerIds());
        }

        $supervisors = User::role(RoleEnum::SUPERVISOR->value)
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('taggable_type', Order::class)
            ->whereNotNull('name')
            ->select('name')
            ->orderBy('name')
            ->get()
            ->map(fn ($tag) => trim((string) $tag->name))
            ->filter(fn ($name) => $name !== '')
            ->unique(fn ($name) => mb_strtolower($name))
            ->values()
            ->map(fn ($name) => ['name' => $name]);

        $createdByUsers = User::query()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name')
            ->get();

        $sources = [
            ContactSourceEnum::TIK_TOK->value,
            ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::SIGNS->value,
            ContactSourceEnum::WALK_IN->value,
            ContactSourceEnum::ESW_REFER->value,
            ContactSourceEnum::ESR_REFER->value,
            ContactSourceEnum::YOUTUBE->value,
            ContactSourceEnum::NEW_ORDER->value,
            ContactSourceEnum::GOOGLE_ADS->value,
            ContactSourceEnum::SAME_AS_ORDER->value,
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
        ];

        $orderTypes = [
            OrderTypeEnum::RESIDENTIAL->value,
            OrderTypeEnum::COMMERCIAL->value,
            OrderTypeEnum::SUPPLY->value,
        ];
        $productLines = array_map(fn (ProductLineEnum $productLine) => $productLine->value, ProductLineEnum::cases());

        return Inertia::render('OrderStorage/Index', [
            'data' => $data,
            'statuses' => $storageStatuses,
            'owners' => $ownerOptions->get(),
            'supervisors' => $supervisors,
            'created_by_users' => $createdByUsers,
            'tags' => $tags,
            'sources' => $sources,
            'order_types' => $orderTypes,
            'product_lines' => $productLines,
            'filters' => $filters,
            'sort' => $sort,
        ]);
    }

    public function tasks(Request $request): JsonResponse
    {
        $user = auth()->user();
        $sort = OrderPipelineSort::resolveFromRequest($request);
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = (int) $request->query('per_page', self::ORDER_STORAGE_PAGE_SIZE);
        $perPage = max(1, min(100, $perPage));

        if (!in_array($status, $this->storageStatuses(), true)) {
            return response()->json([
                'message' => 'Invalid status.'
            ], 422);
        }

        if (!in_array($status, $this->paginatedStorageStatuses(), true)) {
            return response()->json([
                'message' => 'Invalid status.'
            ], 422);
        }

        $filters = $request->only([
            'filter_field',
            'filter_value',
            'filter_value_secondary',
            'filter_op',
            'filter_value_min',
            'filter_value_max'
        ]);
        $filters['filters'] = $request->input('filters', []);
        $filters['filter_match'] = $request->input('filter_match', 'and');
        if (is_string($filters['filters'])) {
            $decoded = json_decode($filters['filters'], true);
            $filters['filters'] = is_array($decoded) ? $decoded : [];
        }
        $filterRows = is_array($filters['filters']) ? $filters['filters'] : [];
        $filterMatch = (string) ($filters['filter_match'] ?? 'and');
        $hasMultiFilters = count($filterRows) > 0;

        $ordersQuery = $this->storageOrdersForStatusQuery($status, $user);
        $ordersQuery = $hasMultiFilters
            ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
            : OrderBoardFilter::apply($ordersQuery, $filters);
        $total = (clone $ordersQuery)->count();
        OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
        $orders = $ordersQuery
            ->with($this->orderStorageRelations())
            ->withCount('childOrders')
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

    /**
     * @return array<int, string>
     */
    private function storageStatuses(): array
    {
        return [
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

    /**
     * @return array<int, string>
     */
    private function paginatedStorageStatuses(): array
    {
        return [
            OrderStatusEnum::COMPLETE->value,
        ];
    }

    private function storageOrdersForStatusQuery(string $status, ?User $user): Builder
    {
        $query = Order::query()->where('status', $status);

        if ($this->isOwnerRestricted($user)) {
            $query->accessibleToOwner($user);
        }

        return $query;
    }

    private function orderStorageRelations(): array
    {
        return [
            'client.companyContact',
            'owners',
            'user',
            'tags:id,name,color,taggable_id,taggable_type',
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
            'is_parent_order' => (int) ($order->child_orders_count ?? 0) > 0,
            'child_orders_count' => (int) ($order->child_orders_count ?? 0),
            'project_amount' => $order->project_amount ? (float) $order->project_amount : null,
            'esr_cost' => $order->esr_cost !== null ? (float) $order->esr_cost : null,
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
            'product_line' => $order->product_line,
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

    private function isOwnerRestricted(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole(RoleEnum::OWNER->value) && !$user->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::ACCOUNT_MANAGER->value,
            RoleEnum::ACCOUNTING->value,
            RoleEnum::FRONTDESK_ADMIN->value,
            'FRONTDESK_ADMIN',
        ]);
    }
}
