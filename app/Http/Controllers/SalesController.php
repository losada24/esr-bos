<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderPipeline;
use App\Enum\ContactSourceEnum;
use App\Enum\FrontdeskStatusEnum;
use App\Enum\LostReasonfrontdeskEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\MethodOfPayment;
use App\Enum\TypeOfFinancing;
use App\Enum\RoleEnum;
use App\Http\Requests\StoreFrontDeskOrderRequest;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Traits\OrderEmails;
use Illuminate\Http\JsonResponse;

class SalesController extends Controller
{
    use OrderEmails;
    private const SALES_PAGE_SIZE = 20;

    public function index()
    {
        $user = auth()->user();

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
    $data = collect($visibleStatuses)->map(function ($status) use ($user, $paginatedStatuses) {
        $ordersQuery = $this->salesOrdersForStatusQuery($status, $user);

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

    return Inertia::render('Sales/Index', [
      'data' => $data,
      'lossReasonFrontdesk' => $lossReasonFrontdesk,
      'sources' => $sources,
      'order_types' => $order_types,
      'owners' => $ownerOptions->get(),
      'methods_of_payment' => array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()),
      'type_of_financing' => array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases()),
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

    $ordersQuery = $this->salesOrdersForStatusQuery($status, $user);
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
      'phone'=> $order->client->phone ?? null,
      'contact_email' => $order->client->email ?? null,
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
      'tags'       => ($order->tags ?? collect())->map(function ($t) {
        return [
          'name'  => $t->name,
          'color' => $t->color,
        ];
      })->values(),
    ];
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

      $clientName = $order->client->name ?? 'Client';
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
    return [
      OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
      OrderStatusEnum::FOLLOW_UP->value,
      OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
      OrderStatusEnum::STAND_BY->value,
      OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value,
      OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
      OrderStatusEnum::LOST_CONTRACT->value,
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
        ContactSourceEnum::META->value,
        ContactSourceEnum::DESTINO_TOLK->value,
        ContactSourceEnum::RESOURCE_MAGAZINE->value,
        ContactSourceEnum::BANNER_PUBLICITARIO->value,
        ContactSourceEnum::PICHY_BOYS->value,
        ContactSourceEnum::GOOGLE_MY_BUSINESS->value,
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

        DB::transaction(function () use ($order, $validated, $request) {
          $order->project_amount = $validated['project_amount'];
          $order->status = $validated['status'];
          $order->save();

          $order->orderStatus()->create([
            'status' => $order->status,
            'user_id' => auth()->id(),
            'notes' => $order->status . ' updated by ' . auth()->user()->name,
          ]);

          $order->notes()->create([
            'content' => $validated['note'],
            'type' => 'order_note',
            'user_id' => auth()->id(),
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

    $validated = $request->validate([
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
      'attachments' => ['required', 'array', 'min:1'],
      'attachments.*' => ['file', 'max:10240', 'mimes:pdf'],
    ]);

    DB::transaction(function () use ($order, $validated, $request) {
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
      $contactEmail = trim($validated['contact_email']);
      if ($order->client && $contactEmail !== '') {
        $order->client->email = $contactEmail;
        $order->client->save();
      }
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
    });

    $order->load('owners', 'client');

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
