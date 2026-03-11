<?php

namespace App\Http\Controllers;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        $module = strtolower(trim((string) $request->query('module', 'all')));
        $origin = strtolower(trim((string) $request->query('origin', $module)));
        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min(25, $limit));

        if ($term === '') {
            return response()->json(['results' => []]);
        }

        $moduleStatuses = $this->moduleStatuses();
        if ($module === '' || $module === 'all') {
            $statuses = array_values(array_unique(array_merge(...array_values($moduleStatuses))));
        } elseif (array_key_exists($module, $moduleStatuses)) {
            $statuses = $moduleStatuses[$module];
        } else {
            return response()->json(['results' => []]);
        }

        $query = Order::query()
            ->select('orders.id', 'orders.name', 'orders.status', 'orders.order_number', 'orders.client_id', 'orders.updated_at')
            ->with([
                'client:id,name',
                'client.companyContact:id,name',
                'orderCompanyContacts.companyContact:id,name',
                'orderCompanyContacts.client:id,name',
                'owners:id,name',
            ]);

        if (!empty($statuses)) {
            $query->whereIn('orders.status', $statuses);
        }

        $restrictionContext = $module !== 'all' ? $module : $origin;
        if ($this->shouldRestrictOwner($request->user(), $restrictionContext)) {
            $query->whereHas('owners', function (Builder $builder) use ($request) {
                $builder->where('users.id', $request->user()?->id);
            });
        }

        $like = '%' . $term . '%';
        $numericTerm = is_numeric($term) ? (float) $term : null;
        $query->where(function (Builder $builder) use ($like) {
            $builder->where('orders.name', 'like', $like)
                ->orWhere('orders.order_number', 'like', $like)
                ->orWhere('orders.job_address', 'like', $like)
                ->orWhere('orders.city', 'like', $like)
                ->orWhere('orders.job_state', 'like', $like)
                ->orWhere('orders.job_zip', 'like', $like)
                ->orWhereHas('client', function (Builder $clientQuery) use ($like) {
                    $clientQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('other_phone', 'like', $like)
                        ->orWhere('secondary_email', 'like', $like);
                })
                ->orWhereHas('client.companyContact', function (Builder $companyQuery) use ($like) {
                    $companyQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                })
                ->orWhereHas('orderCompanyContacts.companyContact', function (Builder $companyQuery) use ($like) {
                    $companyQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                })
                ->orWhereHas('orderCompanyContacts.client', function (Builder $clientQuery) use ($like) {
                    $clientQuery->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                })
                ->orWhereHas('owners', function (Builder $ownerQuery) use ($like) {
                    $ownerQuery->where('users.name', 'like', $like)
                        ->orWhere('users.email', 'like', $like);
                })
                ->orWhereHas('user', function (Builder $userQuery) use ($like) {
                    $userQuery->where('users.name', 'like', $like)
                        ->orWhere('users.email', 'like', $like);
                });
        });

        if ($numericTerm !== null) {
            $query->orWhere('orders.project_amount', '=', $numericTerm);
        }

        $orders = $query
            ->orderByDesc('orders.updated_at')
            ->limit($limit)
            ->get();

        $results = $orders->map(function (Order $order) {
            return [
                'id' => $order->id,
                'name' => $order->name,
                'status' => $order->status,
                'client' => $this->resolveClientName($order),
                'owner' => $this->resolveOwnerName($order),
            ];
        })->values();

        return response()->json([
            'results' => $results,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function moduleStatuses(): array
    {
        return [
            'frontdesk' => [
                OrderStatusEnum::NEW_CUSTOMER_REQUEST->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_FOLLOW_UP->value,
                OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value,
                OrderStatusEnum::LOST_CUSTOMER_REQUEST->value,
                OrderStatusEnum::QUALIFIED->value,
            ],
            'sales' => [
                OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value,
                OrderStatusEnum::PENDING_ASSIGNMENT->value,
                OrderStatusEnum::REQUEST_RE_SCHEDULE->value,
                OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value,
                OrderStatusEnum::FOLLOW_UP->value,
                OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
                OrderStatusEnum::STAND_BY->value,
                OrderStatusEnum::PRE_CONTRACT_APPOINTMENT->value,
                OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
                OrderStatusEnum::LOST_CONTRACT->value,
            ],
            'order_processing' => [
                OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
                OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
                OrderStatusEnum::FILE_REVIEW->value,
                OrderStatusEnum::CLOSED_WON->value,
            ],
            'order_storage' => [
                OrderStatusEnum::ACCOUNT_RECEIPT->value,
                OrderStatusEnum::REVIEW->value,
                OrderStatusEnum::PLANNED->value,
                OrderStatusEnum::MATERIALS_RECEIVED->value,
                OrderStatusEnum::CONFIRMED->value,
                OrderStatusEnum::EXECUTION->value,
                OrderStatusEnum::ON_HOLD->value,
                OrderStatusEnum::SUPERVISION->value,
                OrderStatusEnum::INSPECTION->value,
                OrderStatusEnum::FINISH->value,
                OrderStatusEnum::FINAL_INSPECTION->value,
                OrderStatusEnum::FINAL_COLLECT->value,
                OrderStatusEnum::COMPLETE->value,
            ],
        ];
    }

    private function shouldRestrictOwner(?User $user, string $context): bool
    {
        if (!$user) {
            return false;
        }

        if (!$user->hasRole(RoleEnum::OWNER->value)) {
            return false;
        }

        $context = strtolower($context);

        if ($context === 'sales' || $context === 'order_processing') {
            return !$user->hasAnyRole([
                RoleEnum::ADMIN->value,
                RoleEnum::ACCOUNT_MANAGER->value,
                RoleEnum::OWNER_ADMIN->value,
                RoleEnum::FRONTDESK_ADMIN->value,
            ]);
        }

        if ($context === 'frontdesk') {
            return !$user->hasAnyRole([
                RoleEnum::ADMIN->value,
                RoleEnum::ACCOUNT_MANAGER->value,
            ]);
        }

        return !$user->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::ACCOUNT_MANAGER->value,
            RoleEnum::OWNER_ADMIN->value,
            RoleEnum::FRONTDESK_ADMIN->value,
        ]);
    }

    private function resolveClientName(Order $order): ?string
    {
        if ($order->relationLoaded('client') && $order->client?->name) {
            return $order->client->name;
        }

        $orderContact = $order->orderCompanyContacts
            ->first(function ($contact) {
                return !empty($contact->client?->name);
            });

        return $orderContact?->client?->name;
    }

    private function resolveOwnerName(Order $order): ?string
    {
        if (!$order->relationLoaded('owners')) {
            return null;
        }

        $ownerNames = $order->owners
            ->pluck('name')
            ->filter(function (?string $name) {
                return filled($name);
            })
            ->values();

        if ($ownerNames->isEmpty()) {
            return null;
        }

        return $ownerNames->implode(', ');
    }
}
