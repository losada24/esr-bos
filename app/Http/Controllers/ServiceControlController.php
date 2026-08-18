<?php

namespace App\Http\Controllers;

use App\Enum\AreaEnum;
use App\Enum\BmInvoiceStatusEnum;
use App\Enum\ContactSourceEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceControlClosureResultEnum;
use App\Enum\ServiceControlPriorityEnum;
use App\Enum\ServiceControlStatusEnum;
use App\Enum\ServiceControlTypeEnum;
use App\Enum\StatusUserEnum;
use App\Http\Requests\StoreServiceControlRequest;
use App\Http\Requests\UpdateServiceControlRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\ServiceControl;
use App\Models\ServiceControlHistory;
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
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ServiceControlController extends Controller
{
    public function index(Request $request): Response
    {
        $data = $this->buildIndexData($request, 50, true);

        return Inertia::render('ServiceControl/Index', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->buildIndexData($request, 50, true);
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
            'legend' => collect($this->serviceCalendarStatuses())
                ->map(fn (string $status) => [
                    'label' => $this->humanizeStatus($status),
                    'status' => $status,
                    'color' => $this->serviceCalendarStatusColor($status),
                ])
                ->values(),
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
            ->whereIn('service_status', $statuses)
            ->when($search !== '', function (Builder $query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function (Builder $builder) use ($like) {
                    $builder
                        ->where('service_name', 'like', $like)
                        ->orWhere('service_id', 'like', $like)
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
                $eventDate = $this->serviceCalendarDate($serviceControl, $status, $calendarTimezone);

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
                    'color' => $this->serviceCalendarStatusColor($status),
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
                    'order_number' => $order?->order_number,
                    'client_name' => $client?->name ?? '',
                    'client_phone' => $client?->phone ?? '',
                    'owner_names' => $owners,
                    'supervisor_name' => $order?->supervisor?->name ?? '',
                    'event_date' => $eventStart->format('M d, Y'),
                    'scheduled_date' => $this->formatDate($serviceControl->scheduled_date),
                    'service_created_date' => $this->formatDate($serviceControl->service_created_date),
                    'open_days' => $serviceControl->open_days,
                    'description' => $serviceControl->description,
                    'tooltip' => implode(' | ', array_filter([
                        $serviceControl->service_name,
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

    private function buildIndexData(Request $request, ?int $limit = null, bool $paginate = false): array
    {
        $query = ServiceControl::query()
            ->with([
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
            ]);

        if ($this->isOwnerRestricted($request->user())) {
            $query->whereHas('order', fn (Builder $builder) => $builder->accessibleToOwner($request->user()));
        }

        $type = $request->query('type') === 'bm' ? 'bm' : 'services';
        $query->where('is_bm', $type === 'bm');

        $text = trim((string) $request->query('search', ''));
        if ($text !== '') {
            $like = '%' . $text . '%';
            $query->where(function (Builder $builder) use ($like) {
                $builder
                    ->where('service_name', 'like', $like)
                    ->orWhere('service_id', 'like', $like)
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

        $query->latest();

        if ($limit !== null && ! $paginate) {
            $query->limit($limit);
        }

        $serviceControls = $paginate
            ? $query
                ->paginate($limit ?? 50)
                ->withQueryString()
                ->through(fn (ServiceControl $serviceControl) => $this->serializeServiceControl($serviceControl))
            : $query
                ->get()
                ->map(fn (ServiceControl $serviceControl) => $this->serializeServiceControl($serviceControl))
                ->values();

        return [
            'serviceControls' => $serviceControls,
            'filters' => [
                'search' => $text,
                'status' => $status,
                'priority' => $priority,
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

    public function create(Request $request): RedirectResponse|Response
    {
        $orderId = (int) $request->query('order_id', 0);
        $order = $orderId > 0 ? $this->loadOrderForServiceControl($orderId) : null;

        if ($order) {
            $this->ensureCanAccessOrder($request->user(), $order);
        }

        return Inertia::render('ServiceControl/Create', [
            'order' => $order ? $this->serializeOrderForServiceControl($order) : $this->standaloneOrderSummary(),
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

        if ($orderId > 0) {
            $order = $this->loadOrderForServiceControl($orderId);
            $this->ensureCanAccessOrder($request->user(), $order);
        }

        $serviceControl = DB::transaction(function () use ($request) {
            $serviceControl = ServiceControl::create($this->buildPayload($request, null));

            $serviceControl->histories()->create([
                'user_id' => $request->user()?->id,
                'event_type' => 'CREATED',
                'summary' => 'Service control created.',
                'new_values' => $this->trackedValues($serviceControl),
            ]);

            return $serviceControl;
        });

        return redirect()->route('service-control.edit', $serviceControl)
            ->with('success', 'Service control created successfully.');
    }

    public function update(UpdateServiceControlRequest $request, ServiceControl $serviceControl): RedirectResponse
    {
        $serviceControl->load('order');
        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        $before = $this->trackedValues($serviceControl);

        DB::transaction(function () use ($request, $serviceControl, $before) {
            $payload = $this->buildPayload($request, $serviceControl);
            $serviceControl->fill($payload);

            $dirty = collect($serviceControl->getDirty())
                ->only(array_keys($before))
                ->all();

            $serviceControl->save();

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
        ]);

        $client = $this->serviceClient($serviceControl);
        $payload = [
            'id' => $serviceControl->id,
            'client_id' => $serviceControl->client_id,
            'service_name' => $serviceControl->service_name,
            'service_id' => $serviceControl->service_id,
            'is_bm' => (bool) $serviceControl->is_bm,
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
                    'service_type' => $this->normalizeServiceTypes($serviceControl->service_type),
                    'service_status' => $serviceControl->service_status,
                    'priority' => $serviceControl->priority,
                    'opened_at' => $this->formatDate($serviceControl->opened_at),
                    'open_days' => $serviceControl->open_days,
                ])
                ->all(),
        ];
    }

    private function standaloneOrderSummary(?Client $client = null): array
    {
        return [
            'id' => null,
            'name' => 'Standalone Service',
            'order_number' => null,
            'order_type' => null,
            'job_address' => null,
            'city' => null,
            'job_state' => null,
            'job_zip' => null,
            'address_label' => null,
            'today_date' => now()->format('Y-m-d'),
            'client' => $client ? $this->serializeServiceClient($client) : null,
            'company' => null,
            'supervisor' => null,
            'seller' => null,
            'owners' => [],
            'service_controls' => [],
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

    private function trackedValues(ServiceControl $serviceControl): array
    {
        return [
            'client_id' => $serviceControl->client_id,
            'service_name' => $serviceControl->service_name,
            'service_id' => $serviceControl->service_id,
            'is_bm' => (bool) $serviceControl->is_bm,
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
        $orderId = $serviceControl?->order_id ?? (((int) $request->input('order_id', 0)) ?: null);
        $closedAt = $status === ServiceControlStatusEnum::CLOSED->value
            ? ($executedDate ?: now()->format('Y-m-d'))
            : null;
        $isBm = $orderId ? $request->boolean('is_bm') : false;
        $clientId = $orderId ? null : $this->resolveStandaloneClientId($request, $serviceControl);
        $requesterType = $isBm ? null : $request->input('requester_type');
        $requesterId = $isBm ? null : $request->input('requester_id');
        $requesterRole = $isBm ? null : $request->input('requester_role');

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
            'is_bm' => $isBm,
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
            'eta_date' => $isBm ? null : $request->input('eta_date'),
            'parts_received_date' => $isBm ? null : $request->input('parts_received_date'),
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

    private function serviceCalendarDate(ServiceControl $serviceControl, string $status, string $timezone): ?Carbon
    {
        if ($status === ServiceControlStatusEnum::SCHEDULED->value && ! empty($serviceControl->scheduled_date)) {
            return Carbon::parse($serviceControl->scheduled_date, $timezone);
        }

        $history = $serviceControl->histories
            ->first(function (ServiceControlHistory $history) use ($status) {
                $newValues = is_array($history->new_values) ? $history->new_values : [];

                return ($newValues['service_status'] ?? null) === $status;
            });

        if ($history?->created_at) {
            return $history->created_at instanceof CarbonInterface
                ? $history->created_at->copy()->timezone($timezone)
                : Carbon::parse($history->created_at, $timezone);
        }

        if ($status === ServiceControlStatusEnum::COMPLETED->value && ! empty($serviceControl->executed_date)) {
            return Carbon::parse($serviceControl->executed_date, $timezone);
        }

        return $serviceControl->updated_at
            ? Carbon::parse($serviceControl->updated_at, $timezone)
            : null;
    }

    private function serviceCalendarStatuses(): array
    {
        return [
            ServiceControlStatusEnum::WAITING_FOR_PART->value,
            ServiceControlStatusEnum::READY_TO_SCHEDULE->value,
            ServiceControlStatusEnum::SCHEDULED->value,
            ServiceControlStatusEnum::COMPLETED->value,
            ServiceControlStatusEnum::CANCELED->value,
        ];
    }

    private function serviceStatusOptions(): array
    {
        return collect(ServiceControlStatusEnum::cases())
            ->reject(fn (ServiceControlStatusEnum $status) => $status === ServiceControlStatusEnum::CLOSED)
            ->map(fn (ServiceControlStatusEnum $status) => $status->value)
            ->values()
            ->all();
    }

    private function serviceCalendarStatusColor(string $status): string
    {
        return [
            ServiceControlStatusEnum::WAITING_FOR_PART->value => '#f97316',
            ServiceControlStatusEnum::READY_TO_SCHEDULE->value => '#0ea5e9',
            ServiceControlStatusEnum::SCHEDULED->value => '#facc15',
            ServiceControlStatusEnum::COMPLETED->value => '#22c55e',
            ServiceControlStatusEnum::CANCELED->value => '#ef4444',
        ][$status] ?? '#6b7280';
    }

    private function humanizeStatus(string $status): string
    {
        return ucwords(strtolower(str_replace('_', ' ', $status)));
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

        if ($order?->client) {
            $options->push($this->partyOption('client', $order->client->id, $order->client->name, 'client'));
        }

        if (! $order && $standaloneClient) {
            $options->push($this->partyOption('client', $standaloneClient->id, $standaloneClient->name, 'client'));
        }

        if (! $order) {
            User::query()
                ->select('id', 'name')
                ->where('status', StatusUserEnum::ACTIVE->value)
                ->role([RoleEnum::SUPERVISOR->value, RoleEnum::SERVICE_MANAGER->value, RoleEnum::SERVICE->value, RoleEnum::INSTALLER->value, RoleEnum::ACCOUNT_MANAGER->value])
                ->orderBy('name')
                ->get()
                ->each(function (User $user) use ($options) {
                    $role = $user->hasRole(RoleEnum::ACCOUNT_MANAGER->value)
                        ? 'account_manager'
                        : ($user->hasRole(RoleEnum::SERVICE_MANAGER->value)
                            ? 'service_manager'
                            : ($user->hasSupervisorOnlyAccess()
                                ? 'supervisor'
                                : ($user->hasRole(RoleEnum::SERVICE->value)
                                    ? 'service'
                                    : 'installer')));
                    $options->push($this->partyOption('user', $user->id, $user->name, $role));
                });

            return $options->unique('value')->values()->all();
        }

        User::query()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->role([RoleEnum::SUPERVISOR->value, RoleEnum::SERVICE_MANAGER->value, RoleEnum::SERVICE->value, RoleEnum::INSTALLER->value, RoleEnum::ACCOUNT_MANAGER->value])
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($options) {
                $role = $user->hasRole(RoleEnum::ACCOUNT_MANAGER->value)
                    ? 'account_manager'
                    : ($user->hasRole(RoleEnum::SERVICE_MANAGER->value)
                        ? 'service_manager'
                        : ($user->hasSupervisorOnlyAccess()
                            ? 'supervisor'
                            : ($user->hasRole(RoleEnum::SERVICE->value) ? 'service' : 'installer')));
                $options->push($this->partyOption('user', $user->id, $user->name, $role));
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
