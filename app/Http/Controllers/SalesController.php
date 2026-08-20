<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderPipeline;
use App\Enum\ContactSourceEnum;
use App\Enum\FrontdeskStatusEnum;
use App\Enum\LostReasonfrontdeskEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\MethodOfPayment;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\TypeOfFinancing;
use App\Enum\RoleEnum;
use App\Enum\StatusUserEnum;
use App\Http\Requests\StoreFrontDeskOrderRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Models\Tag;
use App\Models\OrderCompanyContact;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Traits\OrderEmails;
use App\Traits\Snapshot;
use Illuminate\Http\JsonResponse;
use App\Support\PaymentScheduleCalculator;
use App\Support\PaymentInstallmentPresenter;
use App\Support\OrderFinancialEventLogger;
use App\Support\OrderPaymentInformationAuditLogger;
use App\Support\PaymentScheduleTemplates;
use App\Support\OrderBoardFilter;
use App\Support\OrderClientEmailDeliveryLogger;
use App\Support\OrderClientEmailManager;
use App\Support\OrderPipelineSort;

class SalesController extends Controller
{
    use OrderEmails, Snapshot;
    private const SALES_PAGE_SIZE = 20;
    private const BOARD_SALES = 'sales';
    private const BOARD_COMMERCIAL = 'commercial';

    private function orderClientEmailDeliveryLogger(): OrderClientEmailDeliveryLogger
    {
        return app(OrderClientEmailDeliveryLogger::class);
    }

  public function index(Request $request)
  {
    return $this->renderBoard($request, self::BOARD_SALES);
  }

  public function commercialIndex(Request $request)
  {
    return $this->renderBoard($request, self::BOARD_COMMERCIAL);
  }

  private function renderBoard(Request $request, string $board)
  {
        $user = auth()->user();
        $sort = OrderPipelineSort::resolveFromRequest($request);
        $filters = $request->only(['filter_field', 'filter_value', 'filter_value_secondary', 'filter_op', 'filter_value_min', 'filter_value_max']);
        $filters['filters'] = $request->input('filters', []);
        $filters['filter_match'] = $request->input('filter_match', 'and');
        if (is_string($filters['filters'])) {
            $decoded = json_decode($filters['filters'], true);
            $filters['filters'] = is_array($decoded) ? $decoded : [];
        }
        $filterRows = is_array($filters['filters']) ? $filters['filters'] : [];
        $filterMatch = (string) ($filters['filter_match'] ?? 'and');
        $hasMultiFilters = count($filterRows) > 0;

    $salesStatuses = $this->salesStatusesForBoard($board);
    $ownerVisibleStatuses = $this->ownerVisibleSalesStatuses();
    $visibleStatuses = $salesStatuses;
    $paginatedStatuses = $this->paginatedSalesStatuses();
    $lossReasonFrontdesk = [
        LostReasonfrontdeskEnum::NO_RESPONSE_FROM_CLIENT->value,
        LostReasonfrontdeskEnum::DEALER->value,
        LostReasonfrontdeskEnum::FAKE->value,
        LostReasonfrontdeskEnum::WORK->value,
        LostReasonfrontdeskEnum::STOCK->value,
        LostReasonfrontdeskEnum::OTHER_REASONS->value,
    ];
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
            ContactSourceEnum::NEW_ORDER ->value,
            ContactSourceEnum::GOOGLE_ADS->value,
            ContactSourceEnum::SAME_AS_ORDER->value,
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
    ];

    $order_types = $board === self::BOARD_COMMERCIAL
      ? [OrderTypeEnum::COMMERCIAL->value]
      : [OrderTypeEnum::RESIDENTIAL->value, OrderTypeEnum::SUPPLY->value];
    if ($this->isOwnerRestricted($user)) {
        $visibleStatuses = $ownerVisibleStatuses;
    }

    $useEsrAmounts = OrderBoardFilter::hasEsrProductLineFilter($filters);

    // Armar el arreglo que espera el componente React
    $data = collect($visibleStatuses)->map(function ($status) use ($user, $paginatedStatuses, $filters, $filterRows, $filterMatch, $hasMultiFilters, $sort, $useEsrAmounts, $board) {
        $ordersQuery = $this->salesOrdersForStatusQuery($status, $user, $board);
        $ordersQuery = $hasMultiFilters
            ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
            : OrderBoardFilter::apply($ordersQuery, $filters);
        $totalProjectAmount = OrderBoardFilter::totalAmount($ordersQuery, $useEsrAmounts);

        if (in_array($status, $paginatedStatuses, true)) {
            $total = (clone $ordersQuery)->count();
            OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
            $orders = $ordersQuery
                ->with($this->salesOrderRelations())
                ->limit(self::SALES_PAGE_SIZE)
                ->get();
        } else {
            OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
            $orders = $ordersQuery
                ->with($this->salesOrderRelations())
                ->get();
            $total = $orders->count();
        }

        return [
            'id' => $status, // puedes usar el valor del estado como id
            'title' => $status,
            'total_tasks' => $total,
            'total_project_amount' => $totalProjectAmount,
            'tasks' => $orders->map(function ($order) use ($status) {
                return $this->mapSalesOrderToTask($order, $status);
            })->values(),
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

    return Inertia::render('Sales/Index', [
      'data' => $data,
      'lossReasonFrontdesk' => $lossReasonFrontdesk,
      'sources' => $sources,
      'order_types' => $order_types,
      'product_lines' => array_map(fn (ProductLineEnum $productLine) => $productLine->value, ProductLineEnum::cases()),
      'statuses' => $visibleStatuses,
      'owners' => $ownerOptions->get(),
      'supervisors' => $supervisors,
      'created_by_users' => $createdByUsers,
      'tags' => $tags,
      'filters' => $filters,
      'sort' => $sort,
      'pageTitle' => $board === self::BOARD_COMMERCIAL ? 'Commercial' : 'Sales',
      'indexRouteName' => $board === self::BOARD_COMMERCIAL ? 'commercial.index' : 'sales.index',
      'tasksRouteName' => $board === self::BOARD_COMMERCIAL ? 'commercial.tasks' : 'sales.tasks',
      'methods_of_payment' => array_values(array_filter(
        array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()),
        fn (string $method) => !in_array($method, [
          MethodOfPayment::CHECK->value,
          MethodOfPayment::ZELLE->value,
          MethodOfPayment::AIA->value,
        ], true)
      )),
      'type_of_financing' => array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases()),
      'payment_schedule_templates' => PaymentScheduleTemplates::templates(),
    ]);
  }

  public function tasks(Request $request): JsonResponse
  {
    return $this->boardTasks($request, self::BOARD_SALES);
  }

  public function commercialTasks(Request $request): JsonResponse
  {
    return $this->boardTasks($request, self::BOARD_COMMERCIAL);
  }

  private function boardTasks(Request $request, string $board): JsonResponse
  {
    $user = auth()->user();
    $sort = OrderPipelineSort::resolveFromRequest($request);
    $status = (string) $request->query('status', '');
    $page = max(1, (int) $request->query('page', 1));
    $perPage = (int) $request->query('per_page', self::SALES_PAGE_SIZE);
    $perPage = max(1, min(100, $perPage));

    $allowedStatuses = $this->salesStatusesForBoard($board);
    if ($this->isOwnerRestricted($user)) {
      $allowedStatuses = $this->ownerVisibleSalesStatuses();
    }

    if (!in_array($status, $allowedStatuses, true)) {
      return response()->json([
        'message' => 'Invalid status.'
      ], 422);
    }

    if (!in_array($status, $this->paginatedSalesStatuses(), true)) {
      return response()->json([
        'message' => 'Invalid status.'
      ], 422);
    }

    $filters = $request->only(['filter_field', 'filter_value', 'filter_value_secondary', 'filter_op', 'filter_value_min', 'filter_value_max']);
    $filters['filters'] = $request->input('filters', []);
    $filters['filter_match'] = $request->input('filter_match', 'and');
    if (is_string($filters['filters'])) {
        $decoded = json_decode($filters['filters'], true);
        $filters['filters'] = is_array($decoded) ? $decoded : [];
    }
    $filterRows = is_array($filters['filters']) ? $filters['filters'] : [];
    $filterMatch = (string) ($filters['filter_match'] ?? 'and');
    $hasMultiFilters = count($filterRows) > 0;
    $ordersQuery = $this->salesOrdersForStatusQuery($status, $user, $board);
    $ordersQuery = $hasMultiFilters
        ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
        : OrderBoardFilter::apply($ordersQuery, $filters);
    $total = (clone $ordersQuery)->count();
    OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
    $orders = $ordersQuery
      ->with($this->salesOrderRelations())
      ->forPage($page, $perPage)
      ->get();

    $tasks = $orders->map(function (Order $order) use ($status) {
      return $this->mapSalesOrderToTask($order, $status);
    })->values();

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

  private function salesPipelineStatuses(): array
  {
    return [
      OrderStatusEnum::PENDING_FINANCING_OR_DEPOSIT->value,
      OrderStatusEnum::PENDING_HOA_APPROVAL->value,
      OrderStatusEnum::RECTIFICATION_OF_MEASURES->value,
      OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
      OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
    ];
  }

  private function paginatedSalesStatuses(): array
  {
    return [
      OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
      OrderStatusEnum::LOST_CONTRACT->value,
    ];
  }

  private function salesOrderRelations(): array
  {
    return [
      'client.companyContact',
      'client.companyContacts',
      'user',
      'owners',
      'orderStatus',
      'tags:id,name,color,taggable_id,taggable_type',
      'orderCompanyContacts.companyContact',
      'orderCompanyContacts.client.companyContacts',
      'orderCompanyContacts.source',
    ];
  }

  private function salesOrdersForStatusQuery(string $status, ?User $user, string $board = self::BOARD_SALES): Builder
  {
    $query = Order::query();

    if ($status === OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value) {
      $pipelineStatuses = $this->salesPipelineStatuses();
      $query->where(function ($query) use ($status, $pipelineStatuses) {
        $query->where('status', $status)
          ->orWhere(function ($query) use ($status, $pipelineStatuses) {
            $query->whereIn('status', $pipelineStatuses)
              ->whereHas('orderStatus', function ($query) use ($status) {
                $query->where('status', $status);
              });
          });
      });
    } else {
      $query->where('status', $status);
    }

    if ($this->isOwnerRestricted($user)) {
      $query->accessibleToOwner($user);
    }

    $this->applyBoardOrderTypeFilter($query, $board);

    return $query;
  }

  private function applyBoardOrderTypeFilter(Builder $query, string $board): void
  {
    if ($board === self::BOARD_COMMERCIAL) {
      $query->where('orders.order_type', OrderTypeEnum::COMMERCIAL->value);
      return;
    }

    $query->where(function (Builder $query) {
      $query->whereNull('orders.order_type')
        ->orWhere('orders.order_type', '!=', OrderTypeEnum::COMMERCIAL->value);
    });
  }

  private function contractSignedPipelineStatus(Order $order, bool $pendingFinancingOrDeposit, bool $pendingHoaApproval): string
  {
    if ($pendingFinancingOrDeposit) {
      return OrderStatusEnum::PENDING_FINANCING_OR_DEPOSIT->value;
    }

    if ($order->is_supply) {
      return OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value;
    }

    if ($pendingHoaApproval) {
      return OrderStatusEnum::PENDING_HOA_APPROVAL->value;
    }

    return OrderStatusEnum::RECTIFICATION_OF_MEASURES->value;
  }

  private function mapSalesOrderToTask(Order $order, string $status): array
  {
    $clientEmailManager = $this->clientEmailManager();
    $statusHistoryEntry = $order->orderStatus
      ->where('status', $status)
      ->sortByDesc('created_at')
      ->first();
    $statusCreatedAt = optional($statusHistoryEntry)->created_at ?? $order->created_at;
    $followUpStartedAt = optional(
      $order->orderStatus
        ->where('status', OrderStatusEnum::FOLLOW_UP->value)
        ->sortBy('created_at')
        ->first()
    )->created_at;
    $selectedContact = $order->orderCompanyContacts
      ->firstWhere('is_selected', true)
      ?? ($order->orderCompanyContacts->count() === 1 ? $order->orderCompanyContacts->first() : null);

    return [
      'id' => $order->id,
      'title' => $order->name ?? 'No Title',
      'order_number' => $order->order_number,
      'client_id' => $order->client_id ?? null,
      //'description' => $order->notes ?? '',
      'date_edited' => optional($order->updated_at)->format('M d, Y h:i A'),
      'date' => optional($order->created_at)->format('M d, Y h:i A'),
      'status_created_at_iso' => optional($statusCreatedAt)->toIso8601String(),
      'follow_up_started_at_iso' => optional($followUpStartedAt)->toIso8601String(),
      //'names' => $order->user->name ?? 'No Name',
      //'precio' => $order->price ?? 0,
      'schedule_appointment' => $order->schedule_appointment ? Carbon::parse($order->schedule_appointment)->format('M d, Y h:i A') : null,
      'schedule_appointment_iso' => $order->schedule_appointment ? Carbon::parse($order->schedule_appointment)->format('Y-m-d\TH:i') : null,
      'phone'=> optional($order->client)->phone,
      'vip_clients' => (bool) (optional($order->client)->vip_clients ?? false),
      'contact_email' => $clientEmailManager->resolveRecipient($order),
      'client_email_selection' => $clientEmailManager->selectionForOrder($order),
      'client_email_override' => $order->client_email_override,
      'client_email_options' => $clientEmailManager->optionsForOrder($order, $selectedContact),
      'do_not_send_email' => (bool) ($order->do_not_send_email ?? false),
      'created_by' => $order->user->name ?? null,
      'is_supply' => (bool) ($order->is_supply ?? false),
      'name_check' => (bool) ($order->name_check ?? false),
      'address_check' => (bool) ($order->address_check ?? false),
      'amount_check' => (bool) ($order->amount_check ?? false),
      'email_check' => (bool) ($order->email_check ?? false),
      'city_permits' => (bool) ($order->city_permits ?? false),
      'association_permits' => (bool) ($order->association_permits ?? false),
      'pending_financing_or_deposit' => $order->pending_financing_or_deposit,
      'pending_hoa_approval' => $order->pending_hoa_approval,
      'project_amount' => $order->project_amount ? (float) $order->project_amount : 0,
      'esr_cost' => $order->esr_cost !== null ? (float) $order->esr_cost : null,
      'down_payment' => $order->down_payment ? (float) $order->down_payment : null,
      'job_address' => $order->job_address ?? null,
      'city' => $order->city ?? null,
      'job_state' => $order->job_state ?? null,
      'job_zip' => $order->job_zip ?? null,
      'method_of_payment' => $order->method_of_payment ?? null,
      'type_of_financing' => $order->type_of_financing ?? null,
      'owner_ids' => $order->owners->pluck('id')->values(),
      'owners' => $order->owners->map(fn ($owner) => [
        'id' => $owner->id,
        'name' => $owner->name,
      ])->values(),
      'order_type' => $order->order_type,
      'product_line' => $order->product_line,
      'bid_due_date' => $this->resolveBidDueDate($order),
      'order_company_contacts' => $order->orderCompanyContacts
        ->map(fn ($item) => $this->mapOrderCompanyContactForResponse($order, $item))
        ->values(),
      'tags'       => ($order->tags ?? collect())->map(function ($t) {
        return [
          'name'  => $t->name,
          'color' => $t->color,
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

    private function clientEmailManager(): OrderClientEmailManager
    {
        return app(OrderClientEmailManager::class);
    }

  private function mapOrderCompanyContactForResponse(Order $order, OrderCompanyContact $item): array
  {
    return [
      'id' => $item->id,
      'company_name' => $item->companyContact?->name,
      'company_email' => $item->companyContact?->email,
      'client_id' => $item->client_id,
      'client_name' => $item->client?->name,
      'client_email' => $item->client?->email,
      'client_secondary_email' => $item->client?->secondary_email,
      'is_selected' => (bool) ($item->is_selected ?? false),
      'client_email_options' => $this->clientEmailManager()->optionsForOrder($order, $item),
    ];
  }
  private function determineSalesBoardStatus(Order $order): ?string
  {
    $status = $order->status;
    if (in_array($status, $this->salesStatuses(), true)) {
      return $status;
    }
    if (in_array($status, $this->salesPipelineStatuses(), true)) {
      $hadContractSigned = $order->orderStatus->contains(function ($orderStatus) {
        return $orderStatus->status === OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value;
      });
      if ($hadContractSigned) {
        return OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value;
      }
    }

    return null;
  }

  public function calendar()
  {
    $user = auth()->user();

    $allOwners = $this->ownerListForCalendarAll($user);

    return Inertia::render('Sales/Calendar', [
      'owners' => $allOwners->map(fn ($owner) => [
        'id' => $owner->id,
        'name' => $owner->name,
      ])->values(),
      'legend' => $this->salesCalendarLegend(),
    ]);
  }

  public function calendarEvents(Request $request, int $year, int $month): JsonResponse
  {
    $user = auth()->user();
    $ownerFilter = $request->query('owner');
    $calendarTimezone = (string) config('app.timezone', 'UTC');

    $rangeStart = Carbon::createFromDate($year, $month, 1, $calendarTimezone)
      ->startOfMonth()
      ->subWeek()
      ->startOfDay();
    $rangeEnd = Carbon::createFromDate($year, $month, 1, $calendarTimezone)
      ->endOfMonth()
      ->addWeek()
      ->endOfDay();

    $ordersQuery = Order::with(['client', 'owners', 'user', 'orderCompanyContacts.companyContact', 'orderStatus'])
      ->whereNotNull('schedule_appointment')
      ->whereBetween('schedule_appointment', [
        $rangeStart->format('Y-m-d H:i:s'),
        $rangeEnd->format('Y-m-d H:i:s'),
      ])
      ->whereHas('owners');

    if (!empty($ownerFilter) && $ownerFilter !== 'all') {
      $allowedOwnerIds = $this->ownerListForCalendarAll($user)->pluck('id')->map(fn ($id) => (string) $id)->values();
      if (!$allowedOwnerIds->contains((string) $ownerFilter)) {
        return response()->json([]);
      }
      $ordersQuery->whereHas('owners', function ($query) use ($ownerFilter) {
        $query->where('users.id', $ownerFilter);
      });
    }

    if ($this->isOwnerRestricted($user)) {
      $ordersQuery->whereHas('owners', function ($query) use ($user) {
        $query->where('users.id', $user->id);
      });
    }

    $events = $ordersQuery->get()->map(function (Order $order) use ($calendarTimezone) {
      $appointmentStart = Carbon::parse($order->schedule_appointment, $calendarTimezone);
      $appointmentEnd = (clone $appointmentStart)->addHour();

      $client = $order->client;
      $clientName = $client?->name ?? 'Client';
      $owners = $order->owners->pluck('name')->filter();
      $sellerName = $order->user?->name ?? '';
      $selectedCompanyContact = $order->orderCompanyContacts
        ->firstWhere('is_selected', true)
        ?? $order->orderCompanyContacts->first();
      $companyName = $selectedCompanyContact?->companyContact?->name ?? '';
      $primaryLine = ($order->name ?? 'Order');
      $secondaryLine = $appointmentStart->format('h:i A') . ($owners->isNotEmpty() ? ' (' . $owners->implode(', ') . ')' : '');
      $tooltipParts = [
        'Client: ' . $clientName,
        'Status: ' . $order->status,
      ];
      if (!empty($order->city)) {
        $tooltipParts[] = 'City: ' . $order->city;
      }
      $tooltip = implode(' | ', $tooltipParts);

      return [
        'order_id' => $order->id,
        'title' => $primaryLine,
        'tooltip' => $tooltip,
        // Include timezone offset to avoid DST/local parsing ambiguities in the browser.
        'start' => $appointmentStart->format(\DateTimeInterface::ATOM),
        'end' => $appointmentEnd->format(\DateTimeInterface::ATOM),
        'color' => $this->salesCalendarEventColor($order),
        'type_of_event' => $order->status,
        'text' => $order->name ?? 'Order',
        'order_name' => $order->name ?? 'Order',
        'appointment_date' => $appointmentStart->format('M d, Y'),
        'appointment_time' => $appointmentStart->format('h:i A'),
        'owner_names' => $owners->implode(', '),
        'seller_name' => $sellerName,
        'client_name' => $clientName,
        'client_phone' => $client?->phone ?? '',
        'client_email' => $client?->email ?? '',
        'order_type' => $order->order_type ?? '',
        'is_supply' => (bool) ($order->is_supply ?? false),
        'vip_client' => (bool) ($client?->vip_clients ?? false),
        'company_name' => $companyName,
        'city' => $order->city ?? '',
        'job_address' => $order->job_address ?? '',
        'job_city' => $order->job_city ?? '',
        'job_state' => $order->job_state ?? '',
        'job_zip' => $order->job_zip ?? '',
        'secondary_label' => $secondaryLine,
      ];
    });

    return response()->json($events);
  }

  private function salesStatuses(): array
  {
    return [
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
    ];
  }

  private function salesStatusesForBoard(string $board): array
  {
    $hiddenStatus = $board === self::BOARD_COMMERCIAL
      ? OrderStatusEnum::PENDING_ASSIGNMENT->value
      : OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value;

    return array_values(array_filter(
      $this->salesStatuses(),
      fn (string $status) => $status !== $hiddenStatus
    ));
  }

  private function ownerVisibleSalesStatuses(): array
  {
    return array_values(array_filter(
      $this->salesStatuses(),
      fn (string $status) => !in_array($status, [
        OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value,
        OrderStatusEnum::PENDING_ASSIGNMENT->value,
      ], true)
    ));
  }

  private function salesCalendarLegend(): Collection
  {
    return collect([
      [
        'label' => 'Assigned Appointments',
        'color' => '#facc15',
      ],
      [
        'label' => 'Reschedule Appointments',
        'color' => '#f97316',
      ],
      [
        'label' => 'Completed Appointments',
        'color' => '#2563eb',
      ],
      [
        'label' => 'Closed Appointments',
        'color' => '#22c55e',
      ],
      [
        'label' => 'Canceled Appointments',
        'color' => '#ef4444',
      ],
    ]);
  }

  private function salesCalendarEventColor(Order $order): string
  {
    if ($order->status === OrderStatusEnum::LOST_CONTRACT->value) {
      return (float) ($order->project_amount ?? 0) > 0.0 ? '#2563eb' : '#ef4444';
    }

    if ($this->salesCalendarHasReachedContractSigned($order)) {
      return '#22c55e';
    }

    if ($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value) {
      return '#facc15';
    }

    return [
      OrderStatusEnum::FOLLOW_UP->value => '#2563eb',
      OrderStatusEnum::FOLLOW_UP_PROJECTS->value => '#2563eb',
      OrderStatusEnum::STAND_BY->value => '#2563eb',
      OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value => '#2563eb',
      OrderStatusEnum::REQUEST_RE_SCHEDULE->value => '#f97316',
    ][$order->status] ?? '#6b7280';
  }

  private function salesCalendarHasReachedContractSigned(Order $order): bool
  {
    if ($order->status === OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value) {
      return true;
    }

    if ($order->relationLoaded('orderStatus')) {
      return $order->orderStatus->contains(
        fn ($status) => $status->status === OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value
      );
    }

    return $order->hasReachedContractSigned();
  }

  private function isOwnerRestricted(?User $user): bool
  {
    if (!$user) {
      return false;
    }

    return $user->hasRole(RoleEnum::OWNER->value) && !$user->hasAnyRole([
      RoleEnum::ADMIN->value,
      RoleEnum::ACCOUNT_MANAGER->value,
      RoleEnum::OWNER_ADMIN->value,
      RoleEnum::FRONTDESK_ADMIN->value,
    ]);
  }

  private function ownerCanAccessOrder(?User $user, Order $order): bool
  {
    if (!$this->isOwnerRestricted($user)) {
      return true;
    }

    return $user ? $order->isAccessibleToOwner($user) : false;
  }

  private function canMoveOrderToEstimate(?User $user): bool
  {
    if (!$user) {
      return false;
    }

    return $user->hasAnyRole([
      RoleEnum::ADMIN->value,
      RoleEnum::OWNER_ADMIN->value,
      RoleEnum::FRONTDESK_ADMIN->value,
    ]);
  }

  private function ensureRequestRescheduleTransitionAllowed(Order $order, string $targetStatus): void
  {
    if ($order->status !== OrderStatusEnum::REQUEST_RE_SCHEDULE->value) {
      return;
    }

    if (in_array($targetStatus, [
      OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
      OrderStatusEnum::LOST_CONTRACT->value,
    ], true)) {
      return;
    }

    throw ValidationException::withMessages([
      'status' => 'Orders in REQUEST RE-SCHEDULE can only move to ESTIMATE & APPT SCHEDULE or LOST CONTRACT.',
    ]);
  }

  private function salesStatusColor(string $status): string
  {
    return [
      OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value => '#2563eb',
      OrderStatusEnum::PENDING_ASSIGNMENT->value => '#7c3aed',
      OrderStatusEnum::REQUEST_RE_SCHEDULE->value => '#f97316',
      OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value => '#0ea5e9',
      OrderStatusEnum::FOLLOW_UP->value => '#22c55e',
      OrderStatusEnum::FOLLOW_UP_PROJECTS->value => '#16a34a',
      OrderStatusEnum::STAND_BY->value => '#facc15',
      OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value => '#ec4899',
      OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value => '#14b8a6',
      OrderStatusEnum::LOST_CONTRACT->value => '#ef4444',
    ][$status] ?? '#6b7280';
  }

  private function ownerListForCalendar(?User $user): Collection
  {
    $ownerQuery = User::role(RoleEnum::OWNER->value)
      ->select('id', 'name')
      ->where('status', StatusUserEnum::ACTIVE->value)
      ->orderBy('name');

    if ($this->isOwnerRestricted($user)) {
      $ownerQuery->whereIn('id', $user->accessibleOwnerIds());
    }

    return $ownerQuery->get();
  }

  private function ownerListForCalendarAll(?User $user): Collection
  {
    $ownerQuery = User::role(RoleEnum::OWNER->value)
      ->select('id', 'name', 'status')
      ->orderBy('name');

    if ($this->isOwnerRestricted($user)) {
      $ownerQuery->whereIn('id', $user->accessibleOwnerIds());
    }

    return $ownerQuery->get();
  }

  private function ownerColorMapFromList(Collection $owners): array
  {
    $colors = [];

    foreach ($owners as $owner) {
      $colors[$owner->id] = $this->ownerColorFromId((int) $owner->id);
    }

    return $colors;
  }

  private function colorForOwners(Collection $owners, array $colorMap): ?string
  {
    $activeOwners = $owners->filter(fn ($owner) => $owner?->status === StatusUserEnum::ACTIVE->value);

    if ($activeOwners->isEmpty()) {
      return '#ffffff';
    }

    $primaryOwnerId = (int) ($activeOwners->sortBy('id')->first()?->id ?? 0);

    return $colorMap[$primaryOwnerId] ?? '#ffffff';
  }

  private function ownerColorFromId(int $ownerId): string
  {
    if ($ownerId <= 0) {
      return '#6b7280';
    }

    $palette = [
      '#1f77b4', // blue
      '#ff7f0e', // orange
      '#2ca02c', // green
      '#d62728', // red
      '#9467bd', // purple
      '#8c564b', // brown
      '#e377c2', // pink
      '#17becf', // cyan
      '#bcbd22', // olive
      '#4e79a7', // steel blue
      '#f28e2b', // orange
      '#59a14f', // green
      '#e15759', // red
      '#76b7b2', // teal
      '#edc948', // yellow
      '#b07aa1', // lavender
      '#ff9da7', // salmon
      '#9c755f', // brown
      '#2f4b7c', // deep blue
      '#f95d6a', // pink red
      '#3b8bba', // bright blue
      '#00a6d6', // blue cyan
      '#ef476f', // magenta red
      '#06d6a0', // mint
      '#118ab2', // blue
      '#ffd166', // gold
      '#8338ec', // violet
      '#fb5607', // orange
      '#3a86ff', // vivid blue
      '#2a9d8f', // green teal
    ];

    $index = $ownerId % count($palette);
    $cycle = intdiv($ownerId, count($palette));
    $color = $palette[$index];

    if ($cycle > 0) {
      $adjust = ($cycle % 2 === 0) ? -18 : 18;
      return $this->adjustHexColor($color, $adjust);
    }

    return $color;
  }

  private function adjustHexColor(string $hex, int $percent): string
  {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $delta = (int) round(255 * ($percent / 100));
    $r = max(0, min(255, $r + $delta));
    $g = max(0, min(255, $g + $delta));
    $b = max(0, min(255, $b + $delta));

    return sprintf('#%02x%02x%02x', $r, $g, $b);
  }

  public function create()
  {
    return Inertia::render('Frontdesk/Create', [
      'clients' => Client::all(),
      'owners' => User::role(RoleEnum::OWNER->value)
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->get(),
      'status' => [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
      ],
      'sources' => [
         ContactSourceEnum::TIK_TOK->value,
            ContactSourceEnum::INSTAGRAM_FACEBOOK->value,
            ContactSourceEnum::EXTERNAL_REFERAL->value,
            ContactSourceEnum::INTERNAL_REFERAL->value,
            ContactSourceEnum::SIGNS->value,
            ContactSourceEnum::WALK_IN->value,
            ContactSourceEnum::ESW_REFER->value,
            ContactSourceEnum::ESR_REFER->value,
            ContactSourceEnum::YOUTUBE->value,
            ContactSourceEnum::NEW_ORDER ->value,
            ContactSourceEnum::GOOGLE_ADS->value,
            ContactSourceEnum::SAME_AS_ORDER->value,
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
      ],
    ]);
  }

   public function store(StoreFrontDeskOrderRequest $storeFrontDeskOrderRequest, CreateOrderPipeline $createOrderPipeline)
  {
    $createOrderPipeline->handle($storeFrontDeskOrderRequest);
    return redirect()->route('frontdesk.index')
      ->with('success', 'Request created successfully.');
  }

  public function updateStatus(Request $request, Order $order)
{
    $this->ensureRequestRescheduleTransitionAllowed($order, (string) $request->input('status'));

    $order->status = $request->input('status');

    $order->save();
      $order->orderStatus()->create([
        'status' => $request->input('status'),
        'user_id' => auth()->user()->id,
        'notes' => "{$request->input('status')} created by " . auth()->user()->name,
      ]);


    return response()->json(['success' => true, 'order' => $order]);
}

  public function assignEstimate(Request $request, Order $order)
  {
    if (!$this->canMoveOrderToEstimate($request->user())) {
      return response()->json([
        'message' => 'You are not allowed to move this order to ESTIMATE & APPT SCHEDULE.',
      ], 403);
    }

    $this->ensureRequestRescheduleTransitionAllowed($order, OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value);

    $validated = $request->validate([
      'schedule_appointment' => ['nullable', 'date'],
      'owner_ids' => ['array'],
      'owner_ids.*' => ['integer', Rule::exists('users', 'id')],
    ]);

   // $send = new OrderEmails();

    $order->load('saleForm');
    $existingSaleForm = $order->saleForm;

    DB::transaction(function () use ($order, $validated, $existingSaleForm) {
      $order->schedule_appointment = $validated['schedule_appointment'] ?? null;
      $order->status = OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value;
      $order->save();


      if (array_key_exists('owner_ids', $validated)) {
        $order->owners()->sync($validated['owner_ids']);
      }

      $order->orderStatus()->create([
        'status' => OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
        'user_id' => auth()->id(),
        'notes' => OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value . ' updated by ' . auth()->user()->name,
      ]);

      $saleFormPayload = [
        'sale' => $existingSaleForm->sale ?? false,
        'installation' => $existingSaleForm->installation ?? false,
        'permit' => $existingSaleForm->permit ?? false,
        'replacement' => $existingSaleForm->replacement ?? false,
        'new_construction' => $existingSaleForm->new_construction ?? false,
        'financing' => $existingSaleForm->financing ?? false,
        'screen' => $existingSaleForm->screen ?? false,
        'design' => $existingSaleForm->design ?? false,
        'mountin' => $existingSaleForm->mountin ?? false,
        'bar' => $existingSaleForm->bar ?? false,
        'shutter_hole' => $existingSaleForm->shutter_hole ?? false,
        'floor_cutting' => $existingSaleForm->floor_cutting ?? false,
        'interior_finish' => $existingSaleForm->interior_finish ?? false,
        'floor' => $existingSaleForm->floor ?? '',
        'frame_color' => $existingSaleForm->frame_color ?? ($order->frame_color ?? ''),
        'glass_color' => $existingSaleForm->glass_color ?? ($order->glass_color ?? ''),
        'glass_type' => $existingSaleForm->glass_type ?? ($order->glass_type ?? ''),
        'glass_coating' => $existingSaleForm->glass_coating ?? ($order->glass_coating ?? ''),
        'door_quantity' => $existingSaleForm->door_quantity ?? 0,
        'window_quantity' => $existingSaleForm->window_quantity ?? 0,
      ];

      $order->saleForm()->updateOrCreate([], $saleFormPayload);
    });

    $order->load('owners', 'saleForm', 'notes.user');

    $this->sendEmail($order);

    $schedule = $order->schedule_appointment
      ? Carbon::parse($order->schedule_appointment)
      : null;
      
    return response()->json([
      'order' => [
        'id' => $order->id,
        'status' => $order->status,
        'schedule_appointment' => $schedule ? $schedule->format('M d, Y h:i A') : null,
        'schedule_appointment_iso' => $schedule ? $schedule->format('Y-m-d\TH:i') : null,
        'owner_ids' => $order->owners->pluck('id')->values(),
        'owners' => $order->owners->map(fn ($owner) => [
          'id' => $owner->id,
          'name' => $owner->name,
        ])->values(),
        'has_sale_form' => $order->saleForm !== null,
      ],
    ]);
  }

    public function assignFollowUp(Request $request, Order $order)
    {
        if (!$this->ownerCanAccessOrder($request->user(), $order)) {
          return response()->json([
            'message' => 'You are not allowed to update this order.',
          ], 403);
        }

        $validated = $request->validate([
          'status' => ['required', Rule::in([
            OrderStatusEnum::FOLLOW_UP->value,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
          ])],
          'project_amount' => ['required', 'numeric', 'min:1'],
          'product_line' => [
            Rule::requiredIf($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value),
            'nullable',
            Rule::enum(ProductLineEnum::class),
          ],
          'esr_cost' => [
            'nullable',
            'numeric',
            'min:0',
            Rule::requiredIf(fn () => ($request->input('product_line') ?? $order->product_line) === ProductLineEnum::MIXED->value),
          ],
          'note' => ['nullable', 'string'],
          'attachments' => ['required', 'array'],
          'attachments.*' => ['file', 'max:10240', 'mimes:pdf'],
        ]);

        $this->ensureRequestRescheduleTransitionAllowed($order, $validated['status']);

        $noteContent = trim((string) ($validated['note'] ?? ''));
        $currentProjectAmount = (float) ($order->project_amount ?? 0);
        $incomingProjectAmount = (float) $validated['project_amount'];

        if ($request->user()?->hasRole(RoleEnum::OWNER_ADMIN->value) && abs($incomingProjectAmount - $currentProjectAmount) > 0.01) {
          throw ValidationException::withMessages([
            'project_amount' => 'Owner Admin cannot edit Project Amount.',
          ]);
        }

        if ($order->hasReachedContractSigned() && abs($incomingProjectAmount - $currentProjectAmount) > 0.01) {
          throw ValidationException::withMessages([
            'project_amount' => 'Project amount cannot be edited after CONTRACT SIGNED BY CLIENT. Use Change Order instead.',
          ]);
        }

        DB::transaction(function () use ($order, $validated, $request, $noteContent) {
          $oldProjectAmount = (float) ($order->project_amount ?? 0);
          $order->project_amount = $validated['project_amount'];
          $order->product_line = $validated['product_line'] ?? $order->product_line;
          $order->esr_cost = $order->product_line === ProductLineEnum::MIXED->value
            ? $validated['esr_cost']
            : null;
          $order->status = $validated['status'];
          $order->save();

          if (abs((float) $order->project_amount - $oldProjectAmount) > 0.01) {
            OrderFinancialEventLogger::log(
              $order,
              'PROJECT_AMOUNT_UPDATED',
              'Project amount updated',
              [
                'before_amount' => $oldProjectAmount,
                'after_amount' => (float) $order->project_amount,
              ]
            );
          }

          $order->orderStatus()->create([
            'status' => $order->status,
            'user_id' => auth()->id(),
            'notes' => $order->status . ' updated by ' . auth()->user()->name,
          ]);

          if ($noteContent !== '') {
            $order->notes()->create([
              'content' => $noteContent,
              'type' => 'order_note',
              'user_id' => auth()->id(),
            ]);
          }

          if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
              $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
              $filePath = $file->storeAs('order_files', $fileName, 'public');

              $order->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_type' => 'order_files',
                'user_id' => auth()->id(),
              ]);
            }
          }
        });

        $this->createSnapshot($order->fresh());
        $order->load('owners');

        $schedule = $order->schedule_appointment
          ? Carbon::parse($order->schedule_appointment)
          : null;

        return response()->json([
          'order' => [
            'id' => $order->id,
            'status' => $order->status,
            'schedule_appointment' => $schedule ? $schedule->format('M d, Y h:i A') : null,
            'schedule_appointment_iso' => $schedule ? $schedule->format('Y-m-d\TH:i') : null,
            'project_amount' => $order->project_amount,
            'product_line' => $order->product_line,
            'esr_cost' => $order->esr_cost,
            'owner_ids' => $order->owners->pluck('id')->values(),
            'owners' => $order->owners->map(fn ($owner) => [
              'id' => $owner->id,
              'name' => $owner->name,
            ])->values(),
          ],
        ]);
      }

  public function assignStandBy(Request $request, Order $order)
  {
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json([
        'message' => 'You are not allowed to update this order.',
      ], 403);
    }

    $this->ensureRequestRescheduleTransitionAllowed($order, OrderStatusEnum::STAND_BY->value);

    $validated = $request->validate([
      'note' => ['required', 'string'],
      'product_line' => [
        Rule::requiredIf($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value),
        'nullable',
        Rule::enum(ProductLineEnum::class),
      ],
      'esr_cost' => [
        'nullable',
        'numeric',
        'min:0',
        Rule::requiredIf(fn () => ($request->input('product_line') ?? $order->product_line) === ProductLineEnum::MIXED->value),
      ],
    ]);

    $noteContent = trim($validated['note']);

    if ($noteContent === '') {
      return response()->json([
        'message' => 'The note is required.',
        'errors' => ['note' => ['The note is required.']],
      ], 422);
    }

    DB::transaction(function () use ($order, $noteContent, $validated) {
      $order->product_line = $validated['product_line'] ?? $order->product_line;
      $order->esr_cost = $order->product_line === ProductLineEnum::MIXED->value
        ? $validated['esr_cost']
        : null;
      $order->status = OrderStatusEnum::STAND_BY->value;
      $order->save();

      $order->orderStatus()->create([
        'status' => $order->status,
        'user_id' => auth()->id(),
        'notes' => $order->status . ' updated by ' . auth()->user()->name,
      ]);

      $order->notes()->create([
        'content' => $noteContent,
        'type' => 'order_note',
        'user_id' => auth()->id(),
      ]);
    });

    $this->createSnapshot($order->fresh());
    $order->load('owners');

    $schedule = $order->schedule_appointment
      ? Carbon::parse($order->schedule_appointment)
      : null;

    return response()->json([
      'order' => [
        'id' => $order->id,
        'status' => $order->status,
        'product_line' => $order->product_line,
        'esr_cost' => $order->esr_cost,
        'schedule_appointment' => $schedule ? $schedule->format('M d, Y h:i A') : null,
        'schedule_appointment_iso' => $schedule ? $schedule->format('Y-m-d\\TH:i') : null,
        'owner_ids' => $order->owners->pluck('id')->values(),
        'owners' => $order->owners->map(fn ($owner) => [
          'id' => $owner->id,
          'name' => $owner->name,
        ])->values(),
      ],
    ]);
  }

  public function assignRequestReschedule(Request $request, Order $order)
  {
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json([
        'message' => 'You are not allowed to move this order to REQUEST RE-SCHEDULE.',
      ], 403);
    }

    $validated = $request->validate([
      'note' => ['required', 'string'],
    ]);

    $noteContent = trim($validated['note']);

    if ($noteContent === '') {
      return response()->json([
        'message' => 'The note is required.',
        'errors' => ['note' => ['The note is required.']],
      ], 422);
    }

    if ($order->status !== OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value) {
      return response()->json([
        'message' => 'Orders can only move to REQUEST RE-SCHEDULE from ESTIMATE & APPT SCHEDULE.',
      ], 422);
    }

    DB::transaction(function () use ($order, $noteContent) {
      $order->status = OrderStatusEnum::REQUEST_RE_SCHEDULE->value;
      $order->save();

      $order->orderStatus()->create([
        'status' => $order->status,
        'user_id' => auth()->id(),
        'notes' => $order->status . ' updated by ' . auth()->user()->name,
      ]);

      $order->notes()->create([
        'content' => $noteContent,
        'type' => 'order_note',
        'user_id' => auth()->id(),
      ]);
    });

    $order->load('owners', 'notes.user');
    // $order->load('owners', 'saleForm', 'notes.user');

    $this->sendEmail($order, $noteContent);

    $schedule = $order->schedule_appointment
      ? Carbon::parse($order->schedule_appointment)
      : null;

    return response()->json([
      'order' => [
        'id' => $order->id,
        'status' => $order->status,
        'schedule_appointment' => $schedule ? $schedule->format('M d, Y h:i A') : null,
        'schedule_appointment_iso' => $schedule ? $schedule->format('Y-m-d\\TH:i') : null,
        'owner_ids' => $order->owners->pluck('id')->values(),
        'owners' => $order->owners->map(fn ($owner) => [
          'id' => $owner->id,
          'name' => $owner->name,
        ])->values(),
      ],
    ]);
  }

  public function assignPreContract(Request $request, Order $order)
  {
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json([
        'message' => 'You are not allowed to update this order.',
      ], 403);
    }

    $this->ensureRequestRescheduleTransitionAllowed($order, OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value);

    $validated = $request->validate([
      'note' => ['nullable', 'string'],
      'product_line' => [
        Rule::requiredIf($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value),
        'nullable',
        Rule::enum(ProductLineEnum::class),
      ],
    ]);

    $noteContent = trim($validated['note']);

    /*if ($noteContent === '') {
      return response()->json([
        'message' => 'La nota es obligatoria.',
        'errors' => ['note' => ['La nota es obligatoria.']],
      ], 422);
    }*/

    DB::transaction(function () use ($order, $noteContent, $validated) {
      $order->product_line = $validated['product_line'] ?? $order->product_line;
      $order->status = OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value;
      $order->save();

      $order->orderStatus()->create([
        'status' => $order->status,
        'user_id' => auth()->id(),
        'notes' => $order->status . ' updated by ' . auth()->user()->name,
      ]);

      if ($noteContent !== '') {
        $order->notes()->create([
          'content' => $noteContent,
          'type' => 'order_note',
          'user_id' => auth()->id(),
        ]);
      }
    });

    $this->createSnapshot($order->fresh());
    $order->load('owners');

    $schedule = $order->schedule_appointment
      ? Carbon::parse($order->schedule_appointment)
      : null;

    return response()->json([
      'order' => [
        'id' => $order->id,
        'status' => $order->status,
        'product_line' => $order->product_line,
        'schedule_appointment' => $schedule ? $schedule->format('M d, Y h:i A') : null,
        'schedule_appointment_iso' => $schedule ? $schedule->format('Y-m-d\\TH:i') : null,
        'owner_ids' => $order->owners->pluck('id')->values(),
        'owners' => $order->owners->map(fn ($owner) => [
          'id' => $owner->id,
          'name' => $owner->name,
        ])->values(),
      ],
    ]);
  }

  public function assignContractSigned(Request $request, Order $order)
  {
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json([
        'message' => 'You are not allowed to update this order.',
      ], 403);
    }

    $this->ensureRequestRescheduleTransitionAllowed(
      $order,
      $this->contractSignedPipelineStatus(
        $order,
        $request->boolean('pending_financing_or_deposit'),
        $request->boolean('association_permits') && $request->boolean('pending_hoa_approval')
      )
    );

    if ($request->has('type_of_financing') && trim((string) $request->input('type_of_financing')) === '') {
      $request->merge(['type_of_financing' => null]);
    }

    if ($request->has('down_payment') && trim((string) $request->input('down_payment')) === '') {
      $request->merge(['down_payment' => null]);
    }

    $orderCompanyContacts = $order->orderCompanyContacts()->with('client')->get();
    $companyCount = $orderCompanyContacts->count();
    $methodOfPayment = (string) $request->input('method_of_payment');
    $cashMethod = MethodOfPayment::CASH->value;
    $cashAndFinancedMethod = MethodOfPayment::FINANCEDCASH->value;
    $requiresSchedule = in_array($methodOfPayment, [$cashMethod, $cashAndFinancedMethod], true);

    $rules = [
      'project_name' => ['required', 'string', 'max:255'],
      'order_number' => ['required', 'string', 'max:255'],
      'product_line' => [
        Rule::requiredIf($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value),
        'nullable',
        Rule::enum(ProductLineEnum::class),
      ],
      'esr_cost' => [
        'nullable',
        'numeric',
        'min:0',
        Rule::requiredIf(fn () => ($request->input('product_line') ?? $order->product_line) === ProductLineEnum::MIXED->value),
      ],
      'project_amount' => ['required', 'numeric', 'min:1'],
      'job_address' => ['nullable', 'string'],
      'city' => ['nullable', 'string', 'max:255'],
      'job_state' => ['nullable', 'string', 'max:255'],
      'job_zip' => ['nullable', 'string', 'max:50'],
      'client_email_selection' => ['required', 'string', 'max:255'],
      'name_check' => ['nullable', 'boolean'],
      'address_check' => ['nullable', 'boolean'],
      'amount_check' => ['nullable', 'boolean'],
      'email_check' => ['nullable', 'boolean'],
      'city_permits' => ['nullable', 'boolean'],
      'association_permits' => ['nullable', 'boolean'],
      'pending_financing_or_deposit' => ['required', 'boolean'],
      'pending_hoa_approval' => ['required_if:association_permits,1', 'nullable', 'boolean'],
      'method_of_payment' => ['required', Rule::in(array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()))],
      'type_of_financing' => ['nullable', Rule::in(array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases()))],
      'down_payment' => ['nullable', 'numeric', 'min:0'],
      'payment_schedule_type' => [
        'nullable',
        Rule::requiredIf($requiresSchedule),
        Rule::in(PaymentScheduleTemplates::types()),
      ],
      'custom_schedule' => ['nullable', 'array', 'max:6'],
      'custom_schedule.*.label' => ['required', 'string', 'max:255'],
      'custom_schedule.*.amount' => ['required', 'numeric', 'min:0.01'],
      'attachments' => ['required', 'array', 'min:1'],
      'attachments.*' => ['file', 'max:10240', 'mimes:pdf'],
    ];

    if ($order->order_type === OrderTypeEnum::COMMERCIAL->value && $companyCount > 1) {
      $rules['order_company_contact_id'] = [
        'required',
        'integer',
        Rule::exists('order_company_contacts', 'id')->where(fn ($query) => $query->where('order_id', $order->id)),
      ];
    } else {
      $rules['order_company_contact_id'] = ['nullable', 'integer'];
    }

    $validator = Validator::make($request->all(), $rules);
    $validator->after(function ($validator) use ($request, $order, $orderCompanyContacts, $companyCount) {
      $cashMethod = MethodOfPayment::CASH->value;
      $cashAndFinancedMethod = MethodOfPayment::FINANCEDCASH->value;
      $methodOfPayment = (string) $request->input('method_of_payment');
      $scheduleType = (string) $request->input('payment_schedule_type');
      $requiresSchedule = in_array($methodOfPayment, [$cashMethod, $cashAndFinancedMethod], true);
      $isCashAndFinanced = $methodOfPayment === $cashAndFinancedMethod;
      $customSchedule = $request->input('custom_schedule', []);
      $projectAmount = (float) $request->input('project_amount', 0);
      $cashAmount = (float) $request->input('down_payment', 0);

      if ($isCashAndFinanced) {
        if ($request->input('down_payment') === null) {
          $validator->errors()->add('down_payment', 'Cash amount is required for CASH AND FINANCED.');
        } elseif ($cashAmount <= 0) {
          $validator->errors()->add('down_payment', 'Cash amount must be greater than 0.');
        } elseif ($projectAmount > 0 && $cashAmount >= $projectAmount) {
          $validator->errors()->add('down_payment', 'Cash amount must be less than project amount.');
        }

        if ($scheduleType !== PaymentScheduleTypeEnum::CUSTOMIZED->value) {
          $validator->errors()->add('payment_schedule_type', 'CASH AND FINANCED requires CUSTOMIZED payment schedule.');
        }
      }

      if ($requiresSchedule && $scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
        if (!is_array($customSchedule) || count($customSchedule) === 0) {
          $validator->errors()->add('custom_schedule', 'Add at least one custom payment.');
          return;
        }

        $total = 0.0;
        foreach ($customSchedule as $item) {
          $total += (float) ($item['amount'] ?? 0);
        }

        $targetAmount = $isCashAndFinanced ? $cashAmount : $projectAmount;
        if ($targetAmount > 0 && abs($total - $targetAmount) > 0.01) {
          $validator->errors()->add(
            'custom_schedule',
            $isCashAndFinanced
              ? 'Custom payments must total the cash amount.'
              : 'Custom payments must total the project amount.'
          );
        }
      }

      $selectedContact = null;
      if ($order->order_type === OrderTypeEnum::COMMERCIAL->value && $companyCount > 0) {
        $selectedId = $request->input('order_company_contact_id')
          ?: ($companyCount === 1 ? $orderCompanyContacts->first()?->id : null);

        if ($selectedId) {
          $selectedContact = $orderCompanyContacts->firstWhere('id', (int) $selectedId);
        }
      }

      $selectionError = $this->clientEmailManager()->validateSelection(
        $order,
        (string) $request->input('client_email_selection'),
        $selectedContact
      );

      if ($selectionError !== null) {
        $validator->errors()->add('client_email_selection', $selectionError);
      }
    });

    $validated = $validator->validate();

    $existingSchedule = $order->paymentSchedule()->with('installments')->first();
    $hasRecordedPayments = $existingSchedule
      ? $existingSchedule->installments()->whereHas('movements')->exists()
      : false;
    if ($hasRecordedPayments) {
      $currentAmount = (float) ($order->project_amount ?? 0);
      $incomingAmount = (float) ($validated['project_amount'] ?? 0);
      if (abs($incomingAmount - $currentAmount) > 0.01) {
        throw ValidationException::withMessages([
          'project_amount' => 'Project amount cannot be changed after payments are recorded.',
        ]);
      }
    }

    $currentAmount = (float) ($order->project_amount ?? 0);
    $incomingAmount = (float) ($validated['project_amount'] ?? 0);
    if ($request->user()?->hasRole(RoleEnum::OWNER_ADMIN->value) && abs($incomingAmount - $currentAmount) > 0.01) {
      throw ValidationException::withMessages([
        'project_amount' => 'Owner Admin cannot edit Project Amount.',
      ]);
    }

    DB::transaction(function () use ($order, $validated, $request, $orderCompanyContacts, $companyCount) {
      $order->loadMissing('paymentSchedule.installments');
      $beforeClientEmailDelivery = $this->orderClientEmailDeliveryLogger()->capture($order);
      $beforePaymentInformation = OrderPaymentInformationAuditLogger::snapshot($order);
      $oldProjectAmount = (float) ($order->project_amount ?? 0);
      $pendingFinancingOrDeposit = $request->boolean('pending_financing_or_deposit');
      $pendingHoaApproval = $request->boolean('association_permits') && $request->boolean('pending_hoa_approval');
      $newPipelineStatus = $this->contractSignedPipelineStatus(
        $order,
        $pendingFinancingOrDeposit,
        $pendingHoaApproval
      );

      $order->name = $validated['project_name'];
      $order->order_number = trim((string) $validated['order_number']);
      $order->project_amount = $validated['project_amount'];
      $order->product_line = $validated['product_line'] ?? $order->product_line;
      $order->esr_cost = $order->product_line === ProductLineEnum::MIXED->value
        ? $validated['esr_cost']
        : null;
      $order->job_address = isset($validated['job_address']) && $validated['job_address'] !== ''
        ? $validated['job_address']
        : null;
      $order->city = isset($validated['city']) && $validated['city'] !== ''
        ? $validated['city']
        : null;
      $order->job_state = isset($validated['job_state']) && $validated['job_state'] !== ''
        ? $validated['job_state']
        : null;
      $order->job_zip = isset($validated['job_zip']) && $validated['job_zip'] !== ''
        ? $validated['job_zip']
        : null;
      $order->method_of_payment = $validated['method_of_payment'];
      $order->type_of_financing = isset($validated['type_of_financing']) && $validated['type_of_financing'] !== null
        ? trim($validated['type_of_financing'])
        : null;
      $order->down_payment = $validated['method_of_payment'] === MethodOfPayment::FINANCEDCASH->value
        ? ($validated['down_payment'] ?? null)
        : null;
      $order->name_check = $request->boolean('name_check');
      $order->address_check = $request->boolean('address_check');
      $order->amount_check = $request->boolean('amount_check');
      $order->email_check = $request->boolean('email_check');
      $order->city_permits = $request->boolean('city_permits');
      $order->association_permits = $request->boolean('association_permits');
      $order->pending_financing_or_deposit = $pendingFinancingOrDeposit;
      $order->pending_hoa_approval = $pendingHoaApproval;
      $order->status = $newPipelineStatus;
      $order->save();

      if (abs((float) $order->project_amount - $oldProjectAmount) > 0.01) {
        OrderFinancialEventLogger::log(
          $order,
          'PROJECT_AMOUNT_UPDATED',
          'Project amount updated',
          [
            'before_amount' => $oldProjectAmount,
            'after_amount' => (float) $order->project_amount,
          ]
        );
      }

      $selectedContact = null;
      if ($order->order_type === OrderTypeEnum::COMMERCIAL->value && $companyCount > 0) {
        $selectedId = $validated['order_company_contact_id'] ?? ($companyCount === 1 ? $orderCompanyContacts->first()?->id : null);
        if ($selectedId) {
          $order->orderCompanyContacts()->update(['is_selected' => false, 'selected_at' => null]);
          $selectedContact = $order->orderCompanyContacts()->where('id', $selectedId)->first();
          if ($selectedContact) {
            $selectedContact->is_selected = true;
            $selectedContact->selected_at = now();
            $selectedContact->save();
            if ($selectedContact->client_id) {
              $order->client_id = $selectedContact->client_id;
              $order->save();
            }
          }
        }
      }

      $this->clientEmailManager()->applySelection($order, (string) $validated['client_email_selection']);
      $order->save();
      $this->orderClientEmailDeliveryLogger()->logIfChanged($order, $beforeClientEmailDelivery);

      $order->orderStatus()->create([
        'status' => OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
        'user_id' => auth()->id(),
        'notes' => OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value . ' updated by ' . auth()->user()->name,
      ]);

      $order->orderStatus()->create([
        'status' => $newPipelineStatus,
        'user_id' => auth()->id(),
        'notes' => $newPipelineStatus . ' created by ' . auth()->user()->name,
      ]);

      if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
          $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
          $filePath = $file->storeAs('order_files', $fileName, 'public');

          $order->attachments()->create([
            'filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => 'order_files',
            'user_id' => auth()->id(),
          ]);
        }
      }

      $scheduleType = $validated['payment_schedule_type'] ?? null;
      $customSchedule = $validated['custom_schedule'] ?? [];
      $projectAmount = (float) $order->project_amount;
      $isCashAndFinanced = $validated['method_of_payment'] === MethodOfPayment::FINANCEDCASH->value;
      $requiresSchedule = in_array(
        $validated['method_of_payment'],
        [MethodOfPayment::CASH->value, MethodOfPayment::FINANCEDCASH->value],
        true
      );
      $scheduleTotalAmount = $isCashAndFinanced
        ? (float) ($order->down_payment ?? 0)
        : $projectAmount;
      $existingSchedule = $order->paymentSchedule()->first();
      $hasRecordedPayments = $existingSchedule
        ? $existingSchedule->installments()->whereHas('movements')->exists()
        : false;

      if ($hasRecordedPayments) {
        throw ValidationException::withMessages([
          'payment_schedule_type' => 'Payment schedule cannot be changed after payments are recorded.',
        ]);
      }

      if (!$requiresSchedule || !$scheduleType) {
        if ($existingSchedule) {
          $previousScheduleType = $existingSchedule->schedule_type;
          $previousTotalAmount = (float) $existingSchedule->total_amount;
          $existingSchedule->installments()->delete();
          $existingSchedule->delete();

          OrderFinancialEventLogger::log(
            $order,
            'PAYMENT_SCHEDULE_REMOVED',
            'Payment schedule removed',
            [
              'before_schedule_type' => $previousScheduleType,
              'before_total_amount' => $previousTotalAmount,
            ]
          );
        }
      } elseif ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
        $installments = [];
        $runningPercent = 0.0;
        $count = count($customSchedule);
        foreach ($customSchedule as $index => $item) {
          $amount = round((float) ($item['amount'] ?? 0), 2);
          $percentage = $scheduleTotalAmount > 0
            ? round(($amount / $scheduleTotalAmount) * 100, 2)
            : 0;

          if ($index === $count - 1 && $scheduleTotalAmount > 0) {
            $percentage = round(100 - $runningPercent, 2);
          }

          $runningPercent += $percentage;

          $installments[] = [
            'label' => trim((string) ($item['label'] ?? '')),
            'percentage' => $percentage,
            'amount' => $amount,
          ];
        }

        $paymentSchedule = PaymentSchedule::updateOrCreate(
          ['order_id' => $order->id],
          [
            'schedule_type' => $scheduleType,
            'total_amount' => $scheduleTotalAmount,
          ]
        );

        $paymentSchedule->installments()->delete();

        foreach ($installments as $index => $installment) {
          $paymentSchedule->installments()->create([
            'position' => $index + 1,
            'label' => $installment['label'],
            'percentage' => $installment['percentage'],
            'amount' => $installment['amount'],
            'status' => 'PENDING',
          ]);
        }

        OrderFinancialEventLogger::log(
          $order,
          'PAYMENT_SCHEDULE_DEFINED',
          "Payment schedule configured as {$scheduleType}",
          [
            'schedule_type' => $scheduleType,
            'total_amount' => $scheduleTotalAmount,
            'installments' => $installments,
          ]
        );
      } else {
        $scheduleItems = PaymentScheduleTemplates::itemsFor($scheduleType);
        $installments = PaymentScheduleCalculator::withAmounts($scheduleItems, $scheduleTotalAmount);

        $paymentSchedule = PaymentSchedule::updateOrCreate(
          ['order_id' => $order->id],
          [
            'schedule_type' => $scheduleType,
            'total_amount' => $scheduleTotalAmount,
          ]
        );

        $paymentSchedule->installments()->delete();

        foreach ($installments as $index => $installment) {
          $paymentSchedule->installments()->create([
            'position' => $index + 1,
            'label' => $installment['label'],
            'percentage' => $installment['percentage'],
            'amount' => $installment['amount'],
            'status' => 'PENDING',
          ]);
        }

        OrderFinancialEventLogger::log(
          $order,
          'PAYMENT_SCHEDULE_DEFINED',
          "Payment schedule configured as {$scheduleType}",
          [
            'schedule_type' => $scheduleType,
            'total_amount' => $scheduleTotalAmount,
            'installments' => $installments,
          ]
        );
      }

      $order->load('paymentSchedule.installments');
      OrderPaymentInformationAuditLogger::logIfChanged(
        $order,
        $beforePaymentInformation,
        'CONTRACT_SIGNED_MODAL',
        $request
      );
    });

    $this->createSnapshot($order->fresh());
    $order->load('owners', 'client.companyContacts', 'paymentSchedule.installments.paidBy', 'paymentSchedule.installments.movements.paidBy', 'cityFeePayment.paidBy', 'orderCompanyContacts.companyContact', 'orderCompanyContacts.client.companyContacts', 'orderCompanyContacts.source', 'financialEvents.user');
    $selectedContact = $order->orderCompanyContacts
      ->firstWhere('is_selected', true)
      ?? ($order->orderCompanyContacts->count() === 1 ? $order->orderCompanyContacts->first() : null);

    $schedule = $order->schedule_appointment
      ? Carbon::parse($order->schedule_appointment)
      : null;

    return response()->json([
      'order' => [
        'id' => $order->id,
        'name' => $order->name,
        'order_number' => $order->order_number,
        'status' => $order->status,
        'product_line' => $order->product_line,
        'esr_cost' => $order->esr_cost,
        'project_amount' => $order->project_amount,
        'down_payment' => $order->down_payment,
        'job_address' => $order->job_address,
        'city' => $order->city,
        'job_state' => $order->job_state,
        'job_zip' => $order->job_zip,
        'method_of_payment' => $order->method_of_payment,
        'type_of_financing' => $order->type_of_financing,
        'schedule_appointment' => $schedule ? $schedule->format('M d, Y h:i A') : null,
        'schedule_appointment_iso' => $schedule ? $schedule->format('Y-m-d\\TH:i') : null,
        'owner_ids' => $order->owners->pluck('id')->values(),
        'owners' => $order->owners->map(fn ($owner) => [
          'id' => $owner->id,
          'name' => $owner->name,
        ])->values(),
        'contact_email' => $this->clientEmailManager()->resolveRecipient($order),
        'client_email_selection' => $this->clientEmailManager()->selectionForOrder($order),
        'client_email_override' => $order->client_email_override,
        'client_email_options' => $this->clientEmailManager()->optionsForOrder($order, $selectedContact),
        'do_not_send_email' => (bool) ($order->do_not_send_email ?? false),
        'name_check' => (bool) ($order->name_check ?? false),
        'address_check' => (bool) ($order->address_check ?? false),
        'amount_check' => (bool) ($order->amount_check ?? false),
        'email_check' => (bool) ($order->email_check ?? false),
        'city_permits' => (bool) ($order->city_permits ?? false),
        'cost_city_fee' => $order->cost_city_fee,
        'city_fee_payment' => $order->cityFeePayment ? [
          'id' => $order->cityFeePayment->id,
          'order_id' => $order->cityFeePayment->order_id,
          'type' => $order->cityFeePayment->type,
          'amount' => (float) $order->cityFeePayment->amount,
          'note' => $order->cityFeePayment->note,
          'status' => $order->cityFeePayment->status,
          'paid_at' => $order->cityFeePayment->paid_at?->toISOString(),
          'paid_by' => $order->cityFeePayment->paidBy
            ? ['id' => $order->cityFeePayment->paidBy->id, 'name' => $order->cityFeePayment->paidBy->name]
            : null,
        ] : null,
        'association_permits' => (bool) ($order->association_permits ?? false),
        'pending_financing_or_deposit' => (bool) ($order->pending_financing_or_deposit ?? false),
        'pending_hoa_approval' => (bool) ($order->pending_hoa_approval ?? false),
        'has_contract_signed' => true,
        'payment_schedule' => PaymentInstallmentPresenter::schedule($order->paymentSchedule),
        'financial_events' => $order->financialEvents
          ->take(200)
          ->map(fn ($event) => [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'summary' => $event->summary,
            'details' => $event->details,
            'created_at' => optional($event->created_at)->toISOString(),
            'user' => $event->user ? [
              'id' => $event->user->id,
              'name' => $event->user->name,
            ] : null,
          ])
          ->values(),
        'order_company_contacts' => $order->orderCompanyContacts
          ->map(fn ($item) => $this->mapOrderCompanyContactForResponse($order, $item))
          ->values(),
      ],
    ]);
  }

  public function assignLostContract(Request $request, Order $order)
  {
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json([
        'message' => 'You are not allowed to update this order.',
      ], 403);
    }

    $this->ensureRequestRescheduleTransitionAllowed($order, OrderStatusEnum::LOST_CONTRACT->value);

    $validated = $request->validate([
      'loss_reason_frontdesk' => ['required', 'string', 'max:255'],
      'notes' => ['nullable', 'string'],
    ]);

    DB::transaction(function () use ($order, $validated) {
      $order->status = OrderStatusEnum::LOST_CONTRACT->value;
      $order->loss_reason_frontdesk = $validated['loss_reason_frontdesk'];
      $order->save();

      $noteContent = 'Lost Contract: ' . $validated['loss_reason_frontdesk'];
      if (!empty($validated['notes'])) {
        $noteContent .= ' - ' . $validated['notes'];
        $order->notes()->create([
          'content' => $validated['notes'],
          'type' => 'order_note',
          'user_id' => auth()->id(),
        ]);
      }

      $order->orderStatus()->create([
        'status' => $order->status,
        'user_id' => auth()->id(),
        'notes' => $noteContent,
      ]);
    });

    return response()->json([
      'order' => [
        'id' => $order->id,
        'status' => $order->status,
        'loss_reason_frontdesk' => $order->loss_reason_frontdesk,
      ],
    ]);
  }

  public function destroyOrder(Order $order): JsonResponse
  {
    $order->delete();

    return response()->json([
      'message' => 'Order deleted successfully.',
      'order' => [
        'id' => $order->id,
      ],
    ]);
  }

  public function updateStatusLost(Request $request, Order $order)
{
    //dd($request->all());
    $order->status = $request->input('status');

    $order->save();
      $order->orderStatus()->create([
        'status' => $request->input('status'),
        'user_id' => auth()->user()->id,
        'notes' => "{$request->input('status')} created by " . auth()->user()->name,
      ]);


    return response()->json(['success' => true, 'order' => $order]);
}

public function showQuantifiedModal(Order $order)
{
    $order->load('client'); // Relación con Client

    return response()->json($order);
}
    
   /* public function updateStatusQuantified(Request $request, Order $order)
    {     
        //dd($request->all());
        $order->update([
        'name' => $request['name'],
        'order_type' => $request['order_type'],
        'job_address' => $request['job_address'],
        'city' => $request['city'],
        'job_state' => $request['job_state'],
        'job_zip' => $request['job_zip'],
        'bid_due_date' => $request['bid_due_date'],
        'project_amount' => $request['project_amount'],
        'description' => $request['description'],
        'status' => $request['status'],
    ]);

      if ($order->client) {
        $order->client->update([
            'name' => $request['client_name'],
            'source' =>$request['source'],
            'email' => $request['email'],
            'secondary_email' => $request['secondary_email'],
            'phone' => $request['phone'],
            'other_phone' => $request['other_phone'],
            'vip_clients' => $request['vip_clients'] ?? false,
            'vip_notes' => $request['vip_notes'],
            'is_contact' => true,
            'user_id' => auth()->user()->id,
        ]);
    }
          $order->orderStatus()->create([
            'status' => $request['status'],
            'user_id' => auth()->user()->id,
            'notes' => "{$request['status']} created by " . auth()->user()->name,
          ]);



        return response()->json(['success' => true, 'order' => $order]);
    } */

}
