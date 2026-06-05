<?php

namespace App\Http\Controllers;

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
        $baseQuery = CrmNotification::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($now) {
                $query->where('type', '!=', CrmNotification::TYPE_REMINDER)
                    ->orWhereNull('due_at')
                    ->orWhere('due_at', '<=', $now);
            });

        $notifications = (clone $baseQuery)
            ->with('actor:id,name')
            ->latest('created_at')
            ->limit(60)
            ->get()
            ->groupBy('type')
            ->map(fn ($items) => $items->map(fn (CrmNotification $notification) => $this->row($notification))->values())
            ->all();

        return response()->json([
            'unread_count' => (clone $baseQuery)->whereNull('read_at')->count(),
            'feeds' => $notifications[CrmNotification::TYPE_FEED] ?? [],
            'reminders' => $notifications[CrmNotification::TYPE_REMINDER] ?? [],
            'system' => $notifications[CrmNotification::TYPE_SYSTEM] ?? [],
        ]);
    }

    public function markRead(Request $request, CrmNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        if (!$notification->read_at) {
            $notification->update(['read_at' => Carbon::now()]);
        }

        return response()->json(['notification' => $this->row($notification->fresh('actor:id,name'))]);
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

    private function row(CrmNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'actor' => $notification->actor?->name,
            'read_at' => optional($notification->read_at)->toISOString(),
            'created_at' => optional($notification->created_at)->toISOString(),
            'created_at_label' => optional($notification->created_at)->format('M d, h:i A'),
            'due_at' => optional($notification->due_at)->toISOString(),
            'url' => ($notification->data ?? [])['url'] ?? null,
        ];
    }
}
