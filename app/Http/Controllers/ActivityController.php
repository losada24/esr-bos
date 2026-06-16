<?php

namespace App\Http\Controllers;

use App\Enum\RoleEnum;
use App\Enum\StatusUserEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\CrmEventInvitation;
use App\Models\Client;
use App\Models\CrmCall;
use App\Models\CrmEvent;
use App\Models\CrmNotification;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use App\Services\CrmNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    private const EVENT_COLOR = '#2563eb';
    private const EVENT_CLOSED_COLOR = '#2563eb';
    private const EVENT_CANCELLED_COLOR = '#dc2626';
    private const CALL_COLOR = '#7c3aed';

    public function index(Request $request): Response
    {
        return Inertia::render('Activities/Index', [
            'events' => $this->eventRows($this->visibleEventsQuery($request->user())->latest('starts_at')->limit(100)->get()),
            'calls' => $this->callRows($this->visibleCallsQuery($request->user())->latest('call_start_time')->limit(100)->get()),
            'users' => $this->assignableUsers($request->user()),
            'canManageAll' => $this->canManageAllActivities($request->user()),
        ]);
    }

    public function calendarEvents(Request $request, int $year, int $month): JsonResponse
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $rangeStart = Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth()->subWeek()->startOfDay();
        $rangeEnd = Carbon::createFromDate($year, $month, 1, $timezone)->endOfMonth()->addWeek()->endOfDay();
        $types = collect(explode(',', (string) $request->query('types', 'events,calls')))
            ->map(fn (string $type) => trim($type))
            ->filter()
            ->values();
        $ownership = (string) $request->query('ownership', 'all');
        $statusFilter = (string) $request->query('status', 'open');
        $now = Carbon::now($timezone);

        $eventsQuery = $this->visibleEventsQuery($request->user())
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd]);

        if ($ownership === 'mine') {
            $eventsQuery->where('host_id', $request->user()->id);
        }

        if ($statusFilter === 'open') {
            $eventsQuery
                ->where('status', '!=', 'Cancelled')
                ->where('ends_at', '>=', $now);
        } elseif ($statusFilter === 'closed') {
            $eventsQuery->where(function (Builder $query) use ($now) {
                $query->where('status', 'Cancelled')
                    ->orWhere('ends_at', '<', $now);
            });
        }

        $events = $types->contains('events')
            ? $eventsQuery
            ->get()
            ->map(function (CrmEvent $event) use ($timezone, $now) {
                $start = $event->starts_at ? Carbon::parse($event->starts_at, $timezone) : null;
                $end = $event->ends_at ? Carbon::parse($event->ends_at, $timezone) : null;
                $activityStatus = $this->eventStatus($event, $now);

                return [
                    'id' => 'event-' . $event->id,
                    'title' => $event->title,
                    'text' => $event->title,
                    'start' => optional($start)->format(\DateTimeInterface::ATOM),
                    'end' => optional($end)->format(\DateTimeInterface::ATOM),
                    'color' => $this->eventColor($activityStatus),
                    'type' => 'event',
                    'activity_status' => $activityStatus,
                    'order_id' => $event->order_id,
                    'client_id' => $event->client_id,
                    'tooltip' => $event->title,
                ];
            })
            : collect();

        $callsQuery = $this->visibleCallsQuery($request->user())
            ->whereBetween('call_start_time', [$rangeStart, $rangeEnd]);

        if ($ownership === 'mine') {
            $callsQuery->where('owner_id', $request->user()->id);
        }

        if ($statusFilter === 'open') {
            $callsQuery->where('outgoing_call_status', 'Scheduled');
        } elseif ($statusFilter === 'closed') {
            $callsQuery->whereIn('outgoing_call_status', ['Completed', 'Cancelled']);
        }

        $calls = $types->contains('calls')
            ? $callsQuery
            ->get()
            ->map(function (CrmCall $call) use ($timezone) {
                $start = $call->call_start_time ? Carbon::parse($call->call_start_time, $timezone) : null;
                $end = $start ? $start->copy()->addMinutes($call->call_duration_minutes ?: 30) : null;
                $title = 'Call: ' . $call->to_from;

                return [
                    'id' => 'call-' . $call->id,
                    'title' => $title,
                    'text' => $title,
                    'start' => optional($start)->format(\DateTimeInterface::ATOM),
                    'end' => optional($end)->format(\DateTimeInterface::ATOM),
                    'color' => $call->outgoing_call_status === 'Cancelled' ? self::EVENT_CANCELLED_COLOR : self::CALL_COLOR,
                    'type' => 'call',
                    'activity_status' => $call->outgoing_call_status,
                    'order_id' => $call->order_id,
                    'client_id' => $call->client_id,
                    'tooltip' => $call->to_from,
                ];
            })
            : collect();

        return response()->json($events->concat($calls)->values());
    }

    public function storeEvent(Request $request): JsonResponse
    {
        $data = $this->validateEvent($request);

        $order = $this->authorizedOrder($request, $data['order_id'] ?? null);
        $hostId = (int) $request->user()->id;
        $sendInvitation = $this->shouldSendEventInvitation($data, true);
        unset($data['send_invitation']);

        $event = CrmEvent::create([
            ...$data,
            'host_id' => $hostId,
            'client_id' => $data['client_id'] ?? $order?->client_id,
            'status' => $data['status'] ?? 'Scheduled',
            'is_repeating' => (bool) ($data['is_repeating'] ?? false),
            'reminder_enabled' => (bool) ($data['reminder_enabled'] ?? false),
            'online_meeting' => (bool) ($data['online_meeting'] ?? false),
        ]);
        $event = $event->fresh(['host', 'order', 'client']);
        $this->recordEventNotifications($request, $event, 'created');
        $this->sendEventInvitationsIfRequested($event, $sendInvitation);

        return response()->json(['event' => $this->eventRow($event)], 201);
    }

    public function showEvent(Request $request, CrmEvent $event): JsonResponse
    {
        $event = $this->visibleEventsQuery($request->user())->findOrFail($event->id);

        return response()->json(['event' => $this->eventRow($event)]);
    }

    public function eventNotes(Request $request, CrmEvent $event): JsonResponse
    {
        $event = $this->visibleEventsQuery($request->user())->findOrFail($event->id);

        return response()->json($this->noteRows($event->notes()->with(['user:id,name', 'attachments'])->latest()->get()));
    }

    public function storeEventNote(Request $request, CrmEvent $event): JsonResponse
    {
        $event = $this->visibleEventsQuery($request->user())->findOrFail($event->id);
        $data = $this->validateNote($request);

        $note = $event->notes()->create([
            'content' => $data['content'],
            'type' => 'event_note',
            'user_id' => $request->user()->id,
        ]);

        $note->load(['user:id,name', 'attachments']);

        return response()->json($this->noteRow($note), 201);
    }

    public function updateEventNote(Request $request, CrmEvent $event, Note $note): JsonResponse
    {
        $event = $this->visibleEventsQuery($request->user())->findOrFail($event->id);
        $this->authorizeActivityNote($request, $note, CrmEvent::class, $event->id);
        $data = $this->validateNote($request, true);

        $note->update([
            'content' => $data['content'] ?? $note->content,
            'type' => 'event_note',
        ]);
        $note->load(['user:id,name', 'attachments']);

        return response()->json($this->noteRow($note));
    }

    public function destroyEventNote(Request $request, CrmEvent $event, Note $note): JsonResponse
    {
        $event = $this->visibleEventsQuery($request->user())->findOrFail($event->id);
        $this->authorizeActivityNote($request, $note, CrmEvent::class, $event->id);
        $note->delete();

        return response()->json(null, 204);
    }

    public function updateEvent(Request $request, CrmEvent $event): JsonResponse
    {
        $event = $this->visibleEventsQuery($request->user())->findOrFail($event->id);
        abort_unless($this->canManageAllActivities($request->user()) || $event->host_id === $request->user()->id, 403);
        $data = $this->validateEvent($request);
        $order = $this->authorizedOrder($request, $data['order_id'] ?? null);
        $hostId = $this->canManageAllActivities($request->user())
            ? (int) ($data['host_id'] ?? $event->host_id)
            : (int) $request->user()->id;
        $sendInvitation = $this->shouldSendEventInvitation($data);
        unset($data['send_invitation']);

        $event->update([
            ...$data,
            'host_id' => $hostId,
            'client_id' => $data['client_id'] ?? $order?->client_id,
            'status' => $data['status'] ?? 'Scheduled',
            'is_repeating' => (bool) ($data['is_repeating'] ?? false),
            'reminder_enabled' => (bool) ($data['reminder_enabled'] ?? false),
            'online_meeting' => (bool) ($data['online_meeting'] ?? false),
        ]);
        $event = $event->fresh(['host', 'order', 'client']);
        $this->recordEventNotifications($request, $event, 'updated');
        $this->sendEventInvitationsIfRequested($event, $sendInvitation);

        return response()->json(['event' => $this->eventRow($event)]);
    }

    public function storeCall(Request $request): JsonResponse
    {
        $data = $this->validateCall($request);

        $order = !empty($data['order_id']) ? $this->authorizedOrder($request, $data['order_id']) : null;
        $client = !$order && !empty($data['client_id']) ? $this->authorizedClient($request, $data['client_id']) : null;
        if (!$order && !$client && $this->requiresAssignedOrder($request->user())) {
            abort(422, 'This user must select an assigned contact or order.');
        }
        $ownerId = $this->canManageAllActivities($request->user())
            ? (int) ($data['owner_id'] ?? $request->user()->id)
            : (int) $request->user()->id;

        $call = CrmCall::create([
            ...$data,
            'owner_id' => $ownerId,
            'client_id' => $order?->client_id ?? $client?->id ?? ($data['client_id'] ?? null),
            'reminder_enabled' => (bool) ($data['reminder_enabled'] ?? false),
        ]);
        $call = $call->fresh(['owner', 'order', 'client']);
        $this->recordCallNotifications($request, $call, 'created');

        return response()->json(['call' => $this->callRow($call)], 201);
    }

    public function showCall(Request $request, CrmCall $call): JsonResponse
    {
        $call = $this->visibleCallsQuery($request->user())->findOrFail($call->id);

        return response()->json(['call' => $this->callRow($call)]);
    }

    public function callNotes(Request $request, CrmCall $call): JsonResponse
    {
        $call = $this->visibleCallsQuery($request->user())->findOrFail($call->id);

        return response()->json($this->noteRows($call->notes()->with(['user:id,name', 'attachments'])->latest()->get()));
    }

    public function storeCallNote(Request $request, CrmCall $call): JsonResponse
    {
        $call = $this->visibleCallsQuery($request->user())->findOrFail($call->id);
        $data = $this->validateNote($request);

        $note = $call->notes()->create([
            'content' => $data['content'],
            'type' => 'call_note',
            'user_id' => $request->user()->id,
        ]);

        $note->load(['user:id,name', 'attachments']);

        return response()->json($this->noteRow($note), 201);
    }

    public function updateCallNote(Request $request, CrmCall $call, Note $note): JsonResponse
    {
        $call = $this->visibleCallsQuery($request->user())->findOrFail($call->id);
        $this->authorizeActivityNote($request, $note, CrmCall::class, $call->id);
        $data = $this->validateNote($request, true);

        $note->update([
            'content' => $data['content'] ?? $note->content,
            'type' => 'call_note',
        ]);
        $note->load(['user:id,name', 'attachments']);

        return response()->json($this->noteRow($note));
    }

    public function destroyCallNote(Request $request, CrmCall $call, Note $note): JsonResponse
    {
        $call = $this->visibleCallsQuery($request->user())->findOrFail($call->id);
        $this->authorizeActivityNote($request, $note, CrmCall::class, $call->id);
        $note->delete();

        return response()->json(null, 204);
    }

    public function updateCall(Request $request, CrmCall $call): JsonResponse
    {
        $call = $this->visibleCallsQuery($request->user())->findOrFail($call->id);
        $data = $this->validateCall($request);
        $order = !empty($data['order_id']) ? $this->authorizedOrder($request, $data['order_id']) : null;
        $client = !$order && !empty($data['client_id']) ? $this->authorizedClient($request, $data['client_id']) : null;
        if (!$order && !$client && $this->requiresAssignedOrder($request->user())) {
            abort(422, 'This user must select an assigned contact or order.');
        }
        $ownerId = $this->canManageAllActivities($request->user())
            ? (int) ($data['owner_id'] ?? $call->owner_id)
            : (int) $request->user()->id;

        $call->update([
            ...$data,
            'owner_id' => $ownerId,
            'client_id' => $order?->client_id ?? $client?->id ?? ($data['client_id'] ?? null),
            'reminder_enabled' => (bool) ($data['reminder_enabled'] ?? false),
        ]);
        $call = $call->fresh(['owner', 'order', 'client']);
        $this->recordCallNotifications($request, $call, 'updated');

        return response()->json(['call' => $this->callRow($call)]);
    }

    public function context(Request $request): JsonResponse
    {
        $orderId = $request->integer('order_id') ?: null;
        $clientId = $request->integer('client_id') ?: null;
        $order = null;
        $client = null;

        if ($orderId) {
            $order = $this->authorizedOrder($request, $orderId);
            $order->load(['client:id,name,phone,email', 'owners:id,name', 'user:id,name']);
            $client = $order->client;
        } elseif ($clientId) {
            $client = Client::query()->select('id', 'name', 'phone', 'email')->findOrFail($clientId);
        }

        return response()->json([
            'order' => $order ? $this->orderOption($order) : null,
            'client' => $client ? $this->clientOption($client) : null,
        ]);
    }

    public function searchOrders(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $like = '%' . trim($data['q']) . '%';

        $orders = $this->visibleOrdersQuery($request->user())
            ->with(['client:id,name,phone,email', 'owners:id,name', 'user:id,name'])
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('order_number', 'like', $like)
                    ->orWhereHas('client', function (Builder $clientQuery) use ($like) {
                        $clientQuery->where('name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            })
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return response()->json(['data' => $orders->map(fn (Order $order) => $this->orderOption($order))->values()]);
    }

    public function searchClients(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $term = trim($data['q']);
        if ($term === '') {
            return response()->json(['data' => []]);
        }

        $digits = preg_replace('/\D+/', '', $term) ?? '';
        $like = '%' . $term . '%';

        $clients = $this->visibleClientsQuery($request->user())
            ->select('id', 'name', 'phone', 'email')
            ->where(function (Builder $query) use ($like, $digits) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);

                if ($digits !== '') {
                    $query->orWhere('phone', 'like', '%' . $digits . '%');
                } else {
                    $query->orWhere('phone', 'like', $like);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json(['data' => $clients->map(fn (Client $client) => $this->clientOption($client))->values()]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $like = '%' . trim($data['q']) . '%';

        $users = User::query()
            ->select('id', 'name', 'email')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->whereNotNull('email')
            ->where(function (Builder $query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values();

        return response()->json(['data' => $users]);
    }

    private function visibleEventsQuery(?User $user): Builder
    {
        return CrmEvent::query()
            ->with(['host:id,name', 'order:id,name,order_number,client_id', 'client:id,name,phone,email'])
            ->when(!$this->canManageAllActivities($user), function (Builder $query) use ($user) {
                if (!$user) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->where(function (Builder $innerQuery) use ($user) {
                    $innerQuery->where('host_id', $user->id);

                    if ($user->email) {
                        $innerQuery->orWhereJsonContains('participants', strtolower($user->email));
                    }
                });
            });
    }

    private function visibleCallsQuery(?User $user): Builder
    {
        return CrmCall::query()
            ->with(['owner:id,name', 'order:id,name,order_number,client_id', 'client:id,name,phone,email'])
            ->when(!$this->canManageAllActivities($user), function (Builder $query) use ($user) {
                if (!$user) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->where('owner_id', $user->id);
            });
    }

    private function visibleOrdersQuery(?User $user): Builder
    {
        return $this->applyRestrictedOrderVisibility(Order::query(), $user);
    }

    private function visibleClientsQuery(?User $user): Builder
    {
        return Client::query()
            ->when(!$this->canManageAllActivities($user), function (Builder $query) use ($user) {
                $query->whereHas('orders', fn (Builder $orderQuery) => $this->applyRestrictedOrderVisibility($orderQuery, $user));
            });
    }

    private function applyRestrictedOrderVisibility(Builder $query, ?User $user): Builder
    {
        if ($this->canManageAllActivities($user)) {
            return $query;
        }

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(RoleEnum::OWNER->value)) {
            return $query->accessibleToOwner($user);
        }

        if ($user->hasSupervisorOnlyAccess()) {
            return $query->where('supervisor_id', $user->id);
        }

        return $query->where(function (Builder $innerQuery) use ($user) {
            $innerQuery->where('user_id', $user->id)
                ->orWhereHas('owners', fn (Builder $ownerQuery) => $ownerQuery->where('users.id', $user->id))
                ->orWhere('supervisor_id', $user->id);
        });
    }

    private function authorizedOrder(Request $request, ?int $orderId): ?Order
    {
        if (!$orderId) {
            if ($this->requiresAssignedOrder($request->user())) {
                abort(422, 'This user must select an assigned order.');
            }

            return null;
        }

        return $this->visibleOrdersQuery($request->user())->findOrFail($orderId);
    }

    private function authorizedClient(Request $request, ?int $clientId): ?Client
    {
        if (!$clientId) {
            return null;
        }

        return $this->visibleClientsQuery($request->user())->findOrFail($clientId);
    }

    private function requiresAssignedOrder(?User $user): bool
    {
        if (!$user || $this->canManageAllActivities($user)) {
            return false;
        }

        return $user->hasAnyRole([
            RoleEnum::OWNER->value,
            RoleEnum::SUPERVISOR->value,
        ]);
    }

    private function canManageAllActivities(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::ACCOUNT_MANAGER->value,
            RoleEnum::SERVICE_MANAGER->value,
            RoleEnum::OWNER_ADMIN->value,
            RoleEnum::FRONTDESK_ADMIN->value,
        ]);
    }

    private function assignableUsers(?User $user): array
    {
        if (!$this->canManageAllActivities($user)) {
            return [[
                'id' => $user?->id,
                'name' => $user?->name,
                'email' => $user?->email,
            ]];
        }

        return User::query()
            ->select('id', 'name', 'email')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])
            ->values()
            ->all();
    }

    private function validateEvent(Request $request): array
    {
        $request->merge([
            'participants' => $this->normalizeEventParticipants($request->input('participants')),
        ]);

        $data = $request->validate([
            'host_id' => ['nullable', 'integer', 'exists:users,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['nullable', Rule::in(['Scheduled', 'Cancelled'])],
            'is_repeating' => ['boolean'],
            'reminder_enabled' => ['boolean'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'location' => ['nullable', 'string', 'max:255'],
            'online_meeting' => ['boolean'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'participants' => ['nullable', 'array'],
            'participants.*' => ['nullable'],
            'send_invitation' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $data['participants'] = $this->normalizeEventParticipants($data['participants'] ?? []);

        return $data;
    }

    private function normalizeEventParticipants(mixed $participants): array
    {
        if (is_string($participants)) {
            $participants = explode(',', $participants);
        }

        if (!is_array($participants)) {
            return [];
        }

        return collect($participants)
            ->map(function ($participant) {
                if (is_array($participant)) {
                    $participant = $participant['email'] ?? $participant['value'] ?? null;
                }

                if (is_object($participant)) {
                    $participant = $participant->email ?? $participant->value ?? null;
                }

                if (!is_string($participant)) {
                    return null;
                }

                preg_match('/[^\s@,;]+@[^\s@,;]+\.[^\s@,;]+/', $participant, $matches);

                return isset($matches[0]) ? strtolower($matches[0]) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function validateCall(Request $request): array
    {
        return $request->validate([
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'to_from' => ['required', 'string', 'max:255'],
            'call_start_time' => ['required', 'date'],
            'call_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'reminder_enabled' => ['boolean'],
            'reminder_minutes_before' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'call_type' => ['required', Rule::in(['Outbound', 'Inbound'])],
            'outgoing_call_status' => ['required', 'string', 'max:120'],
            'call_purpose' => ['nullable', 'string', 'max:255'],
            'call_agenda' => ['nullable', 'string'],
        ]);
    }

    private function validateNote(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'content' => $partial ? ['sometimes', 'required', 'string'] : ['required', 'string'],
        ]);
    }

    private function authorizeActivityNote(Request $request, Note $note, string $noteableType, int $noteableId): void
    {
        abort_unless(
            $note->noteable_type === $noteableType && (int) $note->noteable_id === $noteableId,
            404
        );

        abort_if($note->user_id !== $request->user()->id, 403);
    }

    private function noteRows($notes): array
    {
        return $notes->map(fn (Note $note) => $this->noteRow($note))->values()->all();
    }

    private function noteRow(Note $note, ?string $contextLabel = null): array
    {
        return [
            'id' => $note->id,
            'content' => $note->content,
            'type' => $note->type,
            'context_label' => $contextLabel,
            'created_at' => optional($note->created_at)->toISOString(),
            'user' => $note->user ? ['name' => $note->user->name] : null,
            'can' => [
                'update' => $note->user_id === auth()->id(),
                'delete' => $note->user_id === auth()->id(),
            ],
            'audio_attachments' => $note->attachments
                ->where('file_type', \App\Enum\AttachmentsFileTypeEnum::NOTE_AUDIO->value)
                ->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'mime_type' => $attachment->mime_type,
                    'duration_seconds' => $attachment->duration_seconds,
                    'transcription_status' => $attachment->transcription_status,
                    'url' => route('notes.audio.show', ['note' => $note->id, 'attachment' => $attachment->id]),
                    'created_at' => optional($attachment->created_at)->toISOString(),
                    'can' => [
                        'delete' => $attachment->user_id === auth()->id() || $note->user_id === auth()->id(),
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    private function eventRows($events): array
    {
        return $events->map(fn (CrmEvent $event) => $this->eventRow($event))->values()->all();
    }

    private function eventRow(CrmEvent $event): array
    {
        $activityStatus = $this->eventStatus($event, Carbon::now((string) config('app.timezone', 'UTC')));
        $startsAt = $this->asCarbon($event->starts_at);
        $endsAt = $this->asCarbon($event->ends_at);

        return [
            'id' => $event->id,
            'host_id' => $event->host_id,
            'order_id' => $event->order_id,
            'client_id' => $event->client_id,
            'title' => $event->title,
            'from' => optional($startsAt)->format('M d, Y h:i A'),
            'to' => optional($endsAt)->format('M d, Y h:i A'),
            'starts_at' => optional($startsAt)->format(\DateTimeInterface::ATOM),
            'ends_at' => optional($endsAt)->format(\DateTimeInterface::ATOM),
            'status' => $activityStatus,
            'status_value' => $event->status ?? 'Scheduled',
            'status_color' => $this->eventColor($activityStatus),
            'is_inactive' => in_array($activityStatus, ['Closed', 'Cancelled'], true),
            'related_to' => $event->order?->name ?? $event->client?->name,
            'order' => $event->order ? $this->orderOption($event->order) : null,
            'client' => $event->client ? $this->clientOption($event->client) : null,
            'host' => $event->host?->name,
            'reminder_enabled' => (bool) $event->reminder_enabled,
            'reminder_minutes_before' => $event->reminder_minutes_before,
            'location' => $event->location,
            'online_meeting' => (bool) $event->online_meeting,
            'meeting_link' => $event->meeting_link,
            'participants' => $event->participants ?? [],
            'description' => $event->description,
        ];
    }

    private function sendEventInvitationsIfRequested(CrmEvent $event, bool $sendInvitation): void
    {
        if (!$sendInvitation) {
            return;
        }

        $emails = collect($event->participants ?? [])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values();

        foreach ($emails as $email) {
            SendGmailEmail::dispatch($email, new CrmEventInvitation($event))->onQueue('emails');
        }
    }

    private function recordEventNotifications(Request $request, CrmEvent $event, string $action): void
    {
        $actor = $request->user();
        $event->loadMissing(['host:id,name,email', 'order:id,name', 'client:id,name']);
        $title = $event->title ?: 'Event';
        $url = route('activities.index', ['mode' => 'event', 'event_id' => $event->id]);

        $this->createNotification(
            $event->host_id,
            $actor?->id,
            CrmNotification::TYPE_FEED,
            $action === 'created' ? 'Event created' : 'Event updated',
            trim(($actor?->name ?? 'Someone') . ' ' . $action . ' event ' . $title),
            $event,
            ['url' => $url]
        );

        foreach ($this->eventParticipantUsers($event) as $participant) {
            if ((int) $participant->id === (int) $event->host_id && $action === 'created') {
                continue;
            }

            $this->createNotification(
                $participant->id,
                $actor?->id,
                CrmNotification::TYPE_SYSTEM,
                $action === 'created' ? 'You were invited to an event' : 'Event updated',
                trim(($actor?->name ?? 'Someone') . ' ' . ($action === 'created' ? 'invited you to ' : 'updated ') . $title),
                $event,
                ['url' => $url]
            );
        }

        $this->syncEventReminderNotifications($event, $url);

        if ($event->order) {
            app(CrmNotificationService::class)->recordOrderFeed(
                $event->order,
                $actor,
                $action === 'created' ? 'Event added to order' : 'Order event updated',
                trim(($actor?->name ?? 'Someone') . ' ' . $action . ' event ' . $title . ' on order ' . ($event->order?->name ?? ('#' . $event->order_id)))
            );
        }
    }

    private function recordCallNotifications(Request $request, CrmCall $call, string $action): void
    {
        $actor = $request->user();
        $call->loadMissing(['owner:id,name,email', 'order:id,name', 'client:id,name']);
        $title = 'Call: ' . $call->to_from;
        $url = route('activities.index', ['mode' => 'call', 'call_id' => $call->id]);

        $this->createNotification(
            $call->owner_id,
            $actor?->id,
            CrmNotification::TYPE_FEED,
            $action === 'created' ? 'Call created' : 'Call updated',
            trim(($actor?->name ?? 'Someone') . ' ' . $action . ' ' . $title),
            $call,
            ['url' => $url]
        );

        $this->syncCallReminderNotifications($call, $url);

        if ($call->order) {
            app(CrmNotificationService::class)->recordOrderFeed(
                $call->order,
                $actor,
                $action === 'created' ? 'Call added to order' : 'Order call updated',
                trim(($actor?->name ?? 'Someone') . ' ' . $action . ' ' . $title . ' on order ' . ($call->order?->name ?? ('#' . $call->order_id)))
            );
        }
    }

    private function syncEventReminderNotifications(CrmEvent $event, string $url): void
    {
        CrmNotification::query()
            ->where('type', CrmNotification::TYPE_REMINDER)
            ->where('notifiable_type', CrmEvent::class)
            ->where('notifiable_id', $event->id)
            ->whereNull('read_at')
            ->delete();

        if (!$event->reminder_enabled || !$event->starts_at) {
            return;
        }

        $dueAt = $this->asCarbon($event->starts_at)?->copy()->subMinutes((int) ($event->reminder_minutes_before ?? 15));
        if (!$dueAt) {
            return;
        }

        $startsAt = $this->formatReminderStartsAt($event->starts_at);
        $body = trim($event->title . ' starts' . ($startsAt ? ' on ' . $startsAt : ''));

        foreach ($this->eventReminderUsers($event) as $user) {
            $this->createNotification(
                $user->id,
                $event->host_id,
                CrmNotification::TYPE_REMINDER,
                'Event reminder',
                $body,
                $event,
                ['url' => $url],
                $dueAt
            );
        }
    }

    private function syncCallReminderNotifications(CrmCall $call, string $url): void
    {
        CrmNotification::query()
            ->where('type', CrmNotification::TYPE_REMINDER)
            ->where('notifiable_type', CrmCall::class)
            ->where('notifiable_id', $call->id)
            ->whereNull('read_at')
            ->delete();

        if (!$call->reminder_enabled || !$call->call_start_time) {
            return;
        }

        $dueAt = $this->asCarbon($call->call_start_time)?->copy()->subMinutes((int) ($call->reminder_minutes_before ?? 15));
        if (!$dueAt) {
            return;
        }

        $startsAt = $this->formatReminderStartsAt($call->call_start_time);
        $body = trim('Call with ' . $call->to_from . ' starts' . ($startsAt ? ' on ' . $startsAt : ''));

        $this->createNotification(
            $call->owner_id,
            $call->owner_id,
            CrmNotification::TYPE_REMINDER,
            'Call reminder',
            $body,
            $call,
            ['url' => $url],
            $dueAt
        );
    }

    private function formatReminderStartsAt($value): ?string
    {
        $startsAt = $this->asCarbon($value);

        return $startsAt
            ? $startsAt->copy()->timezone((string) config('app.timezone', 'UTC'))->format('M d, Y h:i A')
            : null;
    }

    private function createNotification(
        ?int $userId,
        ?int $actorId,
        string $type,
        string $title,
        ?string $body,
        CrmEvent|CrmCall $activity,
        array $data = [],
        ?Carbon $dueAt = null
    ): void {
        if (!$userId) {
            return;
        }

        CrmNotification::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'notifiable_type' => $activity::class,
            'notifiable_id' => $activity->id,
            'due_at' => $dueAt,
        ]);
    }

    private function eventReminderUsers(CrmEvent $event)
    {
        return collect([$event->host])
            ->merge($this->eventParticipantUsers($event))
            ->filter()
            ->unique('id')
            ->values();
    }

    private function eventParticipantUsers(CrmEvent $event)
    {
        $emails = collect($event->participants ?? [])
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return collect();
        }

        return User::query()
            ->select('id', 'name', 'email')
            ->whereIn(DB::raw('LOWER(email)'), $emails)
            ->get();
    }

    private function shouldSendEventInvitation(array $data, bool $sendByDefault = false): bool
    {
        $hasParticipants = collect($data['participants'] ?? [])
            ->contains(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL));

        return $hasParticipants && (
            $sendByDefault || filter_var($data['send_invitation'] ?? false, FILTER_VALIDATE_BOOLEAN)
        );
    }

    private function eventStatus(CrmEvent $event, Carbon $now): string
    {
        if ($event->status === 'Cancelled') {
            return 'Cancelled';
        }

        $endsAt = $this->asCarbon($event->ends_at);

        if ($endsAt && $endsAt->lt($now)) {
            return 'Closed';
        }

        return 'Open';
    }

    private function asCarbon(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    private function eventColor(string $status): string
    {
        return match ($status) {
            'Cancelled' => self::EVENT_CANCELLED_COLOR,
            'Closed' => self::EVENT_CLOSED_COLOR,
            default => self::EVENT_COLOR,
        };
    }

    private function callRows($calls): array
    {
        return $calls->map(fn (CrmCall $call) => $this->callRow($call))->values()->all();
    }

    private function callRow(CrmCall $call): array
    {
        $callStartAt = $this->asCarbon($call->call_start_time);

        return [
            'id' => $call->id,
            'owner_id' => $call->owner_id,
            'order_id' => $call->order_id,
            'client_id' => $call->client_id,
            'to_from' => $call->to_from,
            'call_type' => $call->call_type,
            'outgoing_call_status' => $call->outgoing_call_status,
            'call_start_time' => optional($callStartAt)->format('M d, Y h:i A'),
            'call_start_at' => optional($callStartAt)->format(\DateTimeInterface::ATOM),
            'call_duration' => $call->call_duration_minutes ? $call->call_duration_minutes . ' mins' : '',
            'call_duration_minutes' => $call->call_duration_minutes,
            'related_to' => $call->order?->name ?? $call->client?->name,
            'order' => $call->order ? $this->orderOption($call->order) : null,
            'client' => $call->client ? $this->clientOption($call->client) : null,
            'owner' => $call->owner?->name,
            'reminder_enabled' => (bool) $call->reminder_enabled,
            'reminder_minutes_before' => $call->reminder_minutes_before,
            'call_purpose' => $call->call_purpose,
            'call_agenda' => $call->call_agenda,
        ];
    }

    private function orderOption(Order $order): array
    {
        $defaultOwner = $order->owners->first() ?? $order->user;

        return [
            'id' => $order->id,
            'name' => $order->name,
            'label' => trim(($order->name ?? 'Order') . ($order->order_number ? ' #' . $order->order_number : '')),
            'client' => $order->client ? $this->clientOption($order->client) : null,
            'default_owner_id' => $defaultOwner?->id,
            'default_owner_name' => $defaultOwner?->name,
        ];
    }

    private function clientOption(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'label' => trim(($client->name ?? 'Client') . ' ' . ($client->phone ?? '')),
        ];
    }
}
