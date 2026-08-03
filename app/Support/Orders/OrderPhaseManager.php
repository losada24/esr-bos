<?php

namespace App\Support\Orders;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\EstimateDeliveryInstallationDate;
use App\Mail\InstallationDateConfirmation;
use App\Mail\InstallationDateConfirmationClient;
use App\Models\Order;
use App\Models\OrderPhase;
use App\Models\User;
use App\Support\OrderClientEmailManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderPhaseManager
{
    private const EMAIL_ATTACHMENT_ROLES = [
        'supervisor',
        'service_manager',
        'installer',
        'account_manager',
    ];

    private const REPLANNED_REASON_VALUES = [
        'CLIENT',
        'PERMIT',
        'MATERIALS',
    ];

    private const ADVANCED_STATUSES = [
        'CONFIRMED',
        'EXECUTION',
        'SUPERVISION',
        'INSPECTION',
        'FINISH',
        'FINAL INSPECTION',
        'PENDING COLLECT',
        'COMPLETE',
    ];

    private const STATUS_PRIORITY = [
        'ON HOLD' => 100,
        'REPLANNED' => 90,
        'EXECUTION' => 80,
        'SUPERVISION' => 70,
        'INSPECTION' => 60,
        'FINAL INSPECTION' => 50,
        'PENDING COLLECT' => 45,
        'FINISH' => 40,
        'CONFIRMED' => 30,
        'DELIVERY CONFIRMED' => 25,
        'MATERIALS RECEIVED' => 20,
        'PLANNED' => 10,
    ];

    public function syncFromRequest(Order $order, Request $request): void
    {
        $installByPhases = $request->boolean('install_by_phases');

        if ($installByPhases && $order->childOrders()->exists()) {
            throw ValidationException::withMessages([
                'install_by_phases' => 'Parent orders cannot be installed by phases.',
            ]);
        }

        if (! $installByPhases) {
            $this->disablePhasesIfAllowed($order);
            $order->forceFill(['install_by_phases' => false])->save();
            return;
        }

        $order->forceFill(['install_by_phases' => true])->save();

        $phasesPayload = $request->input('phases', []);
        if (! is_array($phasesPayload) || count($phasesPayload) === 0) {
            $phasesPayload = [$this->defaultPhasePayload($order)];
        }

        $this->syncPhases($order, $phasesPayload);
        $this->syncOrderSummary($order);
    }

    public function updateCalendarPhase(OrderPhase $phase, Request $request): void
    {
        $this->ensureCanUpdatePhaseFromCalendar($phase, $request);
        $phase->loadMissing('order');

        if ($phase->order) {
            $this->syncAttachmentRoleTargets($request, $phase->order);
        }

        $before = $this->snapshot($phase);
        $payload = [
            'status' => $request->input('status', $phase->status),
            'replanned_reasons' => $this->normalizeReplannedReasons($request->input('replanned_reasons', $phase->replanned_reasons ?? []), $request->input('status', $phase->status)),
        ];

        if ($request->input('type_of_event') === ServiceEnum::DELIVERY->value) {
            $payload['delivery_date'] = $request->input('delivery_date', $request->input('start', $this->formatDate($phase->delivery_date)));
        } else {
            $payload['delivery_date'] = $request->input('delivery_date', $this->formatDate($phase->delivery_date));
            $payload['installation_date'] = $request->input('installation_date', $request->input('start', $this->formatDate($phase->installation_date)));
            $payload['installation_end_date'] = $request->input('installation_end_date', $request->input('end', $this->formatDate($phase->installation_end_date)));
        }

        foreach ($this->statusDateFields() as $field) {
            if ($request->has($field)) {
                $payload[$field] = $request->input($field);
            }
        }

        if ($request->has('supervisor_id')) {
            $payload['supervisor_id'] = $request->input('supervisor_id');
        }

        $phase->fill($payload);
        $phase->save();

        if ($request->has('installation_teams')) {
            $phase->installationTeams()->sync($this->normalizeIds($request->input('installation_teams', [])));
        }

        $phase->load('installationTeams.user', 'phaseProducts.orderProduct', 'supervisor', 'order.installationTeams.user', 'order.supervisor');
        $this->logIfChanged($phase, 'calendar_update', $before, $this->snapshot($phase));
        $this->sendPhaseEmailIfNeeded($phase, $before['status'] ?? null, $before['installation_date'] ?? null);
        $this->syncOrderSummary($phase->order);
    }

    public function syncOrderSummary(Order $order): void
    {
        if (! $order->install_by_phases) {
            return;
        }

        $phases = $order->phases()->get();
        if ($phases->isEmpty()) {
            return;
        }

        $dates = $phases->pluck('installation_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date));
        $deliveryDates = $phases->pluck('delivery_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date));
        $endDates = $phases
            ->map(fn (OrderPhase $phase) => $phase->installation_end_date ?: $phase->installation_date)
            ->filter()
            ->map(fn ($date) => Carbon::parse($date));

        $order->forceFill([
            'delivery_date' => $deliveryDates->isNotEmpty() ? $deliveryDates->min()->format('Y-m-d') : $order->delivery_date,
            'installation_date' => $dates->isNotEmpty() ? $dates->min()->format('Y-m-d') : null,
            'installation_end_date' => $endDates->isNotEmpty() ? $endDates->max()->format('Y-m-d') : null,
            'status' => $this->deriveStatus($phases),
        ])->save();
    }

    public function phaseCanBeRemoved(OrderPhase $phase): bool
    {
        return ! in_array($phase->status, self::ADVANCED_STATUSES, true)
            && $phase->logs()->where('action', 'email_sent')->doesntExist();
    }

    private function syncPhases(Order $order, array $phasesPayload): void
    {
        $existingIds = $order->phases()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $seenIds = [];
        $position = 1;

        foreach ($phasesPayload as $phasePayload) {
            if (! is_array($phasePayload)) {
                continue;
            }

            $phaseId = isset($phasePayload['id']) ? (int) $phasePayload['id'] : 0;
            $phase = $phaseId > 0
                ? $order->phases()->whereKey($phaseId)->firstOrFail()
                : new OrderPhase(['order_id' => $order->id]);

            $before = $phase->exists ? $this->snapshot($phase) : null;
            $status = (string) ($phasePayload['status'] ?? OrderStatusEnum::PLANNED->value);
            $this->validatePhaseDates($phasePayload, $position);

            $phase->fill([
                'position' => (int) ($phasePayload['position'] ?? $position),
                'name' => trim((string) ($phasePayload['name'] ?? '')) ?: 'Phase ' . $position,
                'status' => $status,
                'delivery_date' => $phasePayload['delivery_date'] ?? null,
                'installation_date' => $phasePayload['installation_date'] ?? null,
                'installation_end_date' => $phasePayload['installation_end_date'] ?? ($phasePayload['installation_date'] ?? null),
                'inspection_date' => $phasePayload['inspection_date'] ?? null,
                'finish_date' => $phasePayload['finish_date'] ?? null,
                'service_date' => $phasePayload['service_date'] ?? null,
                'pending_collect' => $phasePayload['pending_collect'] ?? null,
                'final_inspection_date' => $phasePayload['final_inspection_date'] ?? null,
                'complete_date' => $phasePayload['complete_date'] ?? null,
                'supervisor_id' => $phasePayload['supervisor_id'] ?? $order->supervisor_id,
                'hide_on_weekends' => (bool) ($phasePayload['hide_on_weekends'] ?? $order->hide_on_weekends),
                'replanned_reasons' => $this->normalizeReplannedReasons($phasePayload['replanned_reasons'] ?? [], $status),
                'notes' => $phasePayload['notes'] ?? null,
            ]);
            $phase->save();

            $teamIds = $this->normalizeIds($phasePayload['installation_teams'] ?? $order->installationTeams()->pluck('installation_teams.id')->all());
            $phase->installationTeams()->sync($teamIds);

            $this->syncPhaseProducts($order, $phase, is_array($phasePayload['products'] ?? null) ? $phasePayload['products'] : []);

            $phase->load('installationTeams.user', 'phaseProducts.orderProduct', 'supervisor');
            $this->logIfChanged(
                $phase,
                $before ? 'updated' : 'created',
                $before,
                $this->snapshot($phase)
            );
            $this->sendPhaseEmailIfNeeded($phase, $before['status'] ?? null, $before['installation_date'] ?? null);

            $seenIds[] = (int) $phase->id;
            $position++;
        }

        $removeIds = array_values(array_diff($existingIds, $seenIds));
        foreach ($removeIds as $removeId) {
            $phase = $order->phases()->whereKey($removeId)->first();
            if (! $phase) {
                continue;
            }
            if (! $this->phaseCanBeRemoved($phase)) {
                throw ValidationException::withMessages([
                    'phases' => "{$phase->name} cannot be removed because it is confirmed, advanced, or has email history.",
                ]);
            }
            if (count($seenIds) === 0) {
                throw ValidationException::withMessages([
                    'phases' => 'Install by phases requires at least one phase.',
                ]);
            }

            $before = $this->snapshot($phase);
            $phase->phaseProducts()->delete();
            $phase->installationTeams()->detach();
            $phase->delete();
            $this->log($phase, 'deleted', $before, null);
        }
    }

    private function syncPhaseProducts(Order $order, OrderPhase $phase, array $products): void
    {
        $orderProductIds = $order->orderProducts()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sync = [];

        foreach ($products as $product) {
            if (! is_array($product)) {
                continue;
            }

            $orderProductId = (int) ($product['order_product_id'] ?? 0);
            $qty = round((float) ($product['qty'] ?? 0), 2);
            if ($orderProductId <= 0 || $qty <= 0 || ! in_array($orderProductId, $orderProductIds, true)) {
                continue;
            }

            $sync[$orderProductId] = ['qty' => $qty];
        }

        $this->validateProductQuantities($order, $phase, $sync);

        $phase->phaseProducts()->delete();
        foreach ($sync as $orderProductId => $data) {
            $phase->phaseProducts()->create([
                'order_product_id' => $orderProductId,
                'qty' => $data['qty'],
            ]);
        }
    }

    private function validateProductQuantities(Order $order, OrderPhase $currentPhase, array $incoming): void
    {
        $totals = $order->orderProducts()->pluck('qty', 'id')
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (float) $qty]);

        $usedByOtherPhases = DB::table('order_phase_products')
            ->join('order_phases', 'order_phases.id', '=', 'order_phase_products.order_phase_id')
            ->where('order_phases.order_id', $order->id)
            ->where('order_phases.id', '!=', $currentPhase->id ?: 0)
            ->whereNull('order_phases.deleted_at')
            ->whereNull('order_phase_products.deleted_at')
            ->select('order_phase_products.order_product_id', DB::raw('SUM(order_phase_products.qty) as total_qty'))
            ->groupBy('order_phase_products.order_product_id')
            ->pluck('total_qty', 'order_product_id')
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (float) $qty]);

        foreach ($incoming as $orderProductId => $data) {
            $requested = (float) $data['qty'] + (float) ($usedByOtherPhases[$orderProductId] ?? 0);
            $available = (float) ($totals[$orderProductId] ?? 0);
            if ($requested - $available > 0.01) {
                throw ValidationException::withMessages([
                    'phases' => 'Phase product quantities cannot exceed the order product quantities.',
                ]);
            }
        }
    }

    private function disablePhasesIfAllowed(Order $order): void
    {
        $phases = $order->phases()->get();
        foreach ($phases as $phase) {
            if (! $this->phaseCanBeRemoved($phase)) {
                throw ValidationException::withMessages([
                    'install_by_phases' => 'Install by phases cannot be disabled because one or more phases are confirmed, advanced, or have email history.',
                ]);
            }
        }

        foreach ($phases as $phase) {
            $before = $this->snapshot($phase);
            $phase->phaseProducts()->delete();
            $phase->installationTeams()->detach();
            $phase->delete();
            $this->log($phase, 'archived', $before, null);
        }
    }

    private function deriveStatus(Collection $phases): string
    {
        if ($phases->every(fn (OrderPhase $phase) => $phase->status === OrderStatusEnum::COMPLETE->value)) {
            return OrderStatusEnum::COMPLETE->value;
        }

        $openPhases = $phases->reject(fn (OrderPhase $phase) => $phase->status === OrderStatusEnum::COMPLETE->value);
        return $openPhases
            ->sortByDesc(fn (OrderPhase $phase) => self::STATUS_PRIORITY[$phase->status] ?? 0)
            ->first()?->status ?? OrderStatusEnum::PLANNED->value;
    }

    private function defaultPhasePayload(Order $order): array
    {
        return [
            'position' => 1,
            'name' => 'Phase 1',
            'status' => $order->status ?: OrderStatusEnum::PLANNED->value,
            'delivery_date' => $this->formatDate($order->delivery_date),
            'installation_date' => $this->formatDate($order->installation_date),
            'installation_end_date' => $this->formatDate($order->installation_end_date) ?: $this->formatDate($order->installation_date),
            'inspection_date' => $this->formatDate($order->inspection_date),
            'finish_date' => $this->formatDate($order->finish_date),
            'service_date' => $this->formatDate($order->service_date),
            'pending_collect' => $this->formatDate($order->pending_collect),
            'final_inspection_date' => $this->formatDate($order->final_inspection_date),
            'complete_date' => $this->formatDate($order->complete_date),
            'supervisor_id' => $order->supervisor_id,
            'installation_teams' => $order->installationTeams()->pluck('installation_teams.id')->all(),
            'hide_on_weekends' => $order->hide_on_weekends,
            'products' => [],
        ];
    }

    private function normalizeIds(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => is_array($item) ? ($item['id'] ?? $item['value'] ?? null) : $item)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function statusDateFields(): array
    {
        return [
            'inspection_date',
            'finish_date',
            'service_date',
            'pending_collect',
            'final_inspection_date',
            'complete_date',
        ];
    }

    private function formatDate(mixed $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    private function normalizeReplannedReasons(mixed $rawReasons, ?string $status): ?array
    {
        if ($status !== OrderStatusEnum::REPLANNED->value || ! is_array($rawReasons)) {
            return null;
        }

        $normalized = collect($rawReasons)
            ->map(fn ($reason) => strtoupper(trim((string) $reason)))
            ->filter(fn ($reason) => in_array($reason, self::REPLANNED_REASON_VALUES, true))
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    private function ensureCanUpdatePhaseFromCalendar(OrderPhase $phase, Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($user->hasAnyRole([RoleEnum::ADMIN->value, RoleEnum::ACCOUNT_MANAGER->value])) {
            return;
        }

        $phase->loadMissing('order');
        $effectiveSupervisorId = $phase->supervisor_id ?: $phase->order?->supervisor_id;
        if ($user->hasSupervisorOnlyAccess() && (int) $effectiveSupervisorId === (int) $user->id) {
            return;
        }

        abort(403, 'You are not authorized to update this phase.');
    }

    private function snapshot(OrderPhase $phase): array
    {
        $phase->loadMissing('installationTeams.user', 'phaseProducts.orderProduct', 'supervisor');

        return [
            'name' => $phase->name,
            'position' => $phase->position,
            'status' => $phase->status,
            'delivery_date' => $this->formatDate($phase->delivery_date),
            'installation_date' => $this->formatDate($phase->installation_date),
            'installation_end_date' => $this->formatDate($phase->installation_end_date),
            'inspection_date' => $this->formatDate($phase->inspection_date),
            'finish_date' => $this->formatDate($phase->finish_date),
            'service_date' => $this->formatDate($phase->service_date),
            'pending_collect' => $this->formatDate($phase->pending_collect),
            'final_inspection_date' => $this->formatDate($phase->final_inspection_date),
            'complete_date' => $this->formatDate($phase->complete_date),
            'supervisor_id' => $phase->supervisor_id,
            'installation_team_ids' => $phase->installationTeams->pluck('id')->values()->all(),
            'products' => $phase->phaseProducts->map(fn ($product) => [
                'order_product_id' => $product->order_product_id,
                'qty' => (float) $product->qty,
            ])->values()->all(),
            'replanned_reasons' => $phase->replanned_reasons,
            'notes' => $phase->notes,
        ];
    }

    private function logIfChanged(OrderPhase $phase, string $action, ?array $before, ?array $after): void
    {
        if ($before === $after) {
            return;
        }

        $this->log($phase, $action, $before, $after);
    }

    private function log(OrderPhase $phase, string $action, ?array $before, ?array $after, ?string $notes = null): void
    {
        $phase->logs()->create([
            'order_id' => $phase->order_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'status' => $phase->status,
            'before' => $before,
            'after' => $after,
            'notes' => $notes,
        ]);
    }

    private function validatePhaseDates(array $phasePayload, int $position): void
    {
        $deliveryDate = $phasePayload['delivery_date'] ?? null;
        $installationDate = $phasePayload['installation_date'] ?? null;
        $installationEndDate = $phasePayload['installation_end_date'] ?? null;

        $messages = [];
        $phaseIndex = max(0, $position - 1);
        if (empty($deliveryDate)) {
            $messages["phases.{$phaseIndex}.delivery_date"] = "Phase {$position} delivery date is required.";
        }
        if (empty($installationDate)) {
            $messages["phases.{$phaseIndex}.installation_date"] = "Phase {$position} installation start date is required.";
        }
        if (empty($installationEndDate)) {
            $messages["phases.{$phaseIndex}.installation_end_date"] = "Phase {$position} installation end date is required.";
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        if (Carbon::parse($installationEndDate)->lt(Carbon::parse($installationDate))) {
            throw ValidationException::withMessages([
                'phases' => "Phase {$position} installation end date cannot be before installation start date.",
            ]);
        }
    }

    private function sendPhaseEmailIfNeeded(OrderPhase $phase, ?string $previousStatus, ?string $previousInstallationDate = null): void
    {
        $isNewlyScheduled = empty($previousInstallationDate) && ! empty($phase->installation_date);
        if ($previousStatus === $phase->status && ! $isNewlyScheduled) {
            return;
        }

        if (! in_array($phase->status, [
            OrderStatusEnum::PLANNED->value,
            OrderStatusEnum::CONFIRMED->value,
            OrderStatusEnum::RESCHEDULE->value,
        ], true)) {
            return;
        }

        if (! $phase->installation_date) {
            return;
        }

        $phase->loadMissing(
            'order.client.companyContact',
            'order.client.companyContacts',
            'order.orderCompanyContacts.client.companyContacts',
            'order.orderCompanyContacts.companyContact',
            'order.owners',
            'order.user',
            'order.attachmentRoleTargets',
            'order.supervisor',
            'order.installationTeams.user',
            'installationTeams.user',
            'supervisor'
        );

        $order = $phase->order;
        if (! $order) {
            return;
        }

        if ($phase->status === OrderStatusEnum::PLANNED->value) {
            $recipients = $order->owners->pluck('email')->all();
            if (! $order->do_not_send_email) {
                $clientEmail = app(OrderClientEmailManager::class)->resolveRecipient($order);
                if (! empty($clientEmail)) {
                    $recipients[] = $clientEmail;
                }
            }

            foreach ($this->uniqueEmails($recipients) as $email) {
                SendGmailEmail::dispatch($email, new EstimateDeliveryInstallationDate($order, $phase))->onQueue('emails');
            }
        }

        if (in_array($phase->status, [OrderStatusEnum::CONFIRMED->value, OrderStatusEnum::RESCHEDULE->value], true)) {
            foreach ($this->uniqueEmails($order->owners->pluck('email')->all()) as $email) {
                SendGmailEmail::dispatch($email, new InstallationDateConfirmationClient($order, false, [], $phase))->onQueue('emails');
            }

            if (! $order->do_not_send_email) {
                $clientEmail = app(OrderClientEmailManager::class)->resolveRecipient($order);
                if (! empty($clientEmail)) {
                    SendGmailEmail::dispatch($clientEmail, new InstallationDateConfirmationClient($order, true, [], $phase))->onQueue('emails');
                }
            }

            $supervisorEmail = optional($phase->supervisor ?? $order->supervisor)->email;
            if (! empty($supervisorEmail)) {
                SendGmailEmail::dispatch($supervisorEmail, new InstallationDateConfirmation($order, true, true, false, true, $this->selectedAttachmentIdsForRole($order, 'supervisor'), $phase))->onQueue('emails');
            }

            foreach ($this->uniqueEmails(User::role([RoleEnum::SERVICE_MANAGER->value])->pluck('email')->all()) as $email) {
                SendGmailEmail::dispatch($email, new InstallationDateConfirmation($order, true, true, false, true, $this->selectedAttachmentIdsForRole($order, 'service_manager'), $phase))->onQueue('emails');
            }

            $effectiveInstallationTeams = $phase->installationTeams->isNotEmpty()
                ? $phase->installationTeams
                : $order->installationTeams;
            $installerEmails = $effectiveInstallationTeams->map(fn ($team) => optional($team->user)->email)->all();
            foreach ($this->uniqueEmails($installerEmails) as $email) {
                SendGmailEmail::dispatch($email, new InstallationDateConfirmation($order, true, true, true, false, $this->selectedAttachmentIdsForRole($order, 'installer'), $phase))->onQueue('emails');
            }

            foreach ($this->uniqueEmails(User::role([RoleEnum::ACCOUNT_MANAGER->value])->pluck('email')->all()) as $email) {
                SendGmailEmail::dispatch($email, new InstallationDateConfirmation($order, true, true, true, false, $this->selectedAttachmentIdsForRole($order, 'account_manager'), $phase))->onQueue('emails');
            }
        }

        $phase->forceFill(['last_email_sent_at' => now()])->save();
        $this->log($phase, 'email_sent', null, $this->snapshot($phase));
    }

    private function selectedAttachmentIdsForRole(Order $order, string $role): array
    {
        $order->loadMissing('attachmentRoleTargets');

        return $order->attachmentRoleTargets
            ->where('role', $role)
            ->pluck('attachment_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function syncAttachmentRoleTargets(Request $request, Order $order): void
    {
        $user = $request->user();
        if (
            ! $user
            || (! $user->hasRole(RoleEnum::ADMIN->value) && ! $user->hasRole(RoleEnum::ACCOUNT_MANAGER->value))
        ) {
            return;
        }

        if (! $request->exists('attachment_role_targets')) {
            return;
        }

        $rawTargets = $request->input('attachment_role_targets', []);
        if (! is_array($rawTargets)) {
            $rawTargets = [];
        }

        $validAttachmentIdMap = $order->attachments()
            ->pluck('attachments.id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();

        $rows = [];
        foreach (self::EMAIL_ATTACHMENT_ROLES as $role) {
            $roleTargets = $rawTargets[$role] ?? [];
            if (! is_array($roleTargets)) {
                continue;
            }

            $attachmentIds = collect($roleTargets)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => isset($validAttachmentIdMap[$id]))
                ->unique()
                ->values()
                ->all();

            foreach ($attachmentIds as $attachmentId) {
                $rows[] = [
                    'attachment_id' => $attachmentId,
                    'role' => $role,
                    'created_by' => $user->id,
                ];
            }
        }

        $order->attachmentRoleTargets()->delete();
        if (! empty($rows)) {
            $order->attachmentRoleTargets()->createMany($rows);
        }

        $order->unsetRelation('attachmentRoleTargets');
        $order->load('attachmentRoleTargets');
    }

    private function uniqueEmails(array $emails): array
    {
        return collect($emails)
            ->filter(fn ($email) => is_string($email) && trim($email) !== '')
            ->map(fn ($email) => trim((string) $email))
            ->unique(fn ($email) => mb_strtolower($email))
            ->values()
            ->all();
    }
}
