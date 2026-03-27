<?php

namespace App\Support;

use App\Jobs\SendGmailEmail;
use App\Mail\OrderOwnerAdditionalOwnerAssigned;
use App\Mail\OrderOwnerAssigned;
use App\Mail\OrderOwnerUnassigned;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderOwnerChangeNotifier
{
    public function notify(Order $order, array $previousOwnerIds, array $currentOwnerIds): void
    {
        $previousOwnerIds = $this->normalizeOwnerIds($previousOwnerIds);
        $currentOwnerIds = $this->normalizeOwnerIds($currentOwnerIds);

        if ($previousOwnerIds === $currentOwnerIds) {
            return;
        }

        $dispatchNotifications = function () use ($order, $previousOwnerIds, $currentOwnerIds) {
            $addedOwnerIds = array_values(array_diff($currentOwnerIds, $previousOwnerIds));
            $removedOwnerIds = array_values(array_diff($previousOwnerIds, $currentOwnerIds));
            $retainedOwnerIds = array_values(array_intersect($previousOwnerIds, $currentOwnerIds));

            if ($addedOwnerIds === [] && $removedOwnerIds === []) {
                return;
            }

            $usersById = User::query()
                ->whereIn('id', array_values(array_unique(array_merge($previousOwnerIds, $currentOwnerIds))))
                ->get()
                ->keyBy('id');

            $freshOrder = Order::query()
                ->with(['client', 'owners'])
                ->find($order->id);

            if (!$freshOrder) {
                return;
            }

            $currentOwners = collect($currentOwnerIds)
                ->map(fn (int $ownerId) => $usersById->get($ownerId))
                ->filter();

            $addedOwners = collect($addedOwnerIds)
                ->map(fn (int $ownerId) => $usersById->get($ownerId))
                ->filter();

            $removedOwners = collect($removedOwnerIds)
                ->map(fn (int $ownerId) => $usersById->get($ownerId))
                ->filter();

            $retainedOwners = collect($retainedOwnerIds)
                ->map(fn (int $ownerId) => $usersById->get($ownerId))
                ->filter();

            foreach ($addedOwners as $owner) {
                $this->dispatchUniqueEmail(
                    $owner,
                    new OrderOwnerAssigned($freshOrder, $owner->name, $currentOwners->pluck('name')->filter()->values()->all())
                );
            }

            foreach ($removedOwners as $owner) {
                $this->dispatchUniqueEmail(
                    $owner,
                    new OrderOwnerUnassigned($freshOrder, $owner->name, $currentOwners->pluck('name')->filter()->values()->all())
                );
            }

            if ($addedOwners->isNotEmpty() && $removedOwners->isEmpty()) {
                $addedOwnerNames = $addedOwners->pluck('name')->filter()->values()->all();

                foreach ($retainedOwners as $owner) {
                    $this->dispatchUniqueEmail(
                        $owner,
                        new OrderOwnerAdditionalOwnerAssigned($freshOrder, $owner->name, $addedOwnerNames, $currentOwners->pluck('name')->filter()->values()->all())
                    );
                }
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatchNotifications);
            return;
        }

        $dispatchNotifications();
    }

    public function normalizeOwnerIds(array $ownerIds): array
    {
        return collect($ownerIds)
            ->map(fn ($ownerId) => (int) $ownerId)
            ->filter(fn (int $ownerId) => $ownerId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function dispatchUniqueEmail(?User $owner, object $mailable): void
    {
        $email = trim((string) ($owner?->email ?? ''));

        if ($email === '') {
            return;
        }

        SendGmailEmail::dispatch($email, $mailable)
            ->onQueue('emails')
            ->afterCommit();
    }
}
