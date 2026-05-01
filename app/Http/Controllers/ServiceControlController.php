<?php

namespace App\Http\Controllers;

use App\Enum\AreaEnum;
use App\Enum\BmInvoiceStatusEnum;
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
use Illuminate\Database\Eloquent\Builder;
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

    private function buildIndexData(Request $request, ?int $limit = null): array
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
                    ->orWhere('service_type', 'like', $like)
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
                'type' => $type,
            ],
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => array_column(ServiceControlStatusEnum::cases(), 'value'),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
            'bmInvoiceStatusOptions' => array_column(BmInvoiceStatusEnum::cases(), 'value'),
        ];
    }

    public function create(Request $request): RedirectResponse|Response
    {
        $orderId = (int) $request->query('order_id', 0);
        if ($orderId <= 0) {
            return redirect()->route('service-control.index')
                ->with('error', 'Select an order before creating a service control.');
        }

        $order = $this->loadOrderForServiceControl($orderId);
        $this->ensureCanAccessOrder($request->user(), $order);

        return Inertia::render('ServiceControl/Create', [
            'order' => $this->serializeOrderForServiceControl($order),
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => array_column(ServiceControlStatusEnum::cases(), 'value'),
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
            'creator:id,name',
            'updater:id,name',
            'histories.user:id,name',
        ]);

        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        return Inertia::render('ServiceControl/Show', [
            'serviceControl' => $this->serializeServiceControl($serviceControl, true),
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => array_column(ServiceControlStatusEnum::cases(), 'value'),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
            'bmInvoiceStatusOptions' => array_column(BmInvoiceStatusEnum::cases(), 'value'),
            'requesterOptions' => $this->buildRequesterOptions($serviceControl->order),
            'assigneeOptions' => $this->buildAssigneeOptions($serviceControl->order),
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
            'creator:id,name',
            'updater:id,name',
            'histories.user:id,name',
        ]);

        $this->ensureCanAccessServiceControl($request->user(), $serviceControl);

        return Inertia::render('ServiceControl/Edit', [
            'serviceControl' => $this->serializeServiceControl($serviceControl, true),
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => array_column(ServiceControlStatusEnum::cases(), 'value'),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
            'areaOptions' => array_column(AreaEnum::cases(), 'value'),
            'bmInvoiceStatusOptions' => array_column(BmInvoiceStatusEnum::cases(), 'value'),
            'requesterOptions' => $this->buildRequesterOptions($serviceControl->order),
            'assigneeOptions' => $this->buildAssigneeOptions($serviceControl->order),
        ]);
    }

    public function store(StoreServiceControlRequest $request): RedirectResponse
    {
        $order = $this->loadOrderForServiceControl((int) $request->input('order_id'));
        $this->ensureCanAccessOrder($request->user(), $order);

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
            'creator:id,name',
            'updater:id,name',
        ]);

        $payload = [
            'id' => $serviceControl->id,
            'service_name' => $serviceControl->service_name,
            'service_id' => $serviceControl->service_id,
            'is_bm' => (bool) $serviceControl->is_bm,
            'service_type' => $serviceControl->service_type,
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
                $serviceControl->order
            ),
            'target_date' => optional($serviceControl->target_date)->format('Y-m-d'),
            'service_created_date' => optional($serviceControl->service_created_date)->format('Y-m-d'),
            'service_id_requested_date' => optional($serviceControl->service_id_requested_date)->format('Y-m-d'),
            'eta_date' => optional($serviceControl->eta_date)->format('Y-m-d'),
            'parts_received_date' => optional($serviceControl->parts_received_date)->format('Y-m-d'),
            'part_delivered_date' => optional($serviceControl->part_delivered_date)->format('Y-m-d'),
            'scheduled_date' => optional($serviceControl->scheduled_date)->format('Y-m-d'),
            'executed_date' => optional($serviceControl->executed_date)->format('Y-m-d'),
            'opened_at' => optional($serviceControl->opened_at)->format('Y-m-d'),
            'closed_at' => optional($serviceControl->closed_at)->format('Y-m-d'),
            'open_days' => $serviceControl->open_days,
            'closure_result' => $serviceControl->closure_result,
            'observations' => $serviceControl->observations,
            'bm_quantity' => $serviceControl->bm_quantity,
            'bm_requested_date' => optional($serviceControl->bm_requested_date)->format('Y-m-d'),
            'bm_picked_up_by' => $serviceControl->bm_picked_up_by,
            'bm_pickup_date' => optional($serviceControl->bm_pickup_date)->format('Y-m-d'),
            'bm_invoice_number' => $serviceControl->bm_invoice_number,
            'bm_invoice_status' => $serviceControl->bm_invoice_status,
            'created_at' => optional($serviceControl->created_at)->toISOString(),
            'updated_at' => optional($serviceControl->updated_at)->toISOString(),
            'creator' => $serviceControl->creator ? [
                'id' => $serviceControl->creator->id,
                'name' => $serviceControl->creator->name,
            ] : null,
            'updater' => $serviceControl->updater ? [
                'id' => $serviceControl->updater->id,
                'name' => $serviceControl->updater->name,
            ] : null,
            'order' => $this->serializeOrderForServiceControl($serviceControl->order),
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
                    'service_type' => $serviceControl->service_type,
                    'service_status' => $serviceControl->service_status,
                    'priority' => $serviceControl->priority,
                    'opened_at' => optional($serviceControl->opened_at)->format('Y-m-d'),
                    'open_days' => $serviceControl->open_days,
                ])
                ->all(),
        ];
    }

    private function trackedValues(ServiceControl $serviceControl): array
    {
        return [
            'service_name' => $serviceControl->service_name,
            'service_id' => $serviceControl->service_id,
            'is_bm' => (bool) $serviceControl->is_bm,
            'service_type' => $serviceControl->service_type,
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
            'target_date' => optional($serviceControl->target_date)->format('Y-m-d'),
            'service_created_date' => optional($serviceControl->service_created_date)->format('Y-m-d'),
            'service_id_requested_date' => optional($serviceControl->service_id_requested_date)->format('Y-m-d'),
            'eta_date' => optional($serviceControl->eta_date)->format('Y-m-d'),
            'parts_received_date' => optional($serviceControl->parts_received_date)->format('Y-m-d'),
            'part_delivered_date' => optional($serviceControl->part_delivered_date)->format('Y-m-d'),
            'scheduled_date' => optional($serviceControl->scheduled_date)->format('Y-m-d'),
            'executed_date' => optional($serviceControl->executed_date)->format('Y-m-d'),
            'opened_at' => optional($serviceControl->opened_at)->format('Y-m-d'),
            'closed_at' => optional($serviceControl->closed_at)->format('Y-m-d'),
            'closure_result' => $serviceControl->closure_result,
            'observations' => $serviceControl->observations,
            'bm_quantity' => $serviceControl->bm_quantity,
            'bm_requested_date' => optional($serviceControl->bm_requested_date)->format('Y-m-d'),
            'bm_picked_up_by' => $serviceControl->bm_picked_up_by,
            'bm_pickup_date' => optional($serviceControl->bm_pickup_date)->format('Y-m-d'),
            'bm_invoice_number' => $serviceControl->bm_invoice_number,
            'bm_invoice_status' => $serviceControl->bm_invoice_status,
        ];
    }

    private function buildPayload(Request $request, ?ServiceControl $serviceControl): array
    {
        $status = (string) $request->input('service_status');
        $executedDate = $request->input('executed_date');
        $closedAt = $status === ServiceControlStatusEnum::CLOSED->value
            ? ($executedDate ?: now()->format('Y-m-d'))
            : null;
        $isBm = $request->boolean('is_bm');

        return [
            'order_id' => $serviceControl?->order_id ?? (int) $request->input('order_id'),
            'service_name' => $request->input('service_name'),
            'service_id' => $isBm ? null : $request->input('service_id'),
            'is_bm' => $isBm,
            'service_type' => $isBm ? null : $request->input('service_type'),
            'description' => $isBm ? null : $request->input('description'),
            'requires_part' => $isBm ? false : $request->boolean('requires_part'),
            'requested_parts' => $isBm ? false : $request->boolean('requested_parts'),
            'parts_available' => $isBm ? false : $request->boolean('parts_available'),
            'service_status' => $isBm ? null : $status,
            'priority' => $isBm ? null : $request->input('priority'),
            'cost' => $isBm ? null : $request->input('cost'),
            'area' => $isBm ? null : $request->input('area'),
            'requester_type' => $isBm ? null : $request->input('requester_type'),
            'requester_id' => $isBm ? null : $request->input('requester_id'),
            'requester_role' => $isBm ? null : $request->input('requester_role'),
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

    private function buildRequesterOptions(Order $order): array
    {
        $options = collect();

        if ($order->client) {
            $options->push($this->partyOption('client', $order->client->id, $order->client->name, 'client'));
        }

        if ($order->user) {
            $options->push($this->partyOption('user', $order->user->id, $order->user->name, 'seller'));
        }

        $order->owners->each(fn (User $owner) => $options->push($this->partyOption('user', $owner->id, $owner->name, 'seller')));

        if ($order->supervisor) {
            $options->push($this->partyOption('user', $order->supervisor->id, $order->supervisor->name, 'supervisor'));
        }

        User::query()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->role([RoleEnum::ACCOUNT_MANAGER->value, RoleEnum::SERVICE_MANAGER->value])
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($options) {
                $role = $user->hasRole(RoleEnum::SERVICE_MANAGER->value) ? 'service_manager' : 'account_manager';
                $options->push($this->partyOption('user', $user->id, $user->name, $role));
            });

        return $options->unique('value')->values()->all();
    }

    private function buildAssigneeOptions(Order $order): array
    {
        $options = collect();

        if ($order->client) {
            $options->push($this->partyOption('client', $order->client->id, $order->client->name, 'client'));
        }

        User::query()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->role(RoleEnum::SUPERVISOR->value)
            ->orderBy('name')
            ->get()
            ->each(fn (User $supervisor) => $options->push($this->partyOption('user', $supervisor->id, $supervisor->name, 'supervisor')));

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

    private function resolvePartyName(?string $type, mixed $id, ?Order $order): ?string
    {
        $id = (int) $id;

        if ($id <= 0 || ! $type) {
            return null;
        }

        if ($type === 'client') {
            if ((int) ($order?->client?->id ?? 0) === $id) {
                return $order?->client?->name;
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
        $this->ensureCanAccessOrder($user, $serviceControl->order);
    }
}
