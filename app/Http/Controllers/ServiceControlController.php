<?php

namespace App\Http\Controllers;

use App\Enum\AreaEnum;
use App\Enum\BmInvoiceStatusEnum;
use App\Enum\ContactSourceEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceControlClosureResultEnum;
use App\Enum\ServiceControlCreationSourceEnum;
use App\Enum\ServiceControlPriorityEnum;
use App\Enum\ServiceControlRequestOriginEnum;
use App\Enum\ServiceControlSourceEnum;
use App\Enum\ServiceControlStatusEnum;
use App\Enum\ServiceControlTypeEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusUserEnum;
use App\Http\Requests\StoreServiceControlRequest;
use App\Http\Requests\UpdateServiceControlRequest;
use App\Models\Client;
use App\Models\Attachment;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use App\Models\ServiceControl;
use App\Models\ServiceControlHistory;
use App\Models\Source;
use App\Models\User;
use App\Exports\ServiceControlExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ServiceControlController extends Controller
{
    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    private function existingRoleNames(array $roles): array
    {
        return DB::table('roles')
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();
    }

    public function index(Request $request): Response
    {
        $data = $this->buildIndexData($request, 50);

        return Inertia::render('ServiceControl/Index', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->buildIndexData($request);
        $pdf = Pdf::loadView('pdf.service-control', $data)->setPaper('A2', 'landscape');

        return $pdf->stream('service-control-report.pdf');
    }

    public function excel(Request $request)
    {
        return Excel::download(
            new ServiceControlExport($this->buildIndexData($request)),
            'Service Control Report.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function calendar(): Response
    {
        return Inertia::render('ServiceControl/Calendar', [
            'legend' => [
                [
                    'label' => 'Services',
                    'status' => ServiceControlRequestOriginEnum::SERVICE->value,
                    'color' => $this->serviceCalendarOriginColor(ServiceControlRequestOriginEnum::SERVICE->value),
                ],
                [
                    'label' => 'Quotes',
                    'status' => ServiceControlRequestOriginEnum::OWNER->value,
                    'color' => $this->serviceCalendarOriginColor(ServiceControlRequestOriginEnum::OWNER->value),
                ],
                [
                    'label' => 'Scheduled Date',
                    'status' => 'scheduled_date',
                    'color' => $this->serviceCalendarScheduledColor(),
                ],
            ],
            'statusOptions' => collect($this->serviceCalendarStatuses())
                ->map(fn (string $status) => [
                    'label' => $this->humanizeStatus($status),
                    'value' => $status,
                ])
                ->values(),
        ]);
    }

    public function calendarEvents(Request $request, int $year, int $month): JsonResponse
    {
        $statusFilter = trim((string) $request->query('status', 'all'));
        $search = trim((string) $request->query('search', ''));
        $allowedStatuses = $this->serviceCalendarStatuses();
        $statuses = in_array($statusFilter, $allowedStatuses, true) ? [$statusFilter] : $allowedStatuses;
        $calendarTimezone = (string) config('app.timezone', 'UTC');
        $rangeStart = Carbon::createFromDate($year, $month, 1, $calendarTimezone)
            ->startOfMonth()
            ->subWeek()
            ->startOfDay();
        $rangeEnd = Carbon::createFromDate($year, $month, 1, $calendarTimezone)
            ->endOfMonth()
            ->addWeek()
            ->endOfDay();

        $serviceControls = ServiceControl::query()
            ->with([
                'order.client',
                'order.supervisor:id,name',
                'order.owners:id,name',
                'client',
                'histories' => fn ($query) => $query->orderByDesc('created_at'),
            ])
            ->where('is_bm', false)
            ->where(function (Builder $query) {
                $query
                    ->whereNotNull('scheduled_date')
                    ->orWhereNotNull('parts_received_date');
            })
            ->whereIn('service_status', $statuses)
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function (Builder $builder) use ($like) {
                    $builder
                        ->where('service_name', 'like', $like)
                        ->orWhere('service_id', 'like', $like)
                        ->orWhere('external_order_id', 'like', $like)
                        ->orWhereHas('order', function (Builder $orderQuery) use ($like) {
                            $orderQuery
                                ->where('name', 'like', $like)
                                ->orWhere('order_number', 'like', $like)
                                ->orWhereHas('client', function (Builder $clientQuery) use ($like) {
                                    $clientQuery
                                        ->where('name', 'like', $like)
                                        ->orWhere('phone', 'like', $like)
                                        ->orWhere('other_phone', 'like', $like);
                                })
                                ->orWhereHas('supervisor', function (Builder $supervisorQuery) use ($like) {
                                    $supervisorQuery->where('name', 'like', $like);
                                });
                        })
                        ->orWhereHas('client', function (Builder $clientQuery) use ($like) {
                            $clientQuery
                                ->where('name', 'like', $like)
                                ->orWhere('phone', 'like', $like)
                                ->orWhere('other_phone', 'like', $like);
                        });
                });
            })
            ->get();

        $events = $serviceControls
            ->map(function (ServiceControl $serviceControl) use ($calendarTimezone) {
                $status = (string) $serviceControl->service_status;
                $requestOrigin = $serviceControl->request_origin ?? ServiceControlRequestOriginEnum::SERVICE->value;
                $eventDate = $this->serviceCalendarDate($serviceControl, $calendarTimezone);

                if (! $eventDate) {
                    return null;
                }

                $eventStart = $eventDate->copy()->startOfDay();
                $eventEnd = $eventStart->copy()->addHour();
                $order = $serviceControl->order;
                $client = $this->serviceClient($serviceControl);
                $owners = $order?->owners?->pluck('name')->filter()->implode(', ') ?? '';

                return [
                    'id' => $serviceControl->id,
                    'service_control_id' => $serviceControl->id,
                    'order_id' => $order?->id,
                    'title' => $serviceControl->service_name ?: ($order?->name ?? 'Service'),
                    'start' => $eventStart->format(\DateTimeInterface::ATOM),
                    'end' => $eventEnd->format(\DateTimeInterface::ATOM),
                    'allDay' => true,
                    'color' => $this->serviceCalendarEventColor($serviceControl, $status, $requestOrigin, $calendarTimezone),
                    'request_origin' => $requestOrigin,
                    'type_label' => $requestOrigin === ServiceControlRequestOriginEnum::OWNER->value ? 'Quote' : 'Service',
                    'status' => $status,
                    'status_label' => $this->humanizeStatus($status),
                    'service_name' => $serviceControl->service_name,
                    'service_id' => $serviceControl->service_id,
                    'assignee_name' => $this->resolvePartyName(
                        $serviceControl->assignee_type,
                        $serviceControl->assignee_id,
                        $order,
                        $serviceControl->client
                    ),
                    'order_name' => $order?->name ?? 'Standalone Service',
                    'order_number' => $this->serviceControlOrderNumber($serviceControl),
                    'client_name' => $client?->name ?? '',
                    'client_phone' => $client?->phone ?? '',
                    'owner_names' => $owners,
                    'supervisor_name' => $order?->supervisor?->name ?? '',
                    'event_date' => $eventStart->format('M d, Y'),
                    'production_output_date' => $this->formatDate($serviceControl->parts_received_date),
                    'urgency_status' => $this->productionOutputUrgencyStatus($serviceControl, $calendarTimezone),
                    'production_output_overdue_days' => $serviceControl->production_output_overdue_days,
                    'production_output_overdue_resolved_at' => optional($serviceControl->production_output_overdue_resolved_at)->toISOString(),
                    'scheduled_date' => $this->formatDate($serviceControl->scheduled_date),
                    'service_created_date' => $this->formatDate($serviceControl->service_created_date),
                    'open_days' => $serviceControl->open_days,
                    'description' => $serviceControl->description,
                    'tooltip' => implode(' | ', array_filter([
                        $serviceControl->service_name,
                        $requestOrigin === ServiceControlRequestOriginEnum::OWNER->value ? 'Quote' : 'Service',
                        'Status: ' . $this->humanizeStatus($status),
                        $client?->name ? 'Client: ' . $client->name : null,
                    ])),
                ];
            })
            ->filter(fn (?array $event) => $event !== null)
            ->filter(function (array $event) use ($rangeStart, $rangeEnd, $calendarTimezone) {
                $eventDate = Carbon::parse((string) $event['start'], $calendarTimezone);

                return $eventDate->betweenIncluded($rangeStart, $rangeEnd);
            })
            ->values();

        return response()->json($events);
    }

    public function searchClients(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if ($search === '') {
            return response()->json(['results' => []]);
        }

        $like = '%' . $search . '%';
        $clients = Client::query()
            ->select('id', 'name', 'phone', 'email', 'other_phone', 'secondary_email')
            ->where(function (Builder $query) use ($like) {
                $query
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('other_phone', 'like', $like)
                    ->orWhere('secondary_email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Client $client) => $this->serializeClientOption($client))
            ->values();

        return response()->json(['results' => $clients]);
    }

    public function searchExternalServiceOrders(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        if ($search === '') {
            return response()->json(['results' => []]);
        }

        return response()->json([
            'results' => $this->externalServiceOrders($search),
        ]);
    }

    private function buildIndexData(Request $request, ?int $limit = null): array
    {
        $query = ServiceControl::query()
            ->with([
                'order.client.companyContact',
                'order.client.companyContacts',
                'order.orderCompanyContacts.companyContact',
                'order.orderCompanyContacts.client',
                'order.parentOrder:id,order_number,name',
                'order.user:id,name',
                'order.owners:id,name',
                'order.supervisor:id,name',
                'client',
                'creator:id,name',
                'updater:id,name',
            ]);

        if ($this->isOwnerRestricted($request->user())) {
            $query->where(function (Builder $builder) use ($request) {
                $builder
                    ->whereHas('order', fn (Builder $orderQuery) => $orderQuery->accessibleToOwner($request->user()))
                    ->orWhere('created_by', $request->user()?->id);
            });
        }

        $type = $request->query('type') === 'quotes' ? 'quotes' : 'services';
        $query->where('is_bm', false);

        if ($type === 'quotes') {
            $query->where(function (Builder $builder) {
                $builder
                    ->where('request_origin', ServiceControlRequestOriginEnum::OWNER->value)
                    ->orWhereHas('order', function (Builder $orderQuery) {
                        $orderQuery
                            ->where('service_origin', 'OWNER')
                            ->orWhere('esr_service', true);
                    });
            });
        } else {
            $query->where(function (Builder $builder) {
                $builder
                    ->where('request_origin', ServiceControlRequestOriginEnum::SERVICE->value)
                    ->orWhereHas('order', function (Builder $orderQuery) {
                        $orderQuery
                            ->where('service_origin', 'SERVICE')
                            ->orWhere('is_post_sale_service', true);
                    });
            });
        }

        $text = trim((string) $request->query('search', ''));
        if ($text !== '') {
            $like = '%' . $text . '%';
            $query->where(function (Builder $builder) use ($like) {
                $builder
                    ->where('service_name', 'like', $like)
                    ->orWhere('service_id', 'like', $like)
                    ->orWhere('external_order_id', 'like', $like)
                    ->orWhere('bm_invoice_number', 'like', $like)
                    ->orWhere('bm_picked_up_by', 'like', $like)
                    ->orWhereRaw('JSON_SEARCH(service_type, "one", ?) IS NOT NULL', [$like])
                    ->orWhere('service_status', 'like', $like)
                    ->orWhere('priority', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('order', function (Builder $orderQuery) use ($like) {
                        $orderQuery
                            ->where('name', 'like', $like)
                            ->orWhere('order_number', 'like', $like)
                            ->orWhere('job_address', 'like', $like)
                            ->orWhereHas('client', function (Builder $clientQuery) use ($like) {
                                $clientQuery
                                    ->where('name', 'like', $like)
                                    ->orWhere('phone', 'like', $like)
                                    ->orWhere('email', 'like', $like);
                            });
                    })
                    ->orWhereHas('client', function (Builder $clientQuery) use ($like) {
                        $clientQuery
                            ->where('name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('other_phone', 'like', $like)
                            ->orWhere('secondary_email', 'like', $like);
                    });
            });
        }

        $status = trim((string) $request->query('status', ''));
        if ($type === 'services' && $status !== '') {
            $query->where('service_status', $status);
        }

        $priority = trim((string) $request->query('priority', ''));
        if ($type === 'services' && $priority !== '') {
            $query->where('priority', $priority);
        }

        $serviceType = trim((string) $request->query('service_type', ''));
        if ($serviceType !== '') {
            $query->whereJsonContains('service_type', $serviceType);
        }

        $query->latest();

        if ($limit !== null) {
            $query->limit($limit);
        }

        $serviceControls = $query
            ->get()
            ->map(fn (ServiceControl $serviceControl) => $this->serializeServiceControl($serviceControl))
            ->values();

        return [
            'serviceControls' => $serviceControls,
            'filters' => [
                'search' => $text,
                'status' => $status,
                'priority' => $priority,
                'service_type' => $serviceType,
                'type' => $type,
            ],
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => $this->serviceStatusOptions(),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
            'bmInvoiceStatusOptions' => array_column(BmInvoiceStatusEnum::cases(), 'value'),
        ];
    }

    private function externalServiceOrders(string $search): array
    {
        $baseUrl = rtrim((string) config('services.esr_orders.base_url'), '/');
        $token = (string) config('services.esr_orders.token');

        if ($token === '') {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->get($baseUrl . '/crm/orders', [
                    'search' => $search,
                ]);
        } catch (\Throwable) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        return collect($response->json('data', []))
            ->filter(fn ($order) => strtolower((string) data_get($order, 'order_type', '')) === 'service')
            ->map(function ($order) {
                $companySummary = [
                    'name' => data_get($order, 'company.name'),
                    'email' => data_get($order, 'company.email'),
                    'phone' => data_get($order, 'company.phone'),
                ];
                $matchedCompany = $this->resolveExternalCompanyFromSummary($companySummary);

                return [
                    'order_id' => data_get($order, 'order_id'),
                    'order_number' => data_get($order, 'order_number'),
                    'name' => data_get($order, 'name'),
                    'amount' => data_get($order, 'amount'),
                    'company' => [
                        ...$companySummary,
                        'bos_id' => $matchedCompany?->id,
                        'exists_in_bos' => $matchedCompany !== null,
                    ],
                    'client' => [
                        'name' => data_get($order, 'client.name') ?: data_get($order, 'customer.name'),
                        'email' => data_get($order, 'client.email') ?: data_get($order, 'customer.email'),
                        'phone' => data_get($order, 'client.phone') ?: data_get($order, 'customer.phone'),
                    ],
                    'account_manager' => [
                        'name' => data_get($order, 'account_manager.name'),
                        'email' => data_get($order, 'account_manager.email'),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private function resolveExternalCompanyFromSummary(array $company): ?CompanyContact
    {
        $email = strtolower(trim((string) ($company['email'] ?? '')));
        $phone = $this->normalizePhone($company['phone'] ?? null);
        $name = trim((string) ($company['name'] ?? ''));

        $query = CompanyContact::query()
            ->select('id', 'name', 'email', 'phone');

        if ($email !== '') {
            $matched = (clone $query)->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($matched) {
                return $matched;
            }
        }

        if ($phone !== '') {
            $matched = (clone $query)
                ->whereNotNull('phone')
                ->get()
                ->first(fn (CompanyContact $item) => $this->normalizePhone($item->phone) === $phone);

            if ($matched) {
                return $matched;
            }
        }

        if ($name !== '') {
            return (clone $query)->where('name', $name)->first();
        }

        return null;
    }

    public function create(Request $request): RedirectResponse|Response
    {
        $orderId = (int) $request->query('order_id', 0);
        $order = $orderId > 0 ? $this->loadOrderForServiceControl($orderId) : null;
        $externalOrder = $order ? [] : $this->externalOrderSummaryFromRequest($request);
        $externalContext = $externalOrder === []
            ? ['client' => null, 'company' => null, 'owner' => null]
            : $this->resolveExternalServiceOrderContext($request, $externalOrder);

        if ($order) {
            $this->ensureCanAccessOrder($request->user(), $order);
        }

        if (
            ! $order
            && $externalOrder !== []
            && ! $externalContext['company']
            && ! empty($externalOrder['company_name'])
        ) {
            return redirect()->route('service-control.index', ['type' => 'services'])
                ->with('error', 'The company from ESR does not exist in BOS. Create the company before continuing.');
        }

        return Inertia::render('ServiceControl/Create', [
            'order' => $order ? $this->serializeOrderForServiceControl($order) : $this->standaloneOrderSummary($externalContext['client'], $externalOrder, $externalContext),
            'externalDefaults' => $order ? [] : $this->externalServiceDefaults($externalOrder, $externalContext),
            'pageTitle' => $request->routeIs('esr-process.create-service') ? 'New Service' : 'Create Service Control',
            'submitRouteName' => $request->routeIs('esr-process.create-service') ? 'esr-process.store-service' : 'service-control.store',
            'defaultServiceSource' => $request->query('service_source') === ServiceControlSourceEnum::ESW->value
                ? ServiceControlSourceEnum::ESW->value
                : ServiceControlSourceEnum::ESR->value,
            'defaultType' => $request->query('type') === 'bm' ? 'bm' : 'services',
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => $this->serviceStatusOptions(),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
            'bmInvoiceStatusOptions' => array_column(BmInvoiceStatusEnum::cases(), 'value'),
            'requesterOptions' => $this->buildRequesterOptions($order),
            'assigneeOptions' => $this->buildAssigneeOptions($order),
        ]);
    }

    public function show(Request $request, ServiceControl $serviceControl): Response
    {
        $serviceControl->load([
                'order.client.companyContact',
                'order.client.companyContacts',
                'order.orderCompanyContacts.companyContact',
                'order.orderCompanyContacts.client',
                'order.parentOrder:id,order_number,name',
                'order.user:id,name',
                'order.owners:id,name',
                'order.supervisor:id,name',
            'client',
            'creator:id,name',
            'updater:id,name',
            'histories.user:id,name',
        ]);

        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        return Inertia::render('ServiceControl/Show', [
            'serviceControl' => $this->serializeServiceControl($serviceControl, true),
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => $this->serviceStatusOptions(),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
            'bmInvoiceStatusOptions' => array_column(BmInvoiceStatusEnum::cases(), 'value'),
            'requesterOptions' => $this->buildRequesterOptions($serviceControl->order, $serviceControl->client),
            'assigneeOptions' => $this->buildAssigneeOptions($serviceControl->order, $serviceControl->client),
        ]);
    }

    public function edit(Request $request, ServiceControl $serviceControl): Response
    {
        $serviceControl->load([
            'order.client.companyContact',
            'order.client.companyContacts',
            'order.orderCompanyContacts.companyContact',
            'order.orderCompanyContacts.client',
            'order.user:id,name',
            'order.owners:id,name',
            'order.supervisor:id,name',
            'client',
            'creator:id,name',
            'updater:id,name',
            'histories.user:id,name',
        ]);

        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        return Inertia::render('ServiceControl/Edit', [
            'serviceControl' => $this->serializeServiceControl($serviceControl, true),
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => $this->serviceStatusOptions(),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
            'bmInvoiceStatusOptions' => array_column(BmInvoiceStatusEnum::cases(), 'value'),
            'requesterOptions' => $this->buildRequesterOptions($serviceControl->order, $serviceControl->client),
            'assigneeOptions' => $this->buildAssigneeOptions($serviceControl->order, $serviceControl->client),
        ]);
    }

    public function store(StoreServiceControlRequest $request): RedirectResponse
    {
        $orderId = (int) $request->input('order_id', 0);
        $parentOrder = null;

        if ($orderId > 0) {
            $parentOrder = $this->loadOrderForServiceControl($orderId);
            $this->ensureCanAccessOrder($request->user(), $parentOrder);
        }

        $serviceControl = DB::transaction(function () use ($request, $parentOrder) {
            if (
                ! $request->boolean('is_bm')
                && $request->input('request_origin') === ServiceControlRequestOriginEnum::SERVICE->value
            ) {
                $order = $this->createPostSaleServiceOrder($request, $parentOrder);
                $request->merge([
                    'order_id' => $order->id,
                    'client_id' => null,
                ]);
            }

            $serviceControl = ServiceControl::create($this->buildPayload($request, null));
            $this->storeAttachments($request, $serviceControl);

            $serviceControl->histories()->create([
                'user_id' => $request->user()?->id,
                'event_type' => 'CREATED',
                'summary' => 'Service control created.',
                'new_values' => $this->trackedValues($serviceControl),
            ]);
            $this->syncServiceManCompletedOrderStatus($serviceControl, $request);

            return $serviceControl;
        });

        if ($request->routeIs('esr-process.store-service')) {
            return redirect()->route('esr-process.index')
                ->with('success', 'Service created successfully.');
        }

        return redirect()->route('service-control.edit', $serviceControl)
            ->with('success', 'Service control created successfully.');
    }

    private function createPostSaleServiceOrder(StoreServiceControlRequest $request, ?Order $parentOrder = null): Order
    {
        $parentOrder?->loadMissing(['owners:id', 'orderCompanyContacts']);
        $clientId = $parentOrder?->client_id ?? $this->resolveStandaloneClientId($request, null);
        $serviceName = trim((string) $request->input('service_name'));
        $serviceId = trim((string) $request->input('service_id'));

        $order = Order::create([
            'client_id' => $clientId,
            'parent_order_id' => $parentOrder?->id,
            'root_order_id' => $parentOrder ? ($parentOrder->root_order_id ?: $parentOrder->id) : null,
            'user_id' => $request->user()?->id,
            'order_type' => $parentOrder?->order_type ?? OrderTypeEnum::COMMERCIAL->value,
            'product_line' => $parentOrder?->product_line,
            'service' => ServiceEnum::SERVICE->value,
            'status' => OrderStatusEnum::SERVICE_IN_REVIEW->value,
            'name' => $serviceName !== '' ? $serviceName : ($parentOrder?->name ?? 'Post Sale Service'),
            'order_number' => $serviceId !== '' ? $serviceId : null,
            'project_amount' => 0,
            'notes' => $request->input('description'),
            'service_origin' => 'SERVICE',
            'is_post_sale_service' => true,
            'esr_service' => false,
        ]);

        $order->orderStatus()->create([
            'status' => OrderStatusEnum::SERVICE_IN_REVIEW->value,
            'user_id' => $request->user()?->id,
            'notes' => 'Post sale service order created from Service Control.',
        ]);

        if ($parentOrder) {
            $order->owners()->sync($parentOrder->owners->pluck('id')->all());
            $parentOrder->orderCompanyContacts->each(function (OrderCompanyContact $contact) use ($order, $clientId) {
                OrderCompanyContact::create([
                    'order_id' => $order->id,
                    'company_contact_id' => $contact->company_contact_id,
                    'client_id' => $contact->client_id ?: $clientId,
                    'source_id' => $contact->source_id,
                    'is_selected' => $contact->is_selected,
                    'selected_at' => $contact->selected_at,
                ]);
            });
        } else {
            $this->syncExternalOwner($order, $request);
            $this->attachExternalCompany($order, $request);
        }

        return $order;
    }

    private function syncExternalOwner(Order $order, StoreServiceControlRequest $request): void
    {
        $ownerId = (int) $request->input('external_owner_id', 0);
        if ($ownerId > 0) {
            $ownerExists = User::assignableOrderOwner()
                ->where('status', StatusUserEnum::ACTIVE->value)
                ->whereKey($ownerId)
                ->exists();

            if ($ownerExists) {
                $order->owners()->syncWithoutDetaching([$ownerId]);
                return;
            }
        }

        $ownerEmail = strtolower(trim((string) $request->input('external_owner_email', '')));
        $ownerName = trim((string) $request->input('external_owner_name', ''));

        if ($ownerEmail === '' && $ownerName === '') {
            return;
        }

        $owner = User::assignableOrderOwner()
            ->select('id', 'name', 'email')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->when(
                $ownerEmail !== '',
                fn (Builder $query) => $query->whereRaw('LOWER(email) = ?', [$ownerEmail]),
                fn (Builder $query) => $query->where('name', $ownerName)
            )
            ->first();

        if (! $owner) {
            return;
        }

        $order->owners()->syncWithoutDetaching([$owner->id]);
    }

    private function attachExternalCompany(Order $order, StoreServiceControlRequest $request): void
    {
        $companyId = (int) $request->input('external_company_contact_id', 0);

        if ($companyId <= 0 || ! $order->client_id) {
            return;
        }

        $company = CompanyContact::visibleTo($request->user())
            ->whereKey($companyId)
            ->first();

        if (! $company) {
            return;
        }

        $sourceId = Source::firstOrCreate([
            'name' => ContactSourceEnum::ESR_REFER->value,
        ])->id;

        OrderCompanyContact::updateOrCreate(
            [
                'order_id' => $order->id,
                'company_contact_id' => $company->id,
            ],
            [
                'client_id' => $order->client_id,
                'source_id' => $sourceId,
                'is_selected' => true,
                'selected_at' => now(),
            ]
        );
    }

    public function update(UpdateServiceControlRequest $request, ServiceControl $serviceControl): RedirectResponse
    {
        $serviceControl->load('order');
        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        $before = $this->trackedValues($serviceControl);

        DB::transaction(function () use ($request, $serviceControl, $before) {
            $payload = $this->buildPayload($request, $serviceControl);
            $serviceControl->fill($payload);
            $this->resolveProductionOutputOverdueIfNeeded($serviceControl, $before);

            $dirty = collect($serviceControl->getDirty())
                ->only(array_keys($before))
                ->all();

            $serviceControl->save();
            $this->storeAttachments($request, $serviceControl);
            $this->syncServiceManCompletedOrderStatus($serviceControl, $request);

            if ($dirty === []) {
                return;
            }

            $after = $this->trackedValues($serviceControl->fresh());
            $oldValues = [];
            $newValues = [];

            foreach (array_keys($dirty) as $field) {
                $oldValues[$field] = $before[$field] ?? null;
                $newValues[$field] = $after[$field] ?? null;
            }

            $summary = array_key_exists('service_status', $newValues)
                ? sprintf(
                    'Status changed from %s to %s.',
                    $oldValues['service_status'] ?? 'N/A',
                    $newValues['service_status'] ?? 'N/A'
                )
                : 'Service control updated.';

            $eventType = array_key_exists('service_status', $newValues) ? 'STATUS_CHANGED' : 'UPDATED';

            $serviceControl->histories()->create([
                'user_id' => $request->user()?->id,
                'event_type' => $eventType,
                'summary' => $summary,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);
        });

        return redirect()->route('service-control.edit', $serviceControl)
            ->with('success', 'Service control updated successfully.');
    }

    public function storeAttachment(Request $request, ServiceControl $serviceControl): JsonResponse
    {
        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        $request->validate([
            'attachments' => ['required', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $this->storeAttachments($request, $serviceControl);
        $serviceControl->load('attachments.user');

        return response()->json([
            'attachments' => $this->serializeAttachments($serviceControl),
        ]);
    }

    public function dropAttachment(Request $request, ServiceControl $serviceControl, Attachment $attachment): JsonResponse
    {
        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        if (
            $attachment->attachable_type !== ServiceControl::class ||
            (int) $attachment->attachable_id !== (int) $serviceControl->id
        ) {
            return response()->json(['message' => 'Attachment not found.'], 404);
        }

        $user = $request->user();
        $canDelete = $user && (
            (int) $attachment->user_id === (int) $user->id ||
            $user->hasRole([
                RoleEnum::ADMIN->value,
                RoleEnum::ACCOUNT_MANAGER->value,
                RoleEnum::SERVICE_MANAGER->value,
            ])
        );

        if (! $canDelete) {
            return response()->json(['message' => 'You do not have permission to delete this file.'], 403);
        }

        $attachment->delete();
        $serviceControl->load('attachments.user');

        return response()->json([
            'message' => 'Attachment deleted.',
            'attachments' => $this->serializeAttachments($serviceControl),
        ]);
    }

    public function destroy(Request $request, ServiceControl $serviceControl): RedirectResponse
    {
        $serviceControl->load('order');
        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        DB::transaction(function () use ($request, $serviceControl) {
            $serviceControl->histories()->create([
                'user_id' => $request->user()?->id,
                'event_type' => 'DELETED',
                'summary' => 'Service control deleted.',
                'old_values' => $this->trackedValues($serviceControl),
            ]);

            $serviceControl->delete();
        });

        return redirect()->route('service-control.index', [
            'type' => $serviceControl->is_bm ? 'bm' : 'services',
        ])->with('success', 'Service control deleted successfully.');
    }

    private function storeAttachments(Request $request, ServiceControl $serviceControl): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('service_control_files', $fileName, 'public');

            $serviceControl->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_type' => 'service_control_files',
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'user_id' => $request->user()?->id,
            ]);
        }
    }

    private function serializeAttachments(ServiceControl $serviceControl): \Illuminate\Support\Collection
    {
        $serviceControl->loadMissing('attachments.user');

        return $serviceControl->attachments
            ->map(fn (Attachment $attachment) => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'file_path' => $attachment->file_path,
                'file_type' => $attachment->file_type,
                'created_at' => optional($attachment->created_at)->toIso8601String(),
                'uploaded_by' => $attachment->user?->name,
                'user_id' => $attachment->user_id,
            ])
            ->values();
    }

    private function loadOrderForServiceControl(int $orderId): Order
    {
        return Order::query()
            ->with([
                'client.companyContact',
                'client.companyContacts',
                'orderCompanyContacts.companyContact',
                'orderCompanyContacts.client',
                'user:id,name',
                'owners:id,name',
                'supervisor:id,name',
                'serviceControls',
            ])
            ->findOrFail($orderId);
    }

    private function serializeServiceControl(ServiceControl $serviceControl, bool $includeHistory = false): array
    {
        $serviceControl->loadMissing([
            'order.client.companyContact',
            'order.client.companyContacts',
            'order.orderCompanyContacts.companyContact',
            'order.orderCompanyContacts.client',
            'order.user:id,name',
            'order.owners:id,name',
            'order.supervisor:id,name',
            'client',
            'creator:id,name',
            'updater:id,name',
            'attachments.user',
        ]);

        $client = $this->serviceClient($serviceControl);
        $payload = [
            'id' => $serviceControl->id,
            'client_id' => $serviceControl->client_id,
            'service_name' => $serviceControl->service_name,
            'service_id' => $serviceControl->service_id,
            'external_order_id' => $serviceControl->external_order_id,
            'is_bm' => (bool) $serviceControl->is_bm,
            'service_source' => $serviceControl->service_source ?? ServiceControlSourceEnum::ESR->value,
            'creation_source' => $serviceControl->creation_source ?? ServiceControlCreationSourceEnum::MANUAL->value,
            'request_origin' => $serviceControl->request_origin ?? ServiceControlRequestOriginEnum::SERVICE->value,
            'service_type' => $this->normalizeServiceTypes($serviceControl->service_type),
            'description' => $serviceControl->description,
            'requires_part' => (bool) $serviceControl->requires_part,
            'requested_parts' => (bool) $serviceControl->requested_parts,
            'parts_available' => (bool) $serviceControl->parts_available,
            'service_status' => $serviceControl->service_status,
            'priority' => $serviceControl->priority,
            'cost' => $serviceControl->cost,
            'area' => $serviceControl->area,
            'requester_type' => $serviceControl->requester_type,
            'requester_id' => $serviceControl->requester_id,
            'requester_role' => $serviceControl->requester_role,
            'assignee_type' => $serviceControl->assignee_type,
            'assignee_id' => $serviceControl->assignee_id,
            'assignee_role' => $serviceControl->assignee_role,
            'assignee_name' => $this->resolvePartyName(
                $serviceControl->assignee_type,
                $serviceControl->assignee_id,
                $serviceControl->order,
                $serviceControl->client
            ),
            'target_date' => $this->formatDate($serviceControl->target_date),
            'service_created_date' => $this->formatDate($serviceControl->service_created_date),
            'service_id_requested_date' => $this->formatDate($serviceControl->service_id_requested_date),
            'eta_date' => $this->formatDate($serviceControl->eta_date),
            'parts_received_date' => $this->formatDate($serviceControl->parts_received_date),
            'urgency_status' => $this->productionOutputUrgencyStatus($serviceControl, (string) config('app.timezone', 'UTC')),
            'production_output_overdue_days' => $serviceControl->production_output_overdue_days,
            'production_output_overdue_resolved_at' => optional($serviceControl->production_output_overdue_resolved_at)->toISOString(),
            'part_delivered_date' => $this->formatDate($serviceControl->part_delivered_date),
            'scheduled_date' => $this->formatDate($serviceControl->scheduled_date),
            'executed_date' => $this->formatDate($serviceControl->executed_date),
            'opened_at' => $this->formatDate($serviceControl->opened_at),
            'closed_at' => $this->formatDate($serviceControl->closed_at),
            'open_days' => $serviceControl->open_days,
            'closure_result' => $serviceControl->closure_result,
            'observations' => $serviceControl->observations,
            'bm_quantity' => $serviceControl->bm_quantity,
            'bm_requested_date' => $this->formatDate($serviceControl->bm_requested_date),
            'bm_picked_up_by' => $serviceControl->bm_picked_up_by,
            'bm_pickup_date' => $this->formatDate($serviceControl->bm_pickup_date),
            'bm_invoice_number' => $serviceControl->bm_invoice_number,
            'bm_invoice_status' => $serviceControl->bm_invoice_status,
            'created_at' => optional($serviceControl->created_at)->toISOString(),
            'updated_at' => optional($serviceControl->updated_at)->toISOString(),
            'attachments' => $this->serializeAttachments($serviceControl),
            'is_missing_service_id_overdue' => $this->isMissingServiceIdOverdue($serviceControl),
            'is_missing_eta_overdue' => $this->isMissingEtaOverdue($serviceControl),
            'creator' => $serviceControl->creator ? [
                'id' => $serviceControl->creator->id,
                'name' => $serviceControl->creator->name,
            ] : null,
            'updater' => $serviceControl->updater ? [
                'id' => $serviceControl->updater->id,
                'name' => $serviceControl->updater->name,
            ] : null,
            'order' => $serviceControl->order
                ? $this->serializeOrderForServiceControl($serviceControl->order)
                : $this->standaloneOrderSummary($serviceControl->client),
            'client' => $client ? $this->serializeServiceClient($client) : null,
        ];

        if ($includeHistory) {
            $serviceControl->loadMissing('histories.user:id,name');
            $payload['histories'] = $serviceControl->histories
                ->map(fn (ServiceControlHistory $history) => [
                    'id' => $history->id,
                    'event_type' => $history->event_type,
                    'summary' => $history->summary,
                    'comment' => $history->comment,
                    'old_values' => $history->old_values,
                    'new_values' => $history->new_values,
                    'created_at' => optional($history->created_at)->toISOString(),
                    'created_at_label' => optional($history->created_at)->format('m/d/Y h:i A'),
                    'user' => $history->user ? [
                        'id' => $history->user->id,
                        'name' => $history->user->name,
                    ] : null,
                ])
                ->values();
        }

        return $payload;
    }

    private function serializeOrderForServiceControl(Order $order): array
    {
        $selectedCompanyContact = $order->orderCompanyContacts
            ->firstWhere('is_selected', true)
            ?? ($order->orderCompanyContacts->count() === 1 ? $order->orderCompanyContacts->first() : null);

        $companyContact = $selectedCompanyContact?->companyContact
            ?? $order->client?->companyContact
            ?? $order->client?->companyContacts?->first();

        return [
            'id' => $order->id,
            'name' => $order->name,
            'order_number' => $order->order_number,
            'parent_order_id' => $order->parent_order_id,
            'parent_order' => $order->parentOrder ? [
                'id' => $order->parentOrder->id,
                'name' => $order->parentOrder->name,
                'order_number' => $order->parentOrder->order_number,
            ] : null,
            'order_type' => $order->order_type,
            'job_address' => $order->job_address,
            'city' => $order->city,
            'job_state' => $order->job_state,
            'job_zip' => $order->job_zip,
            'address_label' => collect([$order->job_address, $order->city, $order->job_state, $order->job_zip])
                ->filter(fn ($value) => filled($value))
                ->implode(', '),
            'today_date' => now()->format('Y-m-d'),
            'client' => [
                'id' => $order->client?->id,
                'name' => $order->client?->name,
                'phone' => $order->client?->phone,
                'email' => $order->client_email_override ?: $order->client?->email,
                'other_phone' => $order->client?->other_phone,
                'secondary_email' => $order->client?->secondary_email,
                'contact_type' => $order->client?->contact_type,
                'vip_clients' => (bool) ($order->client?->vip_clients ?? false),
                'vip_notes' => $order->client?->vip_notes,
            ],
            'company' => $companyContact ? [
                'id' => $companyContact->id,
                'name' => $companyContact->name,
                'email' => $companyContact->email,
                'phone' => $companyContact->phone,
            ] : null,
            'supervisor' => $order->supervisor ? [
                'id' => $order->supervisor->id,
                'name' => $order->supervisor->name,
            ] : null,
            'seller' => $order->user ? [
                'id' => $order->user->id,
                'name' => $order->user->name,
            ] : null,
            'owners' => $order->owners
                ->map(fn (User $owner) => [
                    'id' => $owner->id,
                    'name' => $owner->name,
                ])
                ->values()
                ->all(),
            'service_controls' => $order->serviceControls
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (ServiceControl $serviceControl) => [
                    'id' => $serviceControl->id,
                    'service_name' => $serviceControl->service_name,
                    'service_id' => $serviceControl->service_id,
                    'service_source' => $serviceControl->service_source ?? ServiceControlSourceEnum::ESR->value,
                    'creation_source' => $serviceControl->creation_source ?? ServiceControlCreationSourceEnum::MANUAL->value,
                    'request_origin' => $serviceControl->request_origin ?? ServiceControlRequestOriginEnum::SERVICE->value,
                    'service_type' => $this->normalizeServiceTypes($serviceControl->service_type),
                    'service_status' => $serviceControl->service_status,
                    'priority' => $serviceControl->priority,
                    'opened_at' => $this->formatDate($serviceControl->opened_at),
                    'open_days' => $serviceControl->open_days,
                ])
                ->all(),
        ];
    }

    private function standaloneOrderSummary(?Client $client = null, array $externalOrder = [], array $externalContext = []): array
    {
        $company = $externalContext['company'] ?? null;
        $owner = $externalContext['owner'] ?? null;

        return [
            'id' => null,
            'name' => $externalOrder['name'] ?? 'Standalone Service',
            'order_number' => $externalOrder['order_number'] ?? null,
            'order_type' => $externalOrder !== [] ? 'service' : null,
            'job_address' => null,
            'city' => null,
            'job_state' => null,
            'job_zip' => null,
            'address_label' => null,
            'today_date' => now()->format('Y-m-d'),
            'client' => $client ? $this->serializeServiceClient($client) : null,
            'company' => $company
                ? [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                ]
                : (!empty($externalOrder['company_name']) ? [
                    'id' => null,
                    'name' => $externalOrder['company_name'],
                    'email' => $externalOrder['company_email'] ?? null,
                    'phone' => $externalOrder['company_phone'] ?? null,
                ] : null),
            'supervisor' => null,
            'seller' => null,
            'owners' => $owner ? [[
                'id' => $owner->id,
                'name' => $owner->name,
            ]] : [],
            'service_controls' => [],
        ];
    }

    private function resolveExternalServiceOrderContext(Request $request, array $externalOrder): array
    {
        $company = $this->resolveExternalCompany($request, $externalOrder);
        $client = $this->resolveExternalClient($request, $externalOrder);
        $owner = $this->resolveExternalOwner($externalOrder);

        return [
            'company' => $company,
            'client' => $client,
            'owner' => $owner,
        ];
    }

    private function resolveExternalCompany(Request $request, array $externalOrder): ?CompanyContact
    {
        $email = strtolower(trim((string) ($externalOrder['company_email'] ?? '')));
        $phone = $this->normalizePhone($externalOrder['company_phone'] ?? null);
        $name = trim((string) ($externalOrder['company_name'] ?? ''));

        $query = CompanyContact::visibleTo($request->user())
            ->select('id', 'name', 'email', 'phone');

        if ($email !== '') {
            $company = (clone $query)->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($company) {
                return $company;
            }
        }

        if ($phone !== '') {
            $company = (clone $query)
                ->whereNotNull('phone')
                ->get()
                ->first(fn (CompanyContact $item) => $this->normalizePhone($item->phone) === $phone);

            if ($company) {
                return $company;
            }
        }

        if ($name !== '') {
            return (clone $query)->where('name', $name)->first();
        }

        return null;
    }

    private function resolveExternalClient(Request $request, array $externalOrder): ?Client
    {
        $email = strtolower(trim((string) ($externalOrder['client_email'] ?? $externalOrder['company_email'] ?? '')));
        $phone = $this->normalizePhone($externalOrder['client_phone'] ?? $externalOrder['company_phone'] ?? null);
        $name = trim((string) ($externalOrder['client_name'] ?? $externalOrder['company_name'] ?? ''));

        $query = Client::visibleTo($request->user())
            ->select('id', 'name', 'phone', 'email', 'other_phone', 'secondary_email');

        if ($phone !== '') {
            $client = (clone $query)
                ->whereNotNull('phone')
                ->get()
                ->first(fn (Client $item) => $this->normalizePhone($item->phone) === $phone);

            if ($client) {
                return $client;
            }
        }

        if ($email !== '') {
            $client = (clone $query)->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($client) {
                return $client;
            }
        }

        if ($name !== '') {
            return (clone $query)->where('name', $name)->first();
        }

        return null;
    }

    private function resolveExternalOwner(array $externalOrder): ?User
    {
        $email = strtolower(trim((string) ($externalOrder['owner_email'] ?? '')));
        $name = trim((string) ($externalOrder['owner_name'] ?? ''));

        $query = User::assignableOrderOwner()
            ->select('id', 'name', 'email')
            ->where('status', StatusUserEnum::ACTIVE->value);

        if ($email !== '') {
            $owner = (clone $query)->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($owner) {
                return $owner;
            }
        }

        if ($name !== '') {
            return (clone $query)->where('name', $name)->first();
        }

        return null;
    }

    private function externalOrderSummaryFromRequest(Request $request): array
    {
        $externalOrderId = trim((string) $request->query('external_order_id', ''));
        $orderNumber = trim((string) $request->query('external_order_number', ''));
        $name = trim((string) $request->query('external_order_name', ''));
        $companyName = trim((string) $request->query('external_company_name', ''));
        $clientName = trim((string) $request->query('external_client_name', ''));
        $ownerName = trim((string) $request->query('external_owner_name', ''));
        $ownerEmail = trim((string) $request->query('external_owner_email', ''));

        if ($externalOrderId === '' && $orderNumber === '' && $name === '' && $companyName === '' && $clientName === '' && $ownerEmail === '') {
            return [];
        }

        return [
            'external_order_id' => $externalOrderId !== '' ? $externalOrderId : null,
            'order_number' => $orderNumber !== '' ? $orderNumber : null,
            'name' => $name !== '' ? $name : 'External ESR Service',
            'amount' => trim((string) $request->query('external_amount', '')),
            'company_name' => $companyName !== '' ? $companyName : null,
            'company_email' => trim((string) $request->query('external_company_email', '')) ?: null,
            'company_phone' => trim((string) $request->query('external_company_phone', '')) ?: null,
            'client_name' => $clientName !== '' ? $clientName : null,
            'client_email' => trim((string) $request->query('external_client_email', '')) ?: null,
            'client_phone' => trim((string) $request->query('external_client_phone', '')) ?: null,
            'owner_name' => $ownerName !== '' ? $ownerName : null,
            'owner_email' => $ownerEmail !== '' ? $ownerEmail : null,
        ];
    }

    private function externalServiceDefaults(array $externalOrder, array $externalContext = []): array
    {
        if ($externalOrder === []) {
            return [];
        }

        $orderNumber = $externalOrder['order_number'] ?? null;
        $externalOrderId = $externalOrder['external_order_id'] ?? null;
        $orderName = $externalOrder['name'] ?? 'External ESR Service';
        $company = $externalContext['company'] ?? null;
        $owner = $externalContext['owner'] ?? null;

        return [
            'service_name' => $orderName,
            'service_id' => $orderNumber,
            'external_order_id' => $externalOrderId,
            'service_source' => ServiceControlSourceEnum::ESR->value,
            'creation_source' => ServiceControlCreationSourceEnum::EXTERNAL->value,
            'request_origin' => ServiceControlRequestOriginEnum::SERVICE->value,
            'cost' => $externalOrder['amount'] ?? '',
            'description' => '',
            'new_client' => [
                'name' => $externalOrder['client_name'] ?? $externalOrder['company_name'] ?? '',
                'phone' => $externalOrder['client_phone'] ?? $externalOrder['company_phone'] ?? '',
                'email' => $externalOrder['client_email'] ?? $externalOrder['company_email'] ?? '',
                'other_phone' => '',
                'secondary_email' => '',
            ],
            'external_company_contact_id' => $company?->id,
            'external_owner_id' => $owner?->id,
            'external_owner_name' => $externalOrder['owner_name'] ?? '',
            'external_owner_email' => $externalOrder['owner_email'] ?? '',
        ];
    }

    private function serializeClientOption(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'other_phone' => $client->other_phone,
            'secondary_email' => $client->secondary_email,
            'label' => trim(($client->name ?: 'Client') . ' - ' . ($client->phone ?: $client->email ?: 'No contact')),
        ];
    }

    private function serializeServiceClient(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'other_phone' => $client->other_phone,
            'secondary_email' => $client->secondary_email,
        ];
    }

    private function serviceClient(ServiceControl $serviceControl): ?Client
    {
        return $serviceControl->order?->client ?? $serviceControl->client;
    }

    private function serviceControlOrderNumber(ServiceControl $serviceControl): ?string
    {
        $source = $serviceControl->service_source ?? ServiceControlSourceEnum::ESR->value;

        if ($source === ServiceControlSourceEnum::ESR->value) {
            return filled($serviceControl->external_order_id) ? (string) $serviceControl->external_order_id : null;
        }

        $parentOrderNumber = $serviceControl->order?->parentOrder?->order_number;

        if (filled($parentOrderNumber)) {
            return (string) $parentOrderNumber;
        }

        return filled($serviceControl->order?->order_number) ? (string) $serviceControl->order->order_number : null;
    }

    private function trackedValues(ServiceControl $serviceControl): array
    {
        return [
            'client_id' => $serviceControl->client_id,
            'service_name' => $serviceControl->service_name,
            'service_id' => $serviceControl->service_id,
            'external_order_id' => $serviceControl->external_order_id,
            'is_bm' => (bool) $serviceControl->is_bm,
            'service_source' => $serviceControl->service_source ?? ServiceControlSourceEnum::ESR->value,
            'creation_source' => $serviceControl->creation_source ?? ServiceControlCreationSourceEnum::MANUAL->value,
            'request_origin' => $serviceControl->request_origin ?? ServiceControlRequestOriginEnum::SERVICE->value,
            'service_type' => $this->normalizeServiceTypes($serviceControl->service_type),
            'description' => $serviceControl->description,
            'requires_part' => (bool) $serviceControl->requires_part,
            'requested_parts' => (bool) $serviceControl->requested_parts,
            'parts_available' => (bool) $serviceControl->parts_available,
            'service_status' => $serviceControl->service_status,
            'priority' => $serviceControl->priority,
            'cost' => $serviceControl->cost,
            'area' => $serviceControl->area,
            'requester_type' => $serviceControl->requester_type,
            'requester_id' => $serviceControl->requester_id,
            'requester_role' => $serviceControl->requester_role,
            'assignee_type' => $serviceControl->assignee_type,
            'assignee_id' => $serviceControl->assignee_id,
            'assignee_role' => $serviceControl->assignee_role,
            'target_date' => $this->formatDate($serviceControl->target_date),
            'service_created_date' => $this->formatDate($serviceControl->service_created_date),
            'service_id_requested_date' => $this->formatDate($serviceControl->service_id_requested_date),
            'eta_date' => $this->formatDate($serviceControl->eta_date),
            'parts_received_date' => $this->formatDate($serviceControl->parts_received_date),
            'production_output_overdue_days' => $serviceControl->production_output_overdue_days,
            'production_output_overdue_resolved_at' => optional($serviceControl->production_output_overdue_resolved_at)->toISOString(),
            'part_delivered_date' => $this->formatDate($serviceControl->part_delivered_date),
            'scheduled_date' => $this->formatDate($serviceControl->scheduled_date),
            'executed_date' => $this->formatDate($serviceControl->executed_date),
            'opened_at' => $this->formatDate($serviceControl->opened_at),
            'closed_at' => $this->formatDate($serviceControl->closed_at),
            'closure_result' => $serviceControl->closure_result,
            'observations' => $serviceControl->observations,
            'bm_quantity' => $serviceControl->bm_quantity,
            'bm_requested_date' => $this->formatDate($serviceControl->bm_requested_date),
            'bm_picked_up_by' => $serviceControl->bm_picked_up_by,
            'bm_pickup_date' => $this->formatDate($serviceControl->bm_pickup_date),
            'bm_invoice_number' => $serviceControl->bm_invoice_number,
            'bm_invoice_status' => $serviceControl->bm_invoice_status,
        ];
    }

    private function buildPayload(Request $request, ?ServiceControl $serviceControl): array
    {
        $status = (string) $request->input('service_status');
        $executedDate = $request->input('executed_date');
        $etaDate = $request->input('eta_date');
        $partsReceivedDate = filled($etaDate)
            ? $this->productionOutputDateFromEta((string) $etaDate)
            : $request->input('parts_received_date');
        $orderId = $serviceControl?->order_id ?? (((int) $request->input('order_id', 0)) ?: null);
        $closedAt = $status === ServiceControlStatusEnum::COMPLETED->value
            ? ($executedDate ?: now()->format('Y-m-d'))
            : null;
        $isBm = $orderId ? $request->boolean('is_bm') : false;
        $clientId = $orderId ? null : $this->resolveStandaloneClientId($request, $serviceControl);
        $requesterType = $isBm ? null : $request->input('requester_type');
        $requesterId = $isBm ? null : $request->input('requester_id');
        $requesterRole = $isBm ? null : $request->input('requester_role');
        $creationSource = $serviceControl?->creation_source
            ?? ($request->input('creation_source') === ServiceControlCreationSourceEnum::EXTERNAL->value
                ? ServiceControlCreationSourceEnum::EXTERNAL->value
                : ServiceControlCreationSourceEnum::MANUAL->value);
        $serviceSource = $creationSource === ServiceControlCreationSourceEnum::EXTERNAL->value
            ? ServiceControlSourceEnum::ESR->value
            : $request->input('service_source', ServiceControlSourceEnum::ESR->value);
        $requestOrigin = $serviceControl?->request_origin
            ?? ($request->input('request_origin') === ServiceControlRequestOriginEnum::OWNER->value
                ? ServiceControlRequestOriginEnum::OWNER->value
                : ServiceControlRequestOriginEnum::SERVICE->value);
        $externalOrderId = $serviceControl?->external_order_id
            ?? (filled($request->input('external_order_id')) ? $request->input('external_order_id') : null);

        if (! $orderId && $clientId && blank($requesterType) && blank($requesterId)) {
            $requesterType = 'client';
            $requesterId = $clientId;
            $requesterRole = 'client';
        }

        return [
            'order_id' => $orderId,
            'client_id' => $clientId,
            'service_name' => $request->input('service_name'),
            'service_id' => $isBm ? null : $request->input('service_id'),
            'external_order_id' => $isBm ? null : $externalOrderId,
            'is_bm' => $isBm,
            'service_source' => $isBm ? null : $serviceSource,
            'creation_source' => $isBm ? null : $creationSource,
            'request_origin' => $isBm ? null : $requestOrigin,
            'service_type' => $isBm ? null : $this->normalizeServiceTypes($request->input('service_type')),
            'description' => $isBm ? null : $request->input('description'),
            'requires_part' => $isBm ? false : $request->boolean('requires_part'),
            'requested_parts' => $isBm ? false : $request->boolean('requested_parts'),
            'parts_available' => $isBm ? false : $request->boolean('parts_available'),
            'service_status' => $isBm ? null : $status,
            'priority' => $isBm ? null : $request->input('priority'),
            'cost' => $isBm ? null : $request->input('cost'),
            'area' => $isBm ? null : $request->input('area'),
            'requester_type' => $requesterType,
            'requester_id' => $requesterId,
            'requester_role' => $requesterRole,
            'assignee_type' => $isBm ? null : $request->input('assignee_type'),
            'assignee_id' => $isBm ? null : $request->input('assignee_id'),
            'assignee_role' => $isBm ? null : $request->input('assignee_role'),
            'target_date' => $isBm ? null : $request->input('target_date'),
            'service_created_date' => $isBm ? null : $request->input('service_created_date'),
            'service_id_requested_date' => $isBm ? null : $request->input('service_id_requested_date'),
            'eta_date' => $isBm ? null : $etaDate,
            'parts_received_date' => $isBm ? null : $partsReceivedDate,
            'part_delivered_date' => $isBm ? null : $request->input('part_delivered_date'),
            'scheduled_date' => $isBm ? null : $request->input('scheduled_date'),
            'executed_date' => $isBm ? null : $executedDate,
            'opened_at' => $serviceControl?->opened_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'closed_at' => $closedAt,
            'closure_result' => $isBm ? null : $request->input('closure_result'),
            'observations' => $isBm ? null : $request->input('observations'),
            'bm_quantity' => $isBm ? $request->input('bm_quantity') : null,
            'bm_requested_date' => $isBm ? $request->input('bm_requested_date') : null,
            'bm_picked_up_by' => $isBm ? $request->input('bm_picked_up_by') : null,
            'bm_pickup_date' => $isBm ? $request->input('bm_pickup_date') : null,
            'bm_invoice_number' => $isBm ? $request->input('bm_invoice_number') : null,
            'bm_invoice_status' => $isBm ? $request->input('bm_invoice_status') : null,
            'created_by' => $serviceControl?->created_by ?? $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ];
    }

    private function resolveStandaloneClientId(Request $request, ?ServiceControl $serviceControl): ?int
    {
        if ($serviceControl?->order_id || ((int) $request->input('order_id', 0)) > 0) {
            return null;
        }

        $clientId = (int) $request->input('client_id', 0);

        if ($clientId > 0) {
            return $clientId;
        }

        $clientData = $request->input('new_client', []);
        $name = trim((string) ($clientData['name'] ?? ''));

        if ($name === '') {
            return $serviceControl?->client_id;
        }

        $phone = trim((string) ($clientData['phone'] ?? ''));
        $email = trim((string) ($clientData['email'] ?? ''));
        $existingClient = null;

        if ($phone !== '') {
            $existingClient = Client::query()->where('phone', $phone)->first();
        }

        if (! $existingClient && $email !== '') {
            $existingClient = Client::query()->where('email', $email)->first();
        }

        if ($existingClient) {
            return (int) $existingClient->id;
        }

        return (int) Client::create([
            'name' => $name,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'other_phone' => trim((string) ($clientData['other_phone'] ?? '')) ?: null,
            'secondary_email' => trim((string) ($clientData['secondary_email'] ?? '')) ?: null,
            'source' => ContactSourceEnum::DIRECT_CALL->value,
            'user_id' => $request->user()?->id,
            'is_contact' => true,
        ])->id;
    }

    private function formatDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse((string) $value)->format('Y-m-d');
    }

    private function productionOutputDateFromEta(string $etaDate): string
    {
        return Carbon::parse($etaDate)->addWeekdays(2)->format('Y-m-d');
    }

    private function normalizeServiceTypes(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $types = is_array($value) ? $value : [$value];

        return collect($types)
            ->filter(fn ($type) => filled($type))
            ->map(fn ($type) => (string) $type)
            ->unique()
            ->values()
            ->all();
    }

    private function syncServiceManCompletedOrderStatus(ServiceControl $serviceControl, Request $request): void
    {
        if (! $this->isCompletedServiceMan($serviceControl)) {
            return;
        }

        $order = $serviceControl->order;

        if (! $order || ! $order->is_post_sale_service || $order->status === OrderStatusEnum::COMPLETE->value) {
            return;
        }

        $order->update([
            'status' => OrderStatusEnum::COMPLETE->value,
        ]);

        $order->orderStatus()->create([
            'status' => OrderStatusEnum::COMPLETE->value,
            'user_id' => $request->user()?->id,
            'notes' => 'Post-sale service order completed automatically from Service Control.',
        ]);
    }

    private function isCompletedServiceMan(ServiceControl $serviceControl): bool
    {
        return (string) $serviceControl->service_status === ServiceControlStatusEnum::COMPLETED->value
            && in_array(ServiceControlTypeEnum::SERVICE_MAN->value, $this->normalizeServiceTypes($serviceControl->service_type), true);
    }

    private function serviceCalendarDate(ServiceControl $serviceControl, string $timezone): ?Carbon
    {
        if (! empty($serviceControl->scheduled_date)) {
            return Carbon::parse($serviceControl->scheduled_date, $timezone);
        }

        if (! empty($serviceControl->parts_received_date)) {
            return Carbon::parse($serviceControl->parts_received_date, $timezone);
        }

        return null;
    }

    private function productionOutputOverdueDays(ServiceControl $serviceControl, string $timezone, ?CarbonInterface $asOf = null): int
    {
        if (empty($serviceControl->parts_received_date)) {
            return 0;
        }

        $productionOutputDate = Carbon::parse($serviceControl->parts_received_date, $timezone)->startOfDay();
        $comparisonDate = $asOf
            ? Carbon::parse($asOf, $timezone)->startOfDay()
            : now($timezone)->startOfDay();

        return (int) max(0, $productionOutputDate->diffInDays($comparisonDate, false));
    }

    private function productionOutputResolvedStatuses(): array
    {
        return [
            ServiceControlStatusEnum::PRODUCTION_IN_PROGRESS->value,
            ServiceControlStatusEnum::PRODUCTION_COMPLETED->value,
            ServiceControlStatusEnum::READY_FOR_DELIVERY->value,
            ServiceControlStatusEnum::SCHEDULE_SERVICE_MAN->value,
            ServiceControlStatusEnum::SCHEDULED_SERVICE_MAN->value,
            ServiceControlStatusEnum::DELIVERED->value,
            ServiceControlStatusEnum::COMPLETED->value,
        ];
    }

    private function isProductionOutputResolved(ServiceControl $serviceControl): bool
    {
        return in_array((string) $serviceControl->service_status, $this->productionOutputResolvedStatuses(), true);
    }

    private function productionOutputUrgencyStatus(ServiceControl $serviceControl, string $timezone): string
    {
        if (empty($serviceControl->parts_received_date)) {
            return 'No Production Output Date';
        }

        $productionOutputDate = Carbon::parse($serviceControl->parts_received_date, $timezone)->startOfDay();
        $today = now($timezone)->startOfDay();
        $days = $productionOutputDate->diffInDays($today, false);

        if ($this->isProductionOutputResolved($serviceControl)) {
            $storedDays = (int) ($serviceControl->production_output_overdue_days ?? 0);

            return $storedDays > 0
                ? 'Resolved after ' . $storedDays . ' overdue ' . ($storedDays === 1 ? 'day' : 'days')
                : 'Resolved on time';
        }

        if ($days > 0) {
            return 'Overdue by ' . $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        if ($days === 0) {
            return 'Due today';
        }

        $daysRemaining = abs($days);

        return 'Due in ' . $daysRemaining . ' ' . ($daysRemaining === 1 ? 'day' : 'days');
    }

    private function resolveProductionOutputOverdueIfNeeded(ServiceControl $serviceControl, array $before): void
    {
        if ((string) $serviceControl->service_status !== ServiceControlStatusEnum::PRODUCTION_IN_PROGRESS->value) {
            return;
        }

        if (($before['service_status'] ?? null) === ServiceControlStatusEnum::PRODUCTION_IN_PROGRESS->value) {
            return;
        }

        if (empty($serviceControl->parts_received_date)) {
            return;
        }

        $timezone = (string) config('app.timezone', 'UTC');

        $serviceControl->production_output_overdue_days = $this->productionOutputOverdueDays($serviceControl, $timezone);
        $serviceControl->production_output_overdue_resolved_at = now();
    }

    private function serviceCalendarStatuses(): array
    {
        return [
            ServiceControlStatusEnum::ORDER_IN_REVIEW->value,
            ServiceControlStatusEnum::MATERIAL_REVIEWED->value,
            ServiceControlStatusEnum::PRODUCTION->value,
            ServiceControlStatusEnum::PRODUCTION_IN_PROGRESS->value,
            ServiceControlStatusEnum::PRODUCTION_COMPLETED->value,
            ServiceControlStatusEnum::READY_FOR_DELIVERY->value,
            ServiceControlStatusEnum::SCHEDULE_SERVICE_MAN->value,
            ServiceControlStatusEnum::SCHEDULED_SERVICE_MAN->value,
            ServiceControlStatusEnum::DELIVERED->value,
            ServiceControlStatusEnum::COMPLETED->value,
        ];
    }

    private function serviceStatusOptions(): array
    {
        return collect(ServiceControlStatusEnum::cases())
            ->map(fn (ServiceControlStatusEnum $status) => $status->value)
            ->values()
            ->all();
    }

    private function serviceCalendarOriginColor(?string $requestOrigin): string
    {
        return [
            ServiceControlRequestOriginEnum::SERVICE->value => '#2563eb',
            ServiceControlRequestOriginEnum::OWNER->value => '#7c3aed',
        ][$requestOrigin] ?? '#2563eb';
    }

    private function serviceCalendarScheduledColor(): string
    {
        return '#facc15';
    }

    private function serviceCalendarEventColor(ServiceControl $serviceControl, string $status, ?string $requestOrigin, string $timezone): string
    {
        if (! empty($serviceControl->scheduled_date)) {
            return $this->serviceCalendarScheduledColor();
        }

        if (
            ! in_array($status, $this->productionOutputResolvedStatuses(), true)
            && ! empty($serviceControl->parts_received_date)
            && Carbon::parse($serviceControl->parts_received_date, $timezone)->startOfDay()->lte(now($timezone)->startOfDay())
        ) {
            return '#dc2626';
        }

        if ($status === ServiceControlStatusEnum::COMPLETED->value) {
            return '#16a34a';
        }

        return $this->serviceCalendarOriginColor($requestOrigin);
    }

    private function humanizeStatus(string $status): string
    {
        return $status;
    }

    private function isMissingServiceIdOverdue(ServiceControl $serviceControl): bool
    {
        if ($serviceControl->is_bm || filled($serviceControl->service_id)) {
            return false;
        }

        $baseDate = $serviceControl->service_created_date ?: $serviceControl->created_at;

        if (empty($baseDate)) {
            return false;
        }

        return Carbon::parse($baseDate)->startOfDay()->diffInDays(now()->startOfDay()) >= 5;
    }

    private function isMissingEtaOverdue(ServiceControl $serviceControl): bool
    {
        if ($serviceControl->is_bm || blank($serviceControl->service_id) || filled($serviceControl->eta_date)) {
            return false;
        }

        $baseDate = $serviceControl->service_id_requested_date;

        if (empty($baseDate)) {
            return false;
        }

        return Carbon::parse($baseDate)->startOfDay()->diffInDays(now()->startOfDay()) >= 5;
    }

    private function buildRequesterOptions(?Order $order, ?Client $standaloneClient = null): array
    {
        $options = collect();

        if ($order?->client) {
            $options->push($this->partyOption('client', $order->client->id, $order->client->name, 'client'));
        }

        if (! $order && $standaloneClient) {
            $options->push($this->partyOption('client', $standaloneClient->id, $standaloneClient->name, 'client'));
        }

        User::query()
            ->with('roles:id,name')
            ->select('id', 'name')
            ->whereDoesntHave('roles', fn (Builder $query) => $query->where('name', RoleEnum::CUSTOMER->value))
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($options) {
                $roles = $user->roles->pluck('name')->reject(fn (string $role) => $role === RoleEnum::CUSTOMER->value);

                if ($roles->isEmpty()) {
                    $options->push($this->partyOption('user', $user->id, $user->name, 'no_role'));

                    return;
                }

                $roles->each(fn (string $role) => $options->push($this->partyOption('user', $user->id, $user->name, $role)));
            });

        return $options->unique('value')->values()->all();
    }

    private function buildAssigneeOptions(?Order $order, ?Client $standaloneClient = null): array
    {
        $options = collect();

        $assignableRoles = $this->existingRoleNames([
            RoleEnum::SERVICE->value,
        ]);

        User::query()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->role($assignableRoles)
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($options) {
                $options->push($this->partyOption('user', $user->id, $user->name, RoleEnum::SERVICE->value));
            });

        return $options->unique('value')->values()->all();
    }

    private function partyOption(string $type, ?int $id, ?string $name, string $role): array
    {
        return [
            'value' => $type . ':' . $id . ':' . $role,
            'label' => trim(($name ?: 'N/A') . ' - ' . ucwords(str_replace('_', ' ', $role))),
            'type' => $type,
            'id' => $id,
            'role' => $role,
        ];
    }

    private function resolvePartyName(?string $type, mixed $id, ?Order $order, ?Client $standaloneClient = null): ?string
    {
        $id = (int) $id;

        if ($id <= 0 || ! $type) {
            return null;
        }

        if ($type === 'client') {
            if ((int) ($order?->client?->id ?? 0) === $id) {
                return $order?->client?->name;
            }

            if ((int) ($standaloneClient?->id ?? 0) === $id) {
                return $standaloneClient?->name;
            }

            return Client::query()->whereKey($id)->value('name');
        }

        if ($type === 'user') {
            if ((int) ($order?->supervisor?->id ?? 0) === $id) {
                return $order?->supervisor?->name;
            }

            if ((int) ($order?->user?->id ?? 0) === $id) {
                return $order?->user?->name;
            }

            $owner = $order?->owners?->firstWhere('id', $id);
            if ($owner) {
                return $owner->name;
            }

            return User::query()->whereKey($id)->value('name');
        }

        return null;
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
            RoleEnum::SERVICE_MANAGER->value,
        ]);
    }

    private function ensureCanAccessOrder(?User $user, Order $order): void
    {
        if (!$user) {
            abort(403);
        }

        if ($this->isOwnerRestricted($user) && !$order->isAccessibleToOwner($user)) {
            abort(403, 'You are not authorized to access this order.');
        }
    }

    private function ensureCanAccessServiceControl(?User $user, ServiceControl $serviceControl): void
    {
        $serviceControl->loadMissing('order');

        if (! $serviceControl->order) {
            if (! $user) {
                abort(403);
            }

            return;
        }

        $this->ensureCanAccessOrder($user, $serviceControl->order);
    }
}
