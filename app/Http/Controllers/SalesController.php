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
use App\Enum\TypeOfFinancing;
use App\Enum\RoleEnum;
use App\Events\OrderStatusChanged;
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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Traits\OrderEmails;
use Illuminate\Http\JsonResponse;
use App\Support\PaymentScheduleCalculator;
use App\Support\PaymentScheduleTemplates;
use App\Support\OrderBoardFilter;

class SalesController extends Controller
{
    use OrderEmails;
    private const SALES_PAGE_SIZE = 20;

    public function index(Request $request)
    {
        $user = auth()->user();
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

    $salesStatuses = $this->salesStatuses();
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
    ];

    $order_types = [
      OrderTypeEnum::RESIDENTIAL->value,
      OrderTypeEnum::COMMERCIAL->value,
      OrderTypeEnum::SUPPLY->value,
    ];
    if ($this->isOwnerRestricted($user)) {
        $visibleStatuses = $ownerVisibleStatuses;
    }

    // Armar el arreglo que espera el componente React
    $data = collect($visibleStatuses)->map(function ($status) use ($user, $paginatedStatuses, $filters, $filterRows, $filterMatch, $hasMultiFilters) {
        $ordersQuery = $this->salesOrdersForStatusQuery($status, $user);
        $ordersQuery = $hasMultiFilters
            ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
            : OrderBoardFilter::apply($ordersQuery, $filters);

        if (in_array($status, $paginatedStatuses, true)) {
            $total = (clone $ordersQuery)->count();
            $orders = $ordersQuery
                ->with($this->salesOrderRelations())
                ->orderByDesc('updated_at')
                ->limit(self::SALES_PAGE_SIZE)
                ->get();
        } else {
            $orders = $ordersQuery
                ->with($this->salesOrderRelations())
                ->get();
            $total = $orders->count();
        }

        return [
            'id' => $status, // puedes usar el valor del estado como id
            'title' => $status,
            'total_tasks' => $total,
            'tasks' => $orders->map(function ($order) use ($status) {
                return $this->mapSalesOrderToTask($order, $status);
            })->values(),
        ];
    });

      $ownerOptions = User::role(RoleEnum::OWNER->value)
          ->select('id', 'name')
          ->orderBy('name');

      if ($this->isOwnerRestricted($user)) {
          $ownerOptions->where('id', $user->id);
      }

      $supervisors = User::role(RoleEnum::SUPERVISOR->value)
          ->select('id', 'name')
          ->where('status', StatusUserEnum::ACTIVE->value)
          ->orderBy('name')
          ->get();

      $tags = Tag::query()
          ->where('taggable_type', Order::class)
          ->select('id', 'name')
          ->orderBy('name')
          ->get();

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
      'statuses' => $visibleStatuses,
      'owners' => $ownerOptions->get(),
      'supervisors' => $supervisors,
      'created_by_users' => $createdByUsers,
      'tags' => $tags,
      'filters' => $filters,
      'methods_of_payment' => array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()),
      'type_of_financing' => array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases()),
      'payment_schedule_templates' => PaymentScheduleTemplates::templates(),
    ]);
  }

  public function tasks(Request $request): JsonResponse
  {
    $user = auth()->user();
    $status = (string) $request->query('status', '');
    $page = max(1, (int) $request->query('page', 1));
    $perPage = (int) $request->query('per_page', self::SALES_PAGE_SIZE);
    $perPage = max(1, min(100, $perPage));

    $allowedStatuses = $this->salesStatuses();
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
    $ordersQuery = $this->salesOrdersForStatusQuery($status, $user);
    $ordersQuery = $hasMultiFilters
        ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
        : OrderBoardFilter::apply($ordersQuery, $filters);
    $total = (clone $ordersQuery)->count();
    $orders = $ordersQuery
      ->with($this->salesOrderRelations())
      ->orderByDesc('updated_at')
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
      'user',
      'owners',
      'orderStatus',
      'tags:id,name,color,taggable_id,taggable_type',
      'orderCompanyContacts.companyContact',
      'orderCompanyContacts.client',
      'orderCompanyContacts.source',
    ];
  }

  private function salesOrdersForStatusQuery(string $status, ?User $user): Builder
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
      $query->whereHas('owners', function ($query) use ($user) {
        $query->where('users.id', $user->id);
      });
    }

    return $query;
  }

  private function mapSalesOrderToTask(Order $order, string $status): array
  {
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

    return [
      'id' => $order->id,
      'title' => $order->name ?? 'No Title',
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
      'contact_email' => optional($order->client)->email,
      'created_by' => $order->user->name ?? null,
      'is_supply' => (bool) ($order->is_supply ?? false),
      'name_check' => (bool) ($order->name_check ?? false),
      'address_check' => (bool) ($order->address_check ?? false),
      'amount_check' => (bool) ($order->amount_check ?? false),
      'email_check' => (bool) ($order->email_check ?? false),
      'project_amount' => $order->project_amount ? (float) $order->project_amount : 0,
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
      'bid_due_date' => $this->resolveBidDueDate($order),
      'order_company_contacts' => $order->orderCompanyContacts->map(fn ($item) => [
        'id' => $item->id,
        'company_name' => $item->companyContact?->name,
        'client_id' => $item->client_id,
        'client_name' => $item->client?->name,
        'client_email' => $item->client?->email,
        'is_selected' => (bool) ($item->is_selected ?? false),
      ])->values(),
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
  private function determineSalesBoardStatus(Order $order): ?string
  {
    $status = $order->status;
    if (in_array($status, $this->salesStatuses(), true)) {
      return $status;
    }
    if (in_array($status, [
      OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
      OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value
    ], true)) {
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

    $statuses = $this->salesStatuses();
    if ($this->isOwnerRestricted($user)) {
      $statuses = $this->ownerVisibleSalesStatuses();
    }

    $legend = collect($statuses)->map(fn ($status) => [
      'label' => $status,
      'color' => $this->salesStatusColor($status),
    ])->values();

    return Inertia::render('Sales/Calendar', [
      'statuses' => $statuses,
      'legend' => $legend,
    ]);
  }

  public function calendarEvents(Request $request, int $year, int $month): JsonResponse
  {
    $user = auth()->user();
    $statusFilter = $request->query('status');

    $allowedStatuses = $this->salesStatuses();
    if ($this->isOwnerRestricted($user)) {
      $allowedStatuses = $this->ownerVisibleSalesStatuses();
    }

    $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->subWeek();
    $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->addWeek();

    $ordersQuery = Order::with(['client', 'owners'])
      ->whereNotNull('schedule_appointment')
      ->whereBetween('schedule_appointment', [$start, $end])
      ->whereIn('status', $allowedStatuses);

    if (!empty($statusFilter) && $statusFilter !== 'all') {
      if (!in_array($statusFilter, $allowedStatuses, true)) {
        return response()->json([]);
      }
      $ordersQuery->where('status', $statusFilter);
    }

    if ($this->isOwnerRestricted($user)) {
      $ordersQuery->whereHas('owners', function ($query) use ($user) {
        $query->where('users.id', $user->id);
      });
    }

    $events = $ordersQuery->get()->map(function (Order $order) {
      $start = Carbon::parse($order->schedule_appointment);
      $end = (clone $start)->addHour();

      $clientName = optional($order->client)->name ?? 'Client';
      $owners = $order->owners->pluck('name')->filter();
      $primaryLine = ($order->name ?? 'Order');
      $secondaryLine = $start->format('h:i A') . ($owners->isNotEmpty() ? ' (' . $owners->implode(', ') . ')' : '');
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
        'start' => $start->format('Y-m-d\TH:i'),
        'end' => $end->format('Y-m-d\TH:i'),
        'color' => $this->salesStatusColor($order->status),
        'type_of_event' => $order->status,
        'text' => $order->name ?? 'Order',
        'order_name' => $order->name ?? 'Order',
        'appointment_time' => $start->format('h:i A'),
        'owner_names' => $owners->implode(', '),
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

  public function create()
  {
    return Inertia::render('Frontdesk/Create', [
      'clients' => Client::all(),
      'owners' => User::role(RoleEnum::OWNER->value)->get(),
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
        $validated = $request->validate([
          'status' => ['required', Rule::in([
            OrderStatusEnum::FOLLOW_UP->value,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
          ])],
          'project_amount' => ['required', 'numeric', 'min:1'],
          'note' => ['nullable', 'string'],
          'attachments' => ['required', 'array'],
          'attachments.*' => ['file', 'max:10240', 'mimes:pdf'],
        ]);

        $noteContent = trim((string) ($validated['note'] ?? ''));

        DB::transaction(function () use ($order, $validated, $request, $noteContent) {
          $order->project_amount = $validated['project_amount'];
          $order->status = $validated['status'];
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

    DB::transaction(function () use ($order, $noteContent) {
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

    $order->load('owners');

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

  public function assignRequestReschedule(Request $request, Order $order)
  {
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

    $this->sendEmail($order);

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
    $validated = $request->validate([
      'note' => ['nullable', 'string'],
    ]);

    $noteContent = trim($validated['note']);

    /*if ($noteContent === '') {
      return response()->json([
        'message' => 'La nota es obligatoria.',
        'errors' => ['note' => ['La nota es obligatoria.']],
      ], 422);
    }*/

    DB::transaction(function () use ($order, $noteContent) {
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

    $order->load('owners');

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

  public function assignContractSigned(Request $request, Order $order)
  {
    if ($request->has('type_of_financing') && trim((string) $request->input('type_of_financing')) === '') {
      $request->merge(['type_of_financing' => null]);
    }

    if ($request->has('down_payment') && trim((string) $request->input('down_payment')) === '') {
      $request->merge(['down_payment' => null]);
    }

    $orderCompanyContacts = $order->orderCompanyContacts()->with('client')->get();
    $companyCount = $orderCompanyContacts->count();

    $rules = [
      'project_name' => ['required', 'string', 'max:255'],
      'project_amount' => ['required', 'numeric', 'min:1'],
      'job_address' => ['nullable', 'string'],
      'city' => ['nullable', 'string', 'max:255'],
      'job_state' => ['nullable', 'string', 'max:255'],
      'job_zip' => ['nullable', 'string', 'max:50'],
      'contact_email' => ['required', 'email', 'max:255'],
      'name_check' => ['nullable', 'boolean'],
      'address_check' => ['nullable', 'boolean'],
      'amount_check' => ['nullable', 'boolean'],
      'email_check' => ['nullable', 'boolean'],
      'method_of_payment' => ['required', Rule::in(array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()))],
      'type_of_financing' => ['nullable', Rule::in(array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases()))],
      'down_payment' => ['nullable', 'numeric', 'min:0'],
      'payment_schedule_type' => [
        'nullable',
        Rule::requiredIf($request->input('method_of_payment') === MethodOfPayment::CASH->value),
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
    $validator->after(function ($validator) use ($request) {
      $scheduleType = (string) $request->input('payment_schedule_type');
      $requiresSchedule = $request->input('method_of_payment') === MethodOfPayment::CASH->value;
      $customSchedule = $request->input('custom_schedule', []);

      if ($requiresSchedule && $scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
        if (!is_array($customSchedule) || count($customSchedule) === 0) {
          $validator->errors()->add('custom_schedule', 'Add at least one custom payment.');
          return;
        }

        $total = 0.0;
        foreach ($customSchedule as $item) {
          $total += (float) ($item['amount'] ?? 0);
        }

        $projectAmount = (float) $request->input('project_amount', 0);
        if (abs($total - $projectAmount) > 0.01) {
          $validator->errors()->add('custom_schedule', 'Custom payments must total the project amount.');
        }
      }
    });

    $validated = $validator->validate();

    $contactEmail = trim((string) ($validated['contact_email'] ?? ''));
    $confirmCustomerRole = $request->boolean('confirm_customer_role');
    if ($contactEmail !== '') {
      $existingUser = User::withTrashed()->where('email', $contactEmail)->first();
      if ($existingUser) {
        $existingUser->loadMissing('roles');
        $hasCustomerRole = $existingUser->hasRole(RoleEnum::CUSTOMER->value);
        if (!$hasCustomerRole && !$confirmCustomerRole) {
          return response()->json([
            'message' => 'This email already belongs to a user with role(s): ' . $existingUser->roles->pluck('name')->implode(', ') . '. Do you want to convert it to customer?',
            'requires_confirmation' => true,
            'user_email' => $existingUser->email,
            'user_roles' => $existingUser->roles->pluck('name')->values(),
          ], 409);
        }
      }
    }

    DB::transaction(function () use ($order, $validated, $request, $orderCompanyContacts, $companyCount) {
      $newPipelineStatus = $order->is_supply
        ? OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value
        : OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value;

      $order->name = $validated['project_name'];
      $order->project_amount = $validated['project_amount'];
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
      $order->down_payment = $validated['down_payment'] ?? null;
      $order->name_check = $request->boolean('name_check');
      $order->address_check = $request->boolean('address_check');
      $order->amount_check = $request->boolean('amount_check');
      $order->email_check = $request->boolean('email_check');
      $order->status = $newPipelineStatus;
      $order->save();

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

      $contactEmail = trim($validated['contact_email']);
      $clientForEmail = $selectedContact?->client ?? $order->client;
      if ($clientForEmail && $contactEmail !== '') {
        $clientForEmail->email = $contactEmail;
        $clientForEmail->save();
      }
      $order->orderStatus()->create([
        'status' => OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
        'user_id' => auth()->id(),
        'notes' => OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value . ' updated by ' . auth()->user()->name,
      ]);
      event(new OrderStatusChanged(
        $order,
        OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
        $request->boolean('confirm_customer_role')
      ));

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
      $totalAmount = (float) $order->project_amount;
      $requiresSchedule = $validated['method_of_payment'] === MethodOfPayment::CASH->value;

      if (!$requiresSchedule || !$scheduleType) {
        $existingSchedule = $order->paymentSchedule()->first();
        if ($existingSchedule) {
          $existingSchedule->installments()->delete();
          $existingSchedule->delete();
        }
      } elseif ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
        $installments = [];
        $runningPercent = 0.0;
        $count = count($customSchedule);
        foreach ($customSchedule as $index => $item) {
          $amount = round((float) ($item['amount'] ?? 0), 2);
          $percentage = $totalAmount > 0
            ? round(($amount / $totalAmount) * 100, 2)
            : 0;

          if ($index === $count - 1 && $totalAmount > 0) {
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
            'total_amount' => $totalAmount,
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
      } else {
        $scheduleItems = PaymentScheduleTemplates::itemsFor($scheduleType);
        $installments = PaymentScheduleCalculator::withAmounts($scheduleItems, $totalAmount);

        $paymentSchedule = PaymentSchedule::updateOrCreate(
          ['order_id' => $order->id],
          [
            'schedule_type' => $scheduleType,
            'total_amount' => $totalAmount,
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
      }
    });

    $order->load('owners', 'client', 'paymentSchedule.installments.paidBy', 'orderCompanyContacts.companyContact', 'orderCompanyContacts.client', 'orderCompanyContacts.source');

    $schedule = $order->schedule_appointment
      ? Carbon::parse($order->schedule_appointment)
      : null;

    return response()->json([
      'order' => [
        'id' => $order->id,
        'name' => $order->name,
        'status' => $order->status,
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
        'contact_email' => $order->client?->email,
        'name_check' => (bool) ($order->name_check ?? false),
        'address_check' => (bool) ($order->address_check ?? false),
        'amount_check' => (bool) ($order->amount_check ?? false),
        'email_check' => (bool) ($order->email_check ?? false),
        'payment_schedule' => $order->paymentSchedule
          ? [
            'id' => $order->paymentSchedule->id,
            'schedule_type' => $order->paymentSchedule->schedule_type,
            'total_amount' => $order->paymentSchedule->total_amount,
            'installments' => $order->paymentSchedule->installments->map(fn ($installment) => [
              'id' => $installment->id,
              'label' => $installment->label,
              'percentage' => $installment->percentage,
              'amount' => $installment->amount,
              'due_date' => $installment->due_date?->format('Y-m-d'),
              'status' => $installment->status,
              'paid_at' => $installment->paid_at?->toISOString(),
              'position' => $installment->position,
              'paid_by' => $installment->paidBy
                ? ['id' => $installment->paidBy->id, 'name' => $installment->paidBy->name]
                : null,
            ])->values(),
          ]
          : null,
        'order_company_contacts' => $order->orderCompanyContacts->map(fn ($item) => [
          'id' => $item->id,
          'company_name' => $item->companyContact?->name,
          'client_id' => $item->client_id,
          'client_name' => $item->client?->name,
          'client_email' => $item->client?->email,
          'is_selected' => (bool) ($item->is_selected ?? false),
        ])->values(),
      ],
    ]);
  }

  public function assignLostContract(Request $request, Order $order)
  {
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
