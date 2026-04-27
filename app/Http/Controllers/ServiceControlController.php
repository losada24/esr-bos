<?php

namespace App\Http\Controllers;

use App\Enum\RoleEnum;
use App\Enum\ServiceControlClosureResultEnum;
use App\Enum\ServiceControlPriorityEnum;
use App\Enum\ServiceControlStatusEnum;
use App\Enum\ServiceControlTypeEnum;
use App\Http\Requests\StoreServiceControlRequest;
use App\Http\Requests\UpdateServiceControlRequest;
use App\Models\Order;
use App\Models\ServiceControl;
use App\Models\ServiceControlHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceControlController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ServiceControl::query()
            ->with([
                'order.client.companyContact',
                'order.client.companyContacts',
                'order.orderCompanyContacts.companyContact',
                'order.orderCompanyContacts.client',
                'order.supervisor:id,name',
                'creator:id,name',
                'updater:id,name',
            ]);

        if ($this->isOwnerRestricted($request->user())) {
            $query->whereHas('order', fn (Builder $builder) => $builder->accessibleToOwner($request->user()));
        }

        $text = trim((string) $request->query('search', ''));
        if ($text !== '') {
            $like = '%' . $text . '%';
            $query->where(function (Builder $builder) use ($like) {
                $builder
                    ->where('service_name', 'like', $like)
                    ->orWhere('service_id', 'like', $like)
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
        if ($status !== '') {
            $query->where('service_status', $status);
        }

        $priority = trim((string) $request->query('priority', ''));
        if ($priority !== '') {
            $query->where('priority', $priority);
        }

        $serviceControls = $query
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ServiceControl $serviceControl) => $this->serializeServiceControl($serviceControl))
            ->values();

        return Inertia::render('ServiceControl/Index', [
            'serviceControls' => $serviceControls,
            'filters' => [
                'search' => $text,
                'status' => $status,
                'priority' => $priority,
            ],
            'serviceTypeOptions' => array_column(ServiceControlTypeEnum::cases(), 'value'),
            'serviceStatusOptions' => array_column(ServiceControlStatusEnum::cases(), 'value'),
            'priorityOptions' => array_column(ServiceControlPriorityEnum::cases(), 'value'),
            'closureResultOptions' => array_column(ServiceControlClosureResultEnum::cases(), 'value'),
        ]);
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
        ]);
    }

    public function show(Request $request, ServiceControl $serviceControl): Response
    {
        $serviceControl->load([
            'order.client.companyContact',
            'order.client.companyContacts',
            'order.orderCompanyContacts.companyContact',
            'order.orderCompanyContacts.client',
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
        ]);
    }

    public function edit(Request $request, ServiceControl $serviceControl): Response
    {
        $serviceControl->load([
            'order.client.companyContact',
            'order.client.companyContacts',
            'order.orderCompanyContacts.companyContact',
            'order.orderCompanyContacts.client',
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
            'order.supervisor:id,name',
            'creator:id,name',
            'updater:id,name',
        ]);

        $payload = [
            'id' => $serviceControl->id,
            'service_name' => $serviceControl->service_name,
            'service_id' => $serviceControl->service_id,
            'service_type' => $serviceControl->service_type,
            'description' => $serviceControl->description,
            'requires_part' => (bool) $serviceControl->requires_part,
            'requested_parts' => (bool) $serviceControl->requested_parts,
            'parts_available' => (bool) $serviceControl->parts_available,
            'service_status' => $serviceControl->service_status,
            'priority' => $serviceControl->priority,
            'target_date' => optional($serviceControl->target_date)->format('Y-m-d'),
            'scheduled_date' => optional($serviceControl->scheduled_date)->format('Y-m-d'),
            'executed_date' => optional($serviceControl->executed_date)->format('Y-m-d'),
            'opened_at' => optional($serviceControl->opened_at)->format('Y-m-d'),
            'closed_at' => optional($serviceControl->closed_at)->format('Y-m-d'),
            'open_days' => $serviceControl->open_days,
            'closure_result' => $serviceControl->closure_result,
            'observations' => $serviceControl->observations,
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
            'service_type' => $serviceControl->service_type,
            'description' => $serviceControl->description,
            'requires_part' => (bool) $serviceControl->requires_part,
            'requested_parts' => (bool) $serviceControl->requested_parts,
            'parts_available' => (bool) $serviceControl->parts_available,
            'service_status' => $serviceControl->service_status,
            'priority' => $serviceControl->priority,
            'target_date' => optional($serviceControl->target_date)->format('Y-m-d'),
            'scheduled_date' => optional($serviceControl->scheduled_date)->format('Y-m-d'),
            'executed_date' => optional($serviceControl->executed_date)->format('Y-m-d'),
            'opened_at' => optional($serviceControl->opened_at)->format('Y-m-d'),
            'closed_at' => optional($serviceControl->closed_at)->format('Y-m-d'),
            'closure_result' => $serviceControl->closure_result,
            'observations' => $serviceControl->observations,
        ];
    }

    private function buildPayload(Request $request, ?ServiceControl $serviceControl): array
    {
        $status = (string) $request->input('service_status');
        $executedDate = $request->input('executed_date');
        $closedAt = $status === ServiceControlStatusEnum::CLOSED->value
            ? ($executedDate ?: now()->format('Y-m-d'))
            : null;

        return [
            'order_id' => $serviceControl?->order_id ?? (int) $request->input('order_id'),
            'service_name' => $request->input('service_name'),
            'service_id' => $request->input('service_id'),
            'service_type' => $request->input('service_type'),
            'description' => $request->input('description'),
            'requires_part' => $request->boolean('requires_part'),
            'requested_parts' => $request->boolean('requested_parts'),
            'parts_available' => $request->boolean('parts_available'),
            'service_status' => $status,
            'priority' => $request->input('priority'),
            'target_date' => $request->input('target_date'),
            'scheduled_date' => $request->input('scheduled_date'),
            'executed_date' => $executedDate,
            'opened_at' => $serviceControl?->opened_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'closed_at' => $closedAt,
            'closure_result' => $request->input('closure_result'),
            'observations' => $request->input('observations'),
            'created_by' => $serviceControl?->created_by ?? $request->user()?->id,
            'updated_by' => $request->user()?->id,
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
