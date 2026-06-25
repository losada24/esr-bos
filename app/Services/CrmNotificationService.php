<?php

namespace App\Services;

use App\Enum\RoleEnum;
use App\Models\CrmNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CrmNotificationService
{
    public function recordEsrOrderCreated(Order $order, ?User $actor): void
    {
        $order->loadMissing(['user:id,name,email', 'owners:id,name,email']);

        $recipients = $this->orderUsers($order)
            ->merge(
                User::role(RoleEnum::OWNER_ADMIN->value)
                    ->get(['users.id', 'users.name', 'users.email'])
            )
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();

        foreach ($recipients as $user) {
            CrmNotification::create([
                'user_id' => $user->id,
                'actor_id' => $actor?->id,
                'type' => CrmNotification::TYPE_FEED,
                'title' => 'New ESR order created',
                'body' => ($order->name ?: ('Order #' . $order->id))
                    . ' was created and assigned to '
                    . ($order->owners->pluck('name')->filter()->implode(', ') ?: 'no owner'),
                'data' => [
                    'url' => route('esr-process.order-view', $order->id),
                ],
                'notifiable_type' => Order::class,
                'notifiable_id' => $order->id,
            ]);
        }
    }

    public function recordOrderFeed(Order $order, ?User $actor, string $title, string $body): void
    {
        $order->loadMissing(['user:id,name,email', 'owners:id,name,email', 'supervisor:id,name,email']);

        foreach ($this->orderUsers($order) as $user) {
            CrmNotification::create([
                'user_id' => $user->id,
                'actor_id' => $actor?->id,
                'type' => CrmNotification::TYPE_FEED,
                'title' => $title,
                'body' => $body,
                'data' => [
                    'url' => route('frontdesk.order_view', $order->id),
                ],
                'notifiable_type' => Order::class,
                'notifiable_id' => $order->id,
            ]);
        }
    }

    private function orderUsers(Order $order): EloquentCollection
    {
        $users = new EloquentCollection();

        if ($order->user) {
            $users->push($order->user);
        }

        if ($order->supervisor) {
            $users->push($order->supervisor);
        }

        foreach ($order->owners as $owner) {
            $users->push($owner);
        }

        return $users
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id')
            ->values();
    }
}
