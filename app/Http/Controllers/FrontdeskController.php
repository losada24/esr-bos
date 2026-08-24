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
use App\Enum\ProductLineEnum;
use App\Enum\RoleEnum;
use App\Events\OrderStatusChanged;
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
use App\Models\OrderCompanyContact;
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
use App\Support\PaymentInstallmentPresenter;
use App\Support\OrderBoardFilter;
use App\Support\OrderClientEmailManager;
use App\Support\OrderPipelineSort;
use App\Support\QualifiedOrderDuplicateChecker;
use App\Services\CrmNotificationService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FrontdeskController extends Controller
{   
    use OrderEmails, Snapshot;
    private const FRONTDESK_PAGE_SIZE = 20;

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
            'phone'       => optional($order->client)->phone,
            'vip_clients' => (bool) (optional($order->client)->vip_clients ?? false),
            'created_by'  => $order->user->name ?? null,
            'is_supply'   => (bool) ($order->is_supply ?? false),
            'owner_ids'   => $order->owners->pluck('id')->values(),
            'owners'      => $order->owners->map(fn ($owner) => [
                'id' => $owner->id,
                'name' => $owner->name,
            ])->values(),
            'order_type'  => $order->order_type,
            'product_line' => $order->product_line,
            'bid_due_date' => $this->resolveBidDueDate($order),
            'tags'        => ($order->tags ?? collect())->map(fn($t) => [
                'name'  => $t->name,
                'color' => $t->color,
            ])->values(),
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

    private function appendClientEmailData(Order $order): array
    {
        $orderData = $order->toArray();
        $selectedContact = $order->orderCompanyContacts
            ->firstWhere('is_selected', true)
            ?? ($order->orderCompanyContacts->count() === 1 ? $order->orderCompanyContacts->first() : null);

        $orderData['contact_email'] = $this->clientEmailManager()->resolveRecipient($order);
        $orderData['client_email_selection'] = $this->clientEmailManager()->selectionForOrder($order);
        $orderData['client_email_override'] = $order->client_email_override;
        $orderData['client_email_options'] = $this->clientEmailManager()->optionsForOrder($order, $selectedContact);
        $orderData['order_company_contacts'] = $order->orderCompanyContacts
            ->map(function ($item) use ($order) {
                $data = $item->toArray();
                $data['client_email_options'] = $this->clientEmailManager()->optionsForOrder($order, $item);

                return $data;
            })
            ->values()
            ->all();

        return $orderData;
    }

    public function index(Request $request)
    {
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
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
    ];

    $order_types = [
      OrderTypeEnum::RESIDENTIAL->value,
      OrderTypeEnum::COMMERCIAL->value,
      //OrderTypeEnum::SUPPLY->value,
    ];

    $data = collect($frontdeskStatuses)->map(function ($status) use ($paginatedStatuses, $filters, $filterRows, $filterMatch, $hasMultiFilters, $sort) {
        $ordersQuery = $this->frontdeskOrderQuery($status);
        $ordersQuery = $hasMultiFilters
            ? OrderBoardFilter::applyMultiple($ordersQuery, $filterRows, $filterMatch)
            : OrderBoardFilter::apply($ordersQuery, $filters);

        if (in_array($status, $paginatedStatuses, true)) {
            $total = (clone $ordersQuery)->count();
            OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
            $orders = $ordersQuery
                ->with(['client','user','orderStatus','owners','orderCompanyContacts.companyContact','tags:id,name,color,taggable_id,taggable_type'])
                ->limit(self::FRONTDESK_PAGE_SIZE)
                ->get();
        } else {
            OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
            $orders = $ordersQuery
                ->with(['client','user','orderStatus','owners','orderCompanyContacts.companyContact','tags:id,name,color,taggable_id,taggable_type'])
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
        ->whereNotNull('name')
        ->select('name')
        ->orderBy('name')
        ->get()
        ->map(fn ($tag) => trim((string) $tag->name))
        ->filter(fn ($name) => $name !== '')
        ->unique(fn ($name) => mb_strtolower($name))
        ->values()
        ->map(fn ($name) => ['name' => $name]);

      return Inertia::render('Frontdesk/Index', [
        'data' => $data,
        'lossReasonFrontdesk' => $lossReasonFrontdesk,
        'sources' => $sources,
        'order_types' => $order_types,
        'product_lines' => array_map(fn (ProductLineEnum $productLine) => $productLine->value, ProductLineEnum::cases()),
        'owners' => $owners,
        'supervisors' => $supervisors,
        'created_by_users' => $createdByUsers,
        'tags' => $tags,
        'statuses' => $frontdeskStatuses,
        'filters' => $filters,
        'sort' => $sort,
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
      $sort = OrderPipelineSort::resolveFromRequest($request);
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
      OrderPipelineSort::apply($ordersQuery, $sort['sort_by'], $sort['sort_dir']);
      $orders = $ordersQuery
        ->with(['client','user','orderStatus','owners','orderCompanyContacts.companyContact','tags:id,name,color,taggable_id,taggable_type'])
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

   public function createQualified()
  {
    return Inertia::render('Frontdesk/CreateQualified', [
      'clients' => Client::with(['companyContact:id,name,email', 'companyContacts:id,name,email'])
        ->select('id', 'name', 'phone', 'phone_ext', 'email', 'other_phone', 'secondary_email', 'source', 'vip_clients', 'vip_notes', 'company_contact_id')
        ->orderBy('name')
        ->get(),
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
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
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

    $validated = $request->validate([
      'status' => ['required', 'string'],
      'product_line' => [
        Rule::requiredIf(
          $order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value
          && in_array($request->input('status'), [
            OrderStatusEnum::FOLLOW_UP->value,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
            OrderStatusEnum::STAND_BY->value,
            OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value,
            OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
          ], true)
        ),
        'nullable',
        Rule::enum(ProductLineEnum::class),
      ],
      'note' => ['nullable', 'string', 'max:4000'],
      'invoice_number' => ['nullable', 'string', 'max:255'],
      'order_number' => [
        Rule::requiredIf($request->input('status') === OrderStatusEnum::CLOSED_WON->value),
        'nullable',
        'string',
        'max:255',
      ],
      'confirm_customer_role' => ['nullable', 'boolean'],
      'attachments' => ['nullable', 'array'],
      'attachments.*' => ['file', 'max:10240'],
    ]);

    $noteContent = trim((string) ($validated['note'] ?? ''));
    $invoiceNumber = trim((string) ($validated['invoice_number'] ?? ''));
    $orderNumber = trim((string) ($validated['order_number'] ?? ''));
    $status = $validated['status'];
    $finalStatus = $status === OrderStatusEnum::CLOSED_WON->value
      ? OrderStatusEnum::ACCOUNT_RECEIPT->value
      : $status;
    $historyStatuses = $status === OrderStatusEnum::CLOSED_WON->value
      ? [$status, $finalStatus]
      : [$status];
    $confirmCustomerRole = (bool) ($validated['confirm_customer_role'] ?? false);

    $this->ensureRequestRescheduleTransitionAllowed($order, $finalStatus);

    if ($finalStatus === OrderStatusEnum::REVIEW->value) {
      $order->loadMissing('client');
      $contactEmail = trim((string) $order->client?->email);

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
    }

    DB::transaction(function () use ($order, $request, $validated, $finalStatus, $historyStatuses, $noteContent, $invoiceNumber, $orderNumber, $status, $confirmCustomerRole) {
      $order->status = $finalStatus;
      $order->product_line = $validated['product_line'] ?? $order->product_line;
      if ($invoiceNumber !== '' && $status === OrderStatusEnum::REVIEW->value) {
        $order->invoice_number = $invoiceNumber;
      }
      if ($orderNumber !== '' && $status === OrderStatusEnum::CLOSED_WON->value) {
        $order->order_number = $orderNumber;
      }
      $order->save();

      foreach ($historyStatuses as $historyStatus) {
        $order->orderStatus()->create([
          'status' => $historyStatus,
          'user_id' => auth()->id(),
          'notes' => "{$historyStatus} created by " . auth()->user()->name,
        ]);
      }

      if ($finalStatus === OrderStatusEnum::REVIEW->value) {
        event(new OrderStatusChanged($order, $finalStatus, $confirmCustomerRole));
      }

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
    app(CrmNotificationService::class)->recordOrderFeed(
      $order->fresh(),
      $request->user(),
      'Order status updated',
      ($request->user()?->name ?? 'Someone') . ' moved order ' . ($order->name ?? ('#' . $order->id)) . ' to ' . $finalStatus
    );

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
      app(CrmNotificationService::class)->recordOrderFeed(
        $order,
        $user,
        'Order status updated',
        ($user?->name ?? 'Someone') . ' moved order ' . ($order->name ?? ('#' . $order->id)) . ' to ' . $status
      );
      

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
    app(CrmNotificationService::class)->recordOrderFeed(
      $order,
      $request->user(),
      'Order status updated',
      ($request->user()?->name ?? 'Someone') . ' moved order ' . ($order->name ?? ('#' . $order->id)) . ' to ' . $data['status']
    );

    return response()->json(['success' => true, 'order' => $order]);
}

public function showQuantifiedModal(Order $order)
{
    $order->load('client'); // Relación con Client
    $latestNote = $order->notes()->latest()->first();
    $order->setAttribute('latest_note', $latestNote?->content ?? '');

    return response()->json($order);
}
    
    public function updateStatusQuantified(Request $request, Order $order, QualifiedOrderDuplicateChecker $qualifiedOrderDuplicateChecker)
    {     
        //dd($request->all());
        $request->validate([
          'phone' => [
            'required',
            'regex:/^\d{10}$/',
            Rule::unique('clients', 'phone')->ignore($order->client_id),
          ],
          'notes' => ['nullable', 'string', 'max:2000'],
          'product_line' => ['nullable', 'string', Rule::enum(ProductLineEnum::class)],
          'force_duplicate' => ['nullable', 'boolean'],
          'language' => [
            'required',
            'string',
            Rule::in(array_map(
              static fn (LanguageEnum $language) => $language->value,
              LanguageEnum::cases()
            ))
          ],
        ]);

        $qualifiedOrderDuplicateChecker->ensureNoDuplicateUnlessForced(
          $request->input('name'),
          $order->client_id ? (int) $order->client_id : null,
          $request->boolean('force_duplicate'),
          (int) $order->id,
          $request->input('job_address'),
          $request->input('city'),
          $request->input('job_zip')
        );

        $status = $request['status'];
        if ($request['order_type'] === OrderTypeEnum::RESIDENTIAL->value || $request['order_type'] === OrderTypeEnum::SUPPLY->value) {
          $status = OrderStatusEnum::PENDING_ASSIGNMENT->value;
        } 
        if ($request['order_type'] === OrderTypeEnum::COMMERCIAL->value) {
          $status = OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value;
        }
        
        $scheduleAppointment = $request->input('schedule_appointment');
        $incomingProjectAmount = (float) ($request['project_amount'] ?? 0);
        $currentProjectAmount = (float) ($order->project_amount ?? 0);

        if ($request->user()?->hasRole(RoleEnum::OWNER_ADMIN->value) && abs($incomingProjectAmount - $currentProjectAmount) > 0.01) {
          throw ValidationException::withMessages([
            'project_amount' => 'Owner Admin cannot edit Project Amount.',
          ]);
        }

        $orderPayload = [
          'name' => $request['name'],
          'order_type' => $request['order_type'],
          'product_line' => $request['product_line'] ?? null,
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
            'phone_ext' => $request['phone_ext'] ?? null,
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
          app(CrmNotificationService::class)->recordOrderFeed(
            $order,
            $request->user(),
            'Order qualified',
            ($request->user()?->name ?? 'Someone') . ' qualified order ' . ($order->name ?? ('#' . $order->id)) . ' and moved it to ' . $status
          );

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
    $order->load(
      'tags:id,name,color,taggable_id,taggable_type',
      'client.companyContact',
      'client.companyContacts',
      'client.referral',
      'client.referral.referrerClient:id,name,phone,email',
      'client.referral.referrerUser:id,name,phone,email,status',
      'user',
      'owners',
      'saleForm',
      'attachments.user',
      'orderStatus.user',
      'paymentSchedule.installments.paidBy',
      'paymentSchedule.installments.movements.paidBy',
      'changeOrderPayment.paidBy',
      'cityFeePayment.paidBy',
      'financialEvents.user',
      'serviceControls.creator:id,name',
      'phases',
      'orderCompanyContacts.companyContact',
      'orderCompanyContacts.client.companyContacts',
      'orderCompanyContacts.source'
    );

    $clientOrders = collect();

    if ($order->client) {
      $clientOrdersQuery = $order->client->orders()
        ->where('id', '!=', $order->id)
        ->with(['owners:id,name', 'orderCompanyContacts.companyContact', 'orderCompanyContacts.client'])
        ->orderByDesc('created_at');

      if ($this->isOwnerRestricted(auth()->user())) {
        $clientOrdersQuery->accessibleToOwner(auth()->user());
      }

      $clientOrders = $clientOrdersQuery
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
          'order_company_contacts' => $clientOrder->orderCompanyContacts
            ->map(fn ($item) => [
              'id' => $item->id,
              'company_name' => $item->companyContact?->name,
              'client_name' => $item->client?->name,
              'is_selected' => (bool) ($item->is_selected ?? false),
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
      ->where('status', StatusUserEnum::ACTIVE->value)
      ->orderBy('name');

    if ($this->isOwnerRestricted(auth()->user())) {
      $ownerOptionsQuery->whereIn('id', auth()->user()->accessibleOwnerIds());
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
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
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

    $methodsOfPayment = array_values(array_filter(
      array_map(fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()),
      fn (string $method) => !in_array($method, [
        MethodOfPayment::CHECK->value,
        MethodOfPayment::ZELLE->value,
        MethodOfPayment::AIA->value,
      ], true)
    ));
    $typeOfFinancing = array_map(fn (TypeOfFinancing $financing) => $financing->value, TypeOfFinancing::cases());

    $clients = Client::with(['companyContact:id,name,email', 'companyContacts:id,name,email'])
      ->select('id', 'name', 'phone', 'phone_ext', 'email', 'other_phone', 'secondary_email', 'source', 'vip_clients', 'vip_notes', 'company_contact_id')
      ->orderBy('name')
      ->get();

    $companies = CompanyContact::select('id', 'name', 'email')->orderBy('name')->get();

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
            ContactSourceEnum::DIRECT_CALL->value,
            ContactSourceEnum::CANVASS->value,
            ContactSourceEnum::TRUCK_LED->value,
            ContactSourceEnum::COSTCO->value,
    ];

    $statusOptions = [
        OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
        OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
        OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
        OrderStatusEnum::QUALIFIED->value,
    ];

    $orderData = $this->appendClientEmailData($order);
    $orderData['payment_schedule'] = PaymentInstallmentPresenter::schedule($order->paymentSchedule);
    $orderData['financial_events'] = $order->financialEvents
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
      ->values();
    $orderData['has_contract_signed'] = $order->hasReachedContractSigned();
    $serviceControls = $order->serviceControls
      ->map(fn ($serviceControl) => [
        'id' => $serviceControl->id,
        'service_name' => $serviceControl->service_name,
        'service_id' => $serviceControl->service_id,
        'service_type' => is_array($serviceControl->service_type)
          ? array_values(array_filter($serviceControl->service_type))
          : (filled($serviceControl->service_type) ? [$serviceControl->service_type] : []),
        'service_status' => $serviceControl->service_status,
        'priority' => $serviceControl->priority,
        'open_days' => $serviceControl->open_days,
        'updated_at' => optional($serviceControl->updated_at)->toISOString(),
        'creator' => $serviceControl->creator ? [
          'id' => $serviceControl->creator->id,
          'name' => $serviceControl->creator->name,
        ] : null,
      ])
      ->values();

    // Obtener los parámetros de filtro de la solicitud (request)
    return Inertia::render('Frontdesk/OrderView', [
      //'orderStatuses' => $orderStatuses,
      'order' => $orderData,
      'serviceControls' => $serviceControls,
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
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json(['message' => 'You are not authorized to update this order.'], 403);
    }

    $updatedOrder = $updateQualifiedOrder->handle($request, $order);
    app(CrmNotificationService::class)->recordOrderFeed(
      $updatedOrder,
      $request->user(),
      'Order updated',
      ($request->user()?->name ?? 'Someone') . ' updated order ' . ($updatedOrder->name ?? ('#' . $updatedOrder->id))
    );
    $orderData = $this->appendClientEmailData($updatedOrder);
    $orderData['payment_schedule'] = PaymentInstallmentPresenter::schedule($updatedOrder->paymentSchedule);
    $orderData['has_contract_signed'] = $updatedOrder->hasReachedContractSigned();

    return response()->json([
      'success' => true,
      'order' => $orderData,
    ]);
  }

  public function updateOrderContact(Request $request, Order $order)
  {
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      return response()->json(['message' => 'You are not authorized to update this order.'], 403);
    }

    $mode = $request->input('mode');
    $frontdeskStatuses = [
      OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
      OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
      OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
      OrderStatusEnum::QUALIFIED->value,
    ];
    if (filled($order->status) && !in_array($order->status, $frontdeskStatuses, true)) {
      $frontdeskStatuses[] = $order->status;
    }
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

    $rules = [
      'mode' => ['required', 'string', Rule::in(['contact', 'frontdesk'])],
      'client_name' => ['required', 'string', 'max:255'],
      'email' => ['nullable', 'email', 'max:255'],
      'secondary_email' => ['nullable', 'email', 'max:255'],
      'phone_ext' => ['nullable', 'string', 'max:20'],
      'other_phone' => ['nullable', 'string', 'max:50'],
      'notes' => ['nullable', 'string', 'max:1000'],
      'vip_clients' => ['nullable', 'boolean'],
      'vip_notes' => ['nullable', 'string', 'max:1000'],
      'product_line' => ['nullable', 'string', Rule::enum(ProductLineEnum::class)],
    ];

    $targetClientId = (int) ($request->input('client_id') ?? $order->client_id ?? 0);
    if ($mode === 'frontdesk') {
      $phoneRules = $order->order_type === OrderTypeEnum::COMMERCIAL->value
        ? [
            'nullable',
            'regex:/^\\d{10}$/',
            Rule::unique('clients', 'phone')->ignore($targetClientId),
          ]
        : [
            'required',
            'regex:/^\\d{10}$/',
            Rule::unique('clients', 'phone')->ignore($targetClientId),
          ];

      $rules = array_merge($rules, [
        'phone' => $phoneRules,
        'status' => ['required', 'string', Rule::in($frontdeskStatuses)],
        'source' => ['required', 'string', Rule::in($sources)],
        'commercial_pairs' => ['nullable', 'array', 'max:5'],
        'commercial_pairs.*.company_id' => ['required', 'integer', 'distinct', 'exists:company_contacts,id'],
        'commercial_pairs.*.client_id' => ['required', 'integer', 'exists:clients,id'],
        'commercial_pairs.*.source_id' => ['required', 'integer', 'exists:sources,id'],
      ]);
    } else {
      $rules = array_merge($rules, [
        'phone' => [
          'nullable',
          'string',
          'max:50',
          Rule::unique('clients', 'phone')->ignore($targetClientId),
        ],
        'status' => ['nullable', 'string', Rule::in($frontdeskStatuses)],
        'source' => ['nullable', 'string', Rule::in($sources)],
      ]);
    }

    $rules['client_id'] = ['nullable', 'integer', 'exists:clients,id'];
    $data = $request->validate($rules);

    $clientChanged = false;
    $orderChanged = false;
    $noteCreated = false;
    $statusChanged = false;

    DB::transaction(function () use ($data, $order, $request, $mode, &$clientChanged, &$orderChanged, &$noteCreated, &$statusChanged) {
      $targetClientId = isset($data['client_id']) ? (int) $data['client_id'] : (int) ($order->client_id ?? 0);
      $client = $targetClientId ? Client::find($targetClientId) : null;
      if ($targetClientId) {
        $belongsToOrder = $order->client_id === $targetClientId
          || $order->orderCompanyContacts()->where('client_id', $targetClientId)->exists();
        if (! $belongsToOrder) {
          abort(403, 'You are not authorized to edit this contact.');
        }
      }
      $previousClientName = $client?->name;

      if ($client) {
        $clientPayload = [
          'name' => $data['client_name'],
        ];

        if (array_key_exists('phone', $data) && $data['phone'] !== null) {
          $clientPayload['phone'] = $data['phone'];
        }

        if (array_key_exists('phone_ext', $data)) {
          $clientPayload['phone_ext'] = $data['phone_ext'];
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

      $orderPayload = [];
      if ($mode === 'frontdesk') {
        $currentOrderName = trim((string) ($order->name ?? ''));
        $previousClientName = trim((string) ($previousClientName ?? ''));
        if ($currentOrderName !== '' && $previousClientName !== '' && strcasecmp($currentOrderName, $previousClientName) === 0) {
          $orderPayload['name'] = $data['client_name'];
        }
        $orderPayload['product_line'] = $data['product_line'] ?? null;

        if ($order->order_type === OrderTypeEnum::COMMERCIAL->value && array_key_exists('commercial_pairs', $data)) {
          $commercialPairs = collect($data['commercial_pairs'] ?? []);
          $selectedClientId = $order->client_id ? (int) $order->client_id : null;
          $hasSingleCompany = $commercialPairs->count() === 1;

          OrderCompanyContact::withTrashed()
            ->where('order_id', $order->id)
            ->forceDelete();

          foreach ($commercialPairs as $pair) {
            $clientId = (int) $pair['client_id'];
            $companyId = (int) $pair['company_id'];
            $isSelected = $selectedClientId
              ? $clientId === $selectedClientId
              : $hasSingleCompany;

            app(\App\Support\ClientCompanyContactManager::class)->attach(
              Client::findOrFail($clientId),
              $companyId
            );

            OrderCompanyContact::create([
              'order_id' => $order->id,
              'company_contact_id' => $companyId,
              'client_id' => $clientId,
              'source_id' => (int) $pair['source_id'],
              'is_selected' => $isSelected,
              'selected_at' => $isSelected ? now() : null,
            ]);
          }
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

    $order->refresh()->load(
      'tags:id,name,color,taggable_id,taggable_type',
      'client.companyContact',
      'user',
      'owners',
      'saleForm',
      'attachments.user',
      'orderStatus.user',
      'orderCompanyContacts.companyContact',
      'orderCompanyContacts.client',
      'orderCompanyContacts.source'
    );
    if (($clientChanged || $noteCreated) && ! $orderChanged) {
      $this->createSnapshot($order);
    }
    $order->setAttribute('has_contract_signed', $order->hasReachedContractSigned());
    if ($clientChanged) {
      app(CrmNotificationService::class)->recordOrderFeed(
        $order,
        $request->user(),
        'Order contact updated',
        ($request->user()?->name ?? 'Someone') . ' updated the contact on order ' . ($order->name ?? ('#' . $order->id))
      );
    }
    if ($statusChanged) {
      app(CrmNotificationService::class)->recordOrderFeed(
        $order,
        $request->user(),
        'Order status updated',
        ($request->user()?->name ?? 'Someone') . ' moved order ' . ($order->name ?? ('#' . $order->id)) . ' to ' . $data['status']
      );
    }
    if ($noteCreated) {
      app(CrmNotificationService::class)->recordOrderFeed(
        $order,
        $request->user(),
        'Order note added',
        ($request->user()?->name ?? 'Someone') . ' added a note to order ' . ($order->name ?? ('#' . $order->id))
      );
    }

    return response()->json([
      'success' => true,
      'order' => $order,
    ]);
  }

  public function saleFormPdf(Request $request, Order $order)
  {
    if (!$this->ownerCanAccessOrder($request->user(), $order)) {
      abort(403, 'You are not authorized to access this order.');
    }

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
}

  
