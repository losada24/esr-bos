<?php

namespace App\Http\Controllers;

use App\Actions\CreateOrderPipeline;
use App\Actions\CreateQualifiedOrder;
use App\Actions\UpdateQualifiedOrder;
use App\Enum\ContactSourceEnum;
use App\Enum\FrameColorEnum;
use App\Enum\FrontdeskStatusEnum;
use App\Enum\GlassCoatingEnum;
use App\Enum\GlassColorEnum;
use App\Enum\GlassTypeEnum;
use App\Enum\LanguageEnum;
use App\Enum\LostReasonfrontdeskEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Enum\MethodOfPayment;
use App\Enum\TypeOfFinancing;
use App\Enum\StatusUserEnum;
use App\Http\Requests\StoreFrontDeskOrderRequest;
use App\Http\Requests\StoreQualifiedOrderRequest;
use App\Http\Requests\UpdateQualifiedOrderRequest;
use App\Models\Client;
use App\Models\CompanyContact;
use Illuminate\Http\Request;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\SaleForm;
use App\Models\Source;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\OrderEmails;
use App\Traits\Snapshot;
use App\Support\PaymentScheduleTemplates;
use App\Support\OrderBoardFilter;
use Illuminate\Validation\Rule;

class FrontdeskController extends Controller
{   
    use OrderEmails, Snapshot;
    private const FRONTDESK_PAGE_SIZE = 20;

    private function frontdeskStatuses(): array
    {
        return [
            OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
            OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
            OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
            OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
            OrderStatusEnum::QUALIFIED->value,
        ];
    }

    private function paginatedFrontdeskStatuses(): array
    {
        return [
            OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
            OrderStatusEnum::QUALIFIED->value,
        ];
    }

    private function frontdeskOrderQuery(string $status): Builder
    {
        if ($status === OrderStatusEnum::QUALIFIED->value) {
            return Order::query()
                ->whereHas('orderStatus', function ($query) use ($status) {
                    $query->where('status', $status);
                });
        }

        return Order::query()->where('status', $status);
    }

    private function mapFrontdeskOrderToTask(Order $order, string $status): array
    {
        $statusHistoryEntry = $order->orderStatus
            ->where('status', $status)
            ->sortByDesc('created_at')
            ->first();

        $statusCreatedAt = optional($statusHistoryEntry)->created_at ?? $order->created_at;

        return [
            'id'          => $order->id,
            'title'       => $order->name ?? 'No Title',
            'client_id'   => $order->client_id ?? null,
            'date_edited' => optional($order->updated_at)->format('M d, Y h:i A'),
            'date'        => optional($statusCreatedAt)->format('M d, Y h:i A'),
            'status_created_at_iso' => optional($statusCreatedAt)->toIso8601String(),
            'schedule_appointment' => $order->schedule_appointment ? Carbon::parse($order->schedule_appointment)->format('M d, Y h:i A') : null,
            'phone'       => $order->client->phone ?? null,
            'created_by'  => $order->user->name ?? null,
            'is_supply'   => (bool) ($order->is_supply ?? false),
            'tags'        => ($order->tags ?? collect())->map(fn($t) => [
                'name'  => $t->name,
                'color' => $t->color,
            ])->values(),
        ];
    }

    public function index(Request $request)
    {
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
      // Definir los estados del Frontdesk (como strings usando el enum)
    $frontdeskStatuses = $this->frontdeskStatuses();
    $paginatedStatuses = $this->paginatedFrontdeskStatuses();
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
      //OrderTypeEnum::SUPPLY->value,
    ];

    $data = collect($frontdeskStatuses)->map(function ($status) use ($paginatedStatuses, $filters, $filterRows, $filterMatch, $hasMultiFilters) {
        $ordersQuery = $this->frontdeskOrderQuery($status);
        $ordersQuery = $hasMultiFilters
            ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
            : OrderBoardFilter::apply($ordersQuery, $filters);

        if (in_array($status, $paginatedStatuses, true)) {
            $total = (clone $ordersQuery)->count();
            $orders = $ordersQuery
                ->with(['client','user','orderStatus','tags:id,name,color,taggable_id,taggable_type'])
                ->orderByDesc('updated_at')
                ->limit(self::FRONTDESK_PAGE_SIZE)
                ->get();
        } else {
            $orders = $ordersQuery
                ->with(['client','user','orderStatus','tags:id,name,color,taggable_id,taggable_type'])
                ->get();
            $total = $orders->count();
        }

        return [
            'id' => $status,
            'title' => $status,
            'total_tasks' => $total,
            'tasks' => $orders->map(function ($order) use ($status) {
                return $this->mapFrontdeskOrderToTask($order, $status);
            })->values(),
        ];
    });
      $owners = User::role(RoleEnum::OWNER->value)
        ->select('id', 'name')
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->orderBy('name')
        ->get();

      $supervisors = User::role(RoleEnum::SUPERVISOR->value)
        ->select('id', 'name')
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->orderBy('name')
        ->get();

      $createdByUsers = User::query()
        ->select('id', 'name')
        ->where('status', StatusUserEnum::ACTIVE->value)
        ->orderBy('name')
        ->get();

      $tags = Tag::query()
        ->where('taggable_type', Order::class)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

      return Inertia::render('Frontdesk/Index', [
        'data' => $data,
        'lossReasonFrontdesk' => $lossReasonFrontdesk,
        'sources' => $sources,
        'order_types' => $order_types,
        'owners' => $owners,
        'supervisors' => $supervisors,
        'created_by_users' => $createdByUsers,
        'tags' => $tags,
        'statuses' => $frontdeskStatuses,
        'filters' => $filters,
        'frame_colors' => [
          FrameColorEnum::BLACK->value,
          FrameColorEnum::WHITE->value,
          FrameColorEnum::BRONZE->value,
          FrameColorEnum::CLEAR_ANODIZED->value,
          FrameColorEnum::OTHERS->value,
        ],
        'glass_colors' => [
          GlassColorEnum::BRONZE->value,
          GlassColorEnum::CLEAR->value,
          GlassColorEnum::GRAY->value,
          GlassColorEnum::GREEN->value,
          GlassColorEnum::OTHERS->value,
        ],
        'glass_types' => [
          GlassTypeEnum::LAMINATED->value,
          GlassTypeEnum::INSULATED->value,
          GlassTypeEnum::INSULATED_LAMINATED->value,
        ],
        'glass_coatings' => [
          GlassCoatingEnum::LOWE70->value,
          GlassCoatingEnum::LOWE60->value,
        ],
        'languages' => array_map(
          static fn (LanguageEnum $language) => $language->value,
          LanguageEnum::cases()
        ),
      ]);
    }

    public function tasks(Request $request)
    {
      $status = (string) $request->query('status', '');
      $page = max(1, (int) $request->query('page', 1));
      $perPage = (int) $request->query('per_page', self::FRONTDESK_PAGE_SIZE);
      $perPage = max(1, min(100, $perPage));

      $frontdeskStatuses = $this->frontdeskStatuses();
      if (!in_array($status, $frontdeskStatuses, true)) {
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
      $ordersQuery = $this->frontdeskOrderQuery($status);
      $ordersQuery = $hasMultiFilters
        ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
        : OrderBoardFilter::apply($ordersQuery, $filters);
      $total = (clone $ordersQuery)->count();
      $orders = $ordersQuery
        ->with(['client','user','orderStatus','tags:id,name,color,taggable_id,taggable_type'])
        ->orderByDesc('updated_at')
        ->forPage($page, $perPage)
        ->get();

      $tasks = $orders->map(function ($order) use ($status) {
        return $this->mapFrontdeskOrderToTask($order, $status);
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

   public function createQualified()
  {
    return Inertia::render('Frontdesk/CreateQualified', [
      'clients' => Client::all(),
      'owners' => User::role(RoleEnum::OWNER->value)->get(),
      'status' => [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
      ],
      'sources' => Source::all(),
      'sourcesClients' => [
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
      'frame_colors' => [
        FrameColorEnum::BLACK->value,
        FrameColorEnum::WHITE->value,
        FrameColorEnum::BRONZE->value,
        FrameColorEnum::CLEAR_ANODIZED->value,
        FrameColorEnum::OTHERS->value,
      ],
      'glass_colors' => [
        GlassColorEnum::BRONZE->value,
        GlassColorEnum::CLEAR->value,
        GlassColorEnum::GRAY->value,
        GlassColorEnum::GREEN->value,
        GlassColorEnum::OTHERS->value,
      ],
      //dd(Source::all()),
      'order_types' => [
        OrderTypeEnum::RESIDENTIAL->value,
        OrderTypeEnum::COMMERCIAL->value,
      ],
      'glass_coatings' => [
        GlassCoatingEnum::LOWE70->value,
        GlassCoatingEnum::LOWE60->value,
      ],
      'glass_types' => [
        GlassTypeEnum::LAMINATED->value,
        GlassTypeEnum::INSULATED->value,
        GlassTypeEnum::INSULATED_LAMINATED->value,
      ],
      'languages' => array_map(
        static fn (LanguageEnum $language) => $language->value,
        LanguageEnum::cases()
      ),
      'companies' => CompanyContact::all(),
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
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json(['message' => 'You are not authorized to update this order.'], 403);
    }

    $order->status = $request->input('status');

    $order->save();
      $order->orderStatus()->create([
        'status' => $request->input('status'),
        'user_id' => auth()->user()->id,
        'notes' => "{$request->input('status')} created by " . auth()->user()->name,
      ]);


    return response()->json(['success' => true, 'order' => $order]);
  }

  public function updateStatusStandBy(Request $request, Order $order)
  {
      $data = $request->validate([
          'note' => ['required', 'string'],
      ]);

      $status = OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value;
      $user = $request->user();

      DB::transaction(function () use ($order, $status, $data, $user) {
          $order->notes()->create([
              'content' => $data['note'],
              'type' => 'order_note',
              'user_id' => $user?->id,
          ]);

          $order->update(['status' => $status]);

          $order->orderStatus()->create([
              'status' => $status,
              'user_id' => $user?->id,
              'notes' => "{$status} created by " . ($user?->name ?? 'System'),
          ]);
      });
      $order->load('user'); // Relación con User
      $this->sendEmail($order);
      $order->refresh();
      

      return response()->json(['success' => true, 'order' => $order]);
  }

  public function updateStatusLost(Request $request, Order $order)
  {
    $data = $request->validate([
      'status' => ['required', 'string'],
      'loss_reason_frontdesk' => ['required', 'string', 'max:255'],
      'notes' => ['nullable', 'string', 'max:2000'],
    ]);

    DB::transaction(function () use ($order, $data) {
      $payload = [
        'status' => $data['status'],
        'loss_reason_frontdesk' => $data['loss_reason_frontdesk'],
      ];

      if (filled($data['notes'] ?? null)) {
        $payload['notes'] = $data['notes'];
      }

      if (filled($data['notes'] ?? null)) {
        $order->notes()->create([
          'content' => $data['notes'],
          'type' => 'order_note',
          'user_id' => auth()->id(),
        ]);
      }

      $order->update($payload);

      $order->orderStatus()->create([
        'status' => $data['status'],
        'user_id' => auth()->user()->id,
        'notes' => "{$data['status']} created by " . auth()->user()->name,
      ]);
    });

    $order->refresh();

    return response()->json(['success' => true, 'order' => $order]);
}

public function showQuantifiedModal(Order $order)
{
    $order->load('client'); // Relación con Client
    $latestNote = $order->notes()->latest()->first();
    $order->setAttribute('latest_note', $latestNote?->content ?? '');

    return response()->json($order);
}
    
    public function updateStatusQuantified(Request $request, Order $order)
    {     
        //dd($request->all());
        $request->validate([
          'phone' => ['required', 'regex:/^\d{10}$/'],
          'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $status = $request['status'];
        if ($request['order_type'] === OrderTypeEnum::RESIDENTIAL->value || $request['order_type'] === OrderTypeEnum::SUPPLY->value) {
          $status = OrderStatusEnum::PENDING_ASSIGNMENT->value;
        } 
        if ($request['order_type'] === OrderTypeEnum::COMMERCIAL->value) {
          $status = OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value;
        }
        
        $scheduleAppointment = $request->input('schedule_appointment');

        $orderPayload = [
          'name' => $request['name'],
          'order_type' => $request['order_type'],
          'job_address' => $request['job_address'],
          'city' => $request['city'],
          'job_state' => $request['job_state'],
          'job_zip' => $request['job_zip'],
          'bid_due_date' => $request['bid_due_date'],
          'project_amount' => $request['project_amount'],
          'description' => $request['description'],
          'schedule_appointment' => $scheduleAppointment
            ? Carbon::parse($scheduleAppointment)
            : null,
          'status' => $status,
          'is_supply' => $request['is_supply'] ?? false,
        ];

        if ($request->filled('notes')) {
          $orderPayload['notes'] = $request['notes'];
        }

        $existingSaleForm = $order->saleForm;
        $saleFormPayload = [
          'sale' => $request->boolean('sale'),
          'installation' => $request->boolean('installation'),
          'permit' => $request->boolean('permit'),
          'replacement' => $request->boolean('replacement'),
          'new_construction' => $request->boolean('new_construction'),
          'financing' => $request->boolean('financing'),
          'screen' => $request->boolean('screen'),
          'design' => $request->boolean('design'),
          'mountin' => $request->boolean('mountin'),
          'bar' => $request->boolean('bar'),
          'shutter_hole' => $request->boolean('shutter_hole'),
          'floor_cutting' => $request->boolean('floor_cutting'),
          'interior_finish' => $request->boolean('interior_finish'),
          'hoa' => $request->boolean('hoa'),
          'floor' => $request->input('floor', $existingSaleForm->floor ?? ''),
          'frame_color' => $request->input('frame_color', $existingSaleForm->frame_color ?? ''),
          'glass_color' => $request->input('glass_color', $existingSaleForm->glass_color ?? ''),
          'glass_type' => $request->input('glass_type', $existingSaleForm->glass_type ?? ''),
          'glass_coating' => $request->input('glass_coating', $existingSaleForm->glass_coating ?? ''),
          'language' => $request->input('language', $existingSaleForm->language ?? ''),
          'door_quantity' => $request->filled('door_quantity')
            ? (int) $request->input('door_quantity')
            : ($existingSaleForm->door_quantity ?? 0),
          'window_quantity' => $request->filled('window_quantity')
            ? (int) $request->input('window_quantity')
            : ($existingSaleForm->window_quantity ?? 0),
        ];

        if ($existingSaleForm) {
          $existingSaleForm->update($saleFormPayload);
        } else {
          $order->saleForm()->create($saleFormPayload);
        }

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
          if ($request->filled('notes')) {
            $order->notes()->create([
              'content' => $request['notes'],
              'type' => 'order_note',
              'user_id' => auth()->id(),
            ]);
          }

          $order->update($orderPayload);

          $order->orderStatus()->create([
            'status' => $request['status'],
            'user_id' => auth()->user()->id,
            'notes' => "{$request['status']} created by " . auth()->user()->name,
          ]);

          $order->orderStatus()->create([
            'status' => $status,
            'user_id' => auth()->user()->id,
            'notes' => "{$status} created by " . auth()->user()->name,
          ]);

        $order->load('saleForm', 'client');

          $this->sendEmail($order);

        return response()->json(['success' => true, 'order' => $order]);
    }

    public function storeQualifiedOrder(StoreQualifiedOrderRequest $storeQualifiedOrderRequest, CreateQualifiedOrder $createQualifiedOrder)
  {
    $createQualifiedOrder->handle($storeQualifiedOrderRequest);
    return redirect()->route('frontdesk.index')
      ->with('success', 'Contact created successfully.');
  }
  
    public function orderView($id)
  { // Obtener las órdenes por supervisor

    $order = Order::findOrFail($id);
    if (!$this->ownerCanAccessOrder(auth()->user(), $order)) {
      abort(403, 'You are not authorized to access this order.');
    }
    $order->load('tags:id,name,color,taggable_id,taggable_type', 'client.companyContact', 'user', 'owners', 'saleForm', 'attachments.user', 'orderStatus.user', 'paymentSchedule.installments.paidBy');

    $clientOrders = collect();

    if ($order->client) {
      $clientOrders = $order->client->orders()
        ->where('id', '!=', $order->id)
        ->with(['owners:id,name'])
        ->orderByDesc('created_at')
        ->get(['id', 'order_number', 'name', 'status', 'order_type'])
        ->map(fn ($clientOrder) => [
          'id' => $clientOrder->id,
          'order_number' => $clientOrder->order_number,
          'name' => $clientOrder->name,
          'status' => $clientOrder->status,
          'order_type' => $clientOrder->order_type,
          'owners' => $clientOrder->owners
            ->map(fn ($owner) => [
              'id' => $owner->id,
              'name' => $owner->name,
            ])
            ->values()
            ->all(),
        ]);
    }

    if ($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value && !$order->saleForm) {
      $order->saleForm()->create($this->buildSaleFormPayload($order, null));
      $order->load('saleForm');
    }
    $orderStatuses = OrderStatus::where('order_id', $id)
      ->with(['order', 'user'])
      ->get();

    $orderSnapshots = $order->snapshots()
      ->with(['user:id,name'])
      ->orderBy('created_at')
      ->get()
      ->map(function ($snapshot) {
        return [
          'id' => $snapshot->id,
          'status' => $snapshot->status,
          'created_at' => optional($snapshot->created_at)->toISOString(),
          'user' => $snapshot->user ? [
            'id' => $snapshot->user->id,
            'name' => $snapshot->user->name,
          ] : null,
          'snapshot_data' => $snapshot->snapshot_data,
        ];
      });

        $usedTags = Tag::select('name', 'color', DB::raw('COUNT(*) AS count'))
            ->where('type', 'order')     // o ->where('taggable_type', Order::class)
            ->groupBy('name', 'color')
            ->orderByDesc('count')
            ->limit(200)
            ->get()
            ->map(fn ($t) => [
                'name'  => $t->name,
                'color' => $t->color ?? 'gray',
                'count' => (int) $t->count,
            ]);

            //dd($usedTags);


    $ownerOptionsQuery = User::role(RoleEnum::OWNER->value)
      ->select('id', 'name')
      ->orderBy('name');

    if ($this->isOwnerRestricted(auth()->user())) {
      $ownerOptionsQuery->where('id', auth()->id());
    }

    $ownerOptions = $ownerOptionsQuery->get();

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
      //OrderTypeEnum::SUPPLY->value,
    ];
    $frame_colors = [
      FrameColorEnum::BLACK->value,
      FrameColorEnum::WHITE->value,
      FrameColorEnum::BRONZE->value,
      FrameColorEnum::CLEAR_ANODIZED->value,
      FrameColorEnum::OTHERS->value,
    ];
    $glass_colors = [
      GlassColorEnum::BRONZE->value,
      GlassColorEnum::CLEAR->value,
      GlassColorEnum::GRAY->value,
      GlassColorEnum::GREEN->value,
      GlassColorEnum::OTHERS->value,
    ];
    $glass_types = [
      GlassTypeEnum::LAMINATED->value,
      GlassTypeEnum::INSULATED->value,
      GlassTypeEnum::INSULATED_LAMINATED->value,
    ];
    $glass_coatings = [
      GlassCoatingEnum::LOWE70->value,
      GlassCoatingEnum::LOWE60->value,
    ];
    $languages = array_map(
      static fn (LanguageEnum $language) => $language->value,
      LanguageEnum::cases()
    );

    $methodsOfPayment = array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases());
    $typeOfFinancing = array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases());

    $clients = Client::with(['companyContact'])
      ->select('id', 'name', 'phone', 'email', 'other_phone', 'secondary_email', 'source', 'vip_clients', 'vip_notes', 'company_contact_id')
      ->orderBy('name')
      ->get();

    $companies = CompanyContact::select('id', 'name')->orderBy('name')->get();

    $qualifiedSources = Source::select('id', 'name')->orderBy('name')->get();

    $sourcesClients = [
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

    $statusOptions = [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
    ];

    // Obtener los parámetros de filtro de la solicitud (request)
    return Inertia::render('Frontdesk/OrderView', [
      //'orderStatuses' => $orderStatuses,
      'order' => $order,
      'snapshots' => $orderSnapshots,
      'clientOrders' => $clientOrders,
       'tags' => $order->tags->map(fn($t) => [
                'name'  => $t->name,
                'color' => $t->color,
            ]),
      'usedTags' => $usedTags,
      'ownerOptions' => $ownerOptions,
      'lossReasonFrontdesk' => $lossReasonFrontdesk,
      'sources' => $sources,
      'qualifiedSources' => $qualifiedSources,
      'clients' => $clients,
      'companies' => $companies,
      'sourcesClients' => $sourcesClients,
      'status' => $statusOptions,
      'order_types' => $order_types,
      'methods_of_payment' => $methodsOfPayment,
      'type_of_financing' => $typeOfFinancing,
      'payment_schedule_templates' => PaymentScheduleTemplates::templates(),
      'frame_colors' => $frame_colors,
      'glass_colors' => $glass_colors,
      'glass_types' => $glass_types,
      'glass_coatings' => $glass_coatings,
      'languages' => $languages,
      /*'orderStatuses' => $orderStatuses->map(function ($status) {
        return [
          ...$status->toArray(),
          'created_at_formatted' => Carbon::parse($status->created_at)
            ->setTimezone('America/New_York')
            ->format('Y-m-d'),
        ];
      }),*/


    ]);
  }

  public function updateQualifiedOrder(
    UpdateQualifiedOrderRequest $request,
    UpdateQualifiedOrder $updateQualifiedOrder,
    Order $order
  ) {
    $updatedOrder = $updateQualifiedOrder->handle($request, $order);

    return response()->json([
      'success' => true,
      'order' => $updatedOrder,
    ]);
  }

  public function updateOrderContact(Request $request, Order $order)
  {
    $mode = $request->input('mode');
    $frontdeskStatuses = [
      OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
      OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
      OrderStatusEnum::QUALIFIED->value,
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

    $rules = [
      'mode' => ['required', 'string', Rule::in(['contact', 'frontdesk'])],
      'client_name' => ['required', 'string', 'max:255'],
      'email' => ['nullable', 'email', 'max:255'],
      'secondary_email' => ['nullable', 'email', 'max:255'],
      'other_phone' => ['nullable', 'string', 'max:50'],
      'notes' => ['nullable', 'string', 'max:1000'],
      'vip_clients' => ['nullable', 'boolean'],
      'vip_notes' => ['nullable', 'string', 'max:1000'],
    ];

    if ($mode === 'frontdesk') {
      $rules = array_merge($rules, [
        'phone' => ['required', 'regex:/^\\d{10}$/'],
        'status' => ['required', 'string', Rule::in($frontdeskStatuses)],
        'source' => ['required', 'string', Rule::in($sources)],
      ]);
    } else {
      $rules = array_merge($rules, [
        'phone' => ['nullable', 'string', 'max:50'],
        'status' => ['nullable', 'string', Rule::in($frontdeskStatuses)],
        'source' => ['nullable', 'string', Rule::in($sources)],
      ]);
    }

    $data = $request->validate($rules);

    $clientChanged = false;
    $orderChanged = false;
    $noteCreated = false;

    DB::transaction(function () use ($data, $order, $request, $mode, &$clientChanged, &$orderChanged, &$noteCreated) {
      $client = $order->client;
      $previousClientName = $client?->name;

      if ($client) {
        $clientPayload = [
          'name' => $data['client_name'],
        ];

        if (array_key_exists('phone', $data) && $data['phone'] !== null) {
          $clientPayload['phone'] = $data['phone'];
        }

        if (array_key_exists('email', $data)) {
          $clientPayload['email'] = $data['email'];
        }

        if (array_key_exists('secondary_email', $data)) {
          $clientPayload['secondary_email'] = $data['secondary_email'];
        }

        if (array_key_exists('other_phone', $data)) {
          $clientPayload['other_phone'] = $data['other_phone'];
        }

        if (array_key_exists('source', $data) && $data['source']) {
          $clientPayload['source'] = $data['source'];
        }
        if (array_key_exists('vip_clients', $data)) {
          $clientPayload['vip_clients'] = (bool) $data['vip_clients'];
        }
        if (array_key_exists('vip_notes', $data)) {
          $clientPayload['vip_notes'] = $data['vip_notes'];
        }

        $client->fill($clientPayload);
        $clientChanged = $client->isDirty();
        if ($clientChanged) {
          $client->save();
        }
      }

      $statusChanged = false;
      $orderPayload = [];
      if ($mode === 'frontdesk') {
        $currentOrderName = trim((string) ($order->name ?? ''));
        $previousClientName = trim((string) ($previousClientName ?? ''));
        if ($currentOrderName !== '' && $previousClientName !== '' && strcasecmp($currentOrderName, $previousClientName) === 0) {
          $orderPayload['name'] = $data['client_name'];
        }
      }

      if (filled($data['notes'] ?? null)) {
        $order->notes()->create([
          'content' => $data['notes'],
          'type' => 'order_note',
          'user_id' => $request->user()?->id,
        ]);
        $noteCreated = true;
      }

      if (!empty($data['status'])) {
        $statusChanged = !empty($order->status)
          ? strcasecmp($order->status, $data['status']) !== 0
          : true;
        $orderPayload['status'] = $data['status'];
      }

      $order->fill($orderPayload);
      $orderChanged = $order->isDirty();
      if ($orderChanged) {
        $order->save();
      }

      if ($statusChanged) {
        $order->orderStatus()->create([
          'status' => $data['status'],
          'user_id' => $request->user()?->id,
          'notes' => "{$data['status']} updated via frontdesk edit by " . ($request->user()->name ?? 'System'),
        ]);
      }
    });

    $order->refresh()->load('tags:id,name,color,taggable_id,taggable_type', 'client.companyContact', 'user', 'owners', 'saleForm', 'attachments.user', 'orderStatus.user');
    if (($clientChanged || $noteCreated) && ! $orderChanged) {
      $this->createSnapshot($order);
    }

    return response()->json([
      'success' => true,
      'order' => $order,
    ]);
  }

  public function saleFormPdf(Request $request, Order $order)
  {
    $order->load(['client.companyContact', 'owners', 'saleForm']);

    if (!$order->saleForm) {
      abort(404, 'Sale form not found for this order.');
    }

    $pdf = Pdf::loadView('pdf.sale-form', [
      'order' => $order,
    ]);

    $filename = 'sale-form-' . ($order->order_number ?? $order->id) . '.pdf';

    if ($request->boolean('download')) {
      return $pdf->download($filename);
    }

    return $pdf->stream($filename, ['Attachment' => false]);
  }

  public function tagsUpdate(Request $request, Order $order)
  {
      $validated = $request->validate([
          'tags'         => ['array'],
          'tags.*.name'  => ['required', 'string', 'max:28'],
          'tags.*.color' => ['nullable', 'string', 'in:none,red,orange,amber,yellow,lime,green,teal,sky,blue,indigo,violet,purple,pink,gray'],
      ]);

      $uniqueTags = collect($validated['tags'] ?? [])
          ->map(fn($t) => [
              'name'  => trim($t['name']),
              'color' => $t['color'] ?? 'gray',
          ])
          ->filter(fn($t) => $t['name'] !== '')
          ->unique(fn($t) => mb_strtolower($t['name']))
          ->values()
          ->all();

      DB::transaction(function () use ($order, $uniqueTags) {
          $this->replaceTags($order, $uniqueTags, 'order');
      });
      $this->createSnapshot($order->fresh());

      return back()->with('success', 'Tags actualizados.');
  }

  protected function replaceTags($model, array $tags, string $type): void
  {
      $model->tags()
          ->when($type, fn($q) => $q->where('type', $type))
          ->delete();

      if (!empty($tags)) {
          $rows = array_map(fn($t) => [
              'name'    => $t['name'],
              'color'   => $t['color'] ?? 'gray',
              'type'    => $type,
              'user_id' => auth()->id(),
          ], $tags);

          $model->tags()->createMany($rows);
      }
  }

  protected function buildSaleFormPayload(Order $order, ?SaleForm $existing = null): array
  {
      return [
          'sale' => $existing?->sale ?? false,
          'installation' => $existing?->installation ?? false,
          'permit' => $existing?->permit ?? false,
          'replacement' => $existing?->replacement ?? false,
          'new_construction' => $existing?->new_construction ?? false,
          'financing' => $existing?->financing ?? false,
          'screen' => $existing?->screen ?? false,
          'design' => $existing?->design ?? false,
          'mountin' => $existing?->mountin ?? false,
          'bar' => $existing?->bar ?? false,
          'shutter_hole' => $existing?->shutter_hole ?? false,
          'floor_cutting' => $existing?->floor_cutting ?? false,
          'interior_finish' => $existing?->interior_finish ?? false,
          'floor' => $existing?->floor ?? '',
          'frame_color' => $existing?->frame_color ?? ($order->frame_color ?? ''),
          'glass_color' => $existing?->glass_color ?? ($order->glass_color ?? ''),
          'glass_type' => $existing?->glass_type ?? ($order->glass_type ?? ''),
          'glass_coating' => $existing?->glass_coating ?? ($order->glass_coating ?? ''),
          'door_quantity' => $existing?->door_quantity ?? 0,
          'window_quantity' => $existing?->window_quantity ?? 0,
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

  private function ownerCanAccessOrder(?User $user, Order $order): bool
  {
      if (!$this->isOwnerRestricted($user)) {
          return true;
      }

      return $order->owners()
          ->where('users.id', $user->id)
          ->exists();
  }
}

  
