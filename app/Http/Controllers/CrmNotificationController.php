<?php

namespace App\Http\Controllers;

use App\Enum\RoleEnum;
use App\Models\CrmNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CrmNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = Carbon::now();
        $supervising = $this->canSuperviseNotifications($request);
        $baseQuery = $this->visibleNotificationsQuery($request)
            ->where(function ($query) use ($now) {
                $query->where('type', '!=', CrmNotification::TYPE_REMINDER)
                    ->orWhereNull('due_at')
                    ->orWhere('due_at', '<=', $now);
            });

        $notificationRows = (clone $baseQuery)
            ->with(['actor:id,name', 'user:id,name'])
            ->latest('created_at')
            ->limit(60)
            ->get(['id', 'user_id', 'actor_id', 'type', 'title', 'body', 'data', 'notifiable_type', 'notifiable_id', 'due_at', 'read_at', 'created_at']);

        if ($this->isOwnerAdmin($request)) {
            $ownNotificationKeys = $notificationRows
                ->filter(fn (CrmNotification $notification) => (int) $notification->user_id === (int) $user->id)
                ->mapWithKeys(fn (CrmNotification $notification) => [$this->dedupeKey($notification) => true]);

            $notificationRows = $notificationRows
                ->reject(fn (CrmNotification $notification) => (int) $notification->user_id !== (int) $user->id
                    && $ownNotificationKeys->has($this->dedupeKey($notification)))
                ->values();
        }

        $notifications = $notificationRows
            ->groupBy('type')
            ->map(fn ($items) => $items->map(fn (CrmNotification $notification) => $this->row($notification, $supervising))->values())
            ->all();

        return response()->json([
            'unread_count' => $supervising
                ? (clone $baseQuery)->where('user_id', $user->id)->whereNull('read_at')->count()
                : (clone $baseQuery)->whereNull('read_at')->count(),
            'supervising' => $supervising,
            'feeds' => $notifications[CrmNotification::TYPE_FEED] ?? [],
            'reminders' => $notifications[CrmNotification::TYPE_REMINDER] ?? [],
            'system' => $notifications[CrmNotification::TYPE_SYSTEM] ?? [],
        ]);
    }

    public function markRead(Request $request, CrmNotification $notification): JsonResponse
    {
        $ownsNotification = (int) $notification->user_id === (int) $request->user()->id;
        $canViewNotification = $this->visibleNotificationsQuery($request)
            ->whereKey($notification->getKey())
            ->exists();

        abort_unless($canViewNotification, 404);

        if ($ownsNotification && !$notification->read_at) {
            $notification->update(['read_at' => Carbon::now()]);
        }

        return response()->json(['notification' => $this->row($notification->fresh(['actor:id,name', 'user:id,name']), $this->canSuperviseNotifications($request))]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $now = Carbon::now();

        CrmNotification::query()
            ->where('user_id', $request->user()->id)
            ->where(function ($query) use ($now) {
                $query->where('type', '!=', CrmNotification::TYPE_REMINDER)
                    ->orWhereNull('due_at')
                    ->orWhere('due_at', '<=', $now);
            })
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['ok' => true]);
    }

    private function row(CrmNotification $notification, bool $supervising = false): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'actor' => $notification->actor?->name,
            'owner' => $supervising ? $notification->user?->name : null,
            'is_supervised' => $supervising,
            'read_at' => optional($notification->read_at)->toISOString(),
            'created_at' => optional($notification->created_at)->toISOString(),
            'created_at_label' => optional($notification->created_at)->format('M d, h:i A'),
            'due_at' => optional($notification->due_at)->toISOString(),
            'url' => ($notification->data ?? [])['url'] ?? null,
        ];
    }

    private function canSuperviseNotifications(Request $request): bool
    {
        return (bool) $request->user()?->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::OWNER_ADMIN->value,
        ]);
    }

    private function isOwnerAdmin(Request $request): bool
    {
        return (bool) $request->user()?->hasRole(RoleEnum::OWNER_ADMIN->value);
    }

    private function visibleNotificationsQuery(Request $request)
    {
        $user = $request->user();

        $query = CrmNotification::query();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return $query;
        }

        if ($user->hasRole(RoleEnum::OWNER_ADMIN->value)) {
            return $query->where(function ($innerQuery) use ($user) {
                $innerQuery
                    ->where('user_id', $user->id)
                    ->orWhereHas('user.roles', function ($roleQuery) {
                        $roleQuery->where('name', RoleEnum::OWNER->value);
                    });
            });
        }

        return $query->where('user_id', $user->id);
    }

    private function dedupeKey(CrmNotification $notification): string
    {
        return implode('|', [
            $notification->type,
            $notification->title,
            $notification->notifiable_type,
            $notification->notifiable_id,
            ($notification->data ?? [])['url'] ?? '',
        ]);
    }
}
