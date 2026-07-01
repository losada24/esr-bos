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
        $globalModuleStatuses = array_filter(
            $moduleStatuses,
            fn (string $moduleName) => $moduleName !== 'commissions',
            ARRAY_FILTER_USE_KEY
        );

        if ($module === '' || $module === 'all') {
            $statuses = array_values(array_unique(array_merge(...array_values($globalModuleStatuses))));
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

        if ($origin === 'service_control' || $module === 'service_control') {
            $query->withCount([
                'serviceControls as assigned_services_count' => fn (Builder $builder) => $builder->where('is_bm', false),
                'serviceControls as assigned_bm_count' => fn (Builder $builder) => $builder->where('is_bm', true),
            ]);
        }

        if (!empty($statuses)) {
            $query->whereIn('orders.status', $statuses);
        }

        $restrictionContext = $module !== 'all' ? $module : $origin;
        if ($this->shouldRestrictOwner($request->user(), $restrictionContext)) {
            $query->accessibleToOwner($request->user());
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
                ->orWhereHas('client.companyContacts', function (Builder $companyQuery) use ($like) {
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
                'company' => $this->resolveCompanyName($order),
                'owner' => $this->resolveOwnerName($order),
                'assigned_services_count' => (int) ($order->assigned_services_count ?? 0),
                'assigned_bm_count' => (int) ($order->assigned_bm_count ?? 0),
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
            'esr_process' => [
                OrderStatusEnum::DEALER_REQUEST->value,
                OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
                OrderStatusEnum::REVIEW->value,
                OrderStatusEnum::ACCOUNT_RECEIPT->value,
                OrderStatusEnum::PLANNED->value,
                OrderStatusEnum::PRODUCTION->value,
                OrderStatusEnum::PRODUCTION_SERVICES->value,
                OrderStatusEnum::PRE_COORDINATION_ACCOUNTING->value,
                OrderStatusEnum::PENDING_MAT_REYLOS->value,
                OrderStatusEnum::PENDING_MATERIALS->value,
                OrderStatusEnum::PENDING_MATERIALS_EWS->value,
                OrderStatusEnum::MATERIAL_ORDER_COMPLETED->value,
                OrderStatusEnum::MATERIAL_ORDER_COMPLETED_FINANCED->value,
                OrderStatusEnum::STORAGE_MATERIAL->value,
                OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED->value,
                OrderStatusEnum::PENDING_PAYMENT->value,
                OrderStatusEnum::COMPLETE->value,
                OrderStatusEnum::LOST->value,
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
            'commissions' => array_column(OrderStatusEnum::cases(), 'value'),
            'service_control' => [
                OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value,
                OrderStatusEnum::CLOSED_WON->value,
                OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
                OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
                OrderStatusEnum::FILE_REVIEW->value,
                OrderStatusEnum::ACCOUNT_RECEIPT->value,
                OrderStatusEnum::REVIEW->value,
                OrderStatusEnum::PLANNED->value,
                OrderStatusEnum::MATERIALS_RECEIVED->value,
                OrderStatusEnum::CONFIRMED->value,
                OrderStatusEnum::DELIVERY_CONFIRMED->value,
                OrderStatusEnum::EXECUTION->value,
                OrderStatusEnum::ON_HOLD->value,
                OrderStatusEnum::SUPERVISION->value,
                OrderStatusEnum::INSPECTION->value,
                OrderStatusEnum::FINISH->value,
                OrderStatusEnum::FINAL_INSPECTION->value,
                OrderStatusEnum::FINAL_COLLECT->value,
                OrderStatusEnum::COMPLETE->value,
                OrderStatusEnum::SERVICE->value,
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

        if ($context === 'sales' || $context === 'order_processing' || $context === 'service_control') {
            return !$user->hasAnyRole([
                RoleEnum::ADMIN->value,
                RoleEnum::ACCOUNT_MANAGER->value,
                RoleEnum::OWNER_ADMIN->value,
                RoleEnum::FRONTDESK_ADMIN->value,
                RoleEnum::SERVICE_MANAGER->value,
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

    private function resolveCompanyName(Order $order): ?string
    {
        $selectedCompany = $order->orderCompanyContacts
            ->firstWhere('is_selected', true)
            ?? $order->orderCompanyContacts
                ->first(function ($contact) {
                    return !empty($contact->companyContact?->name);
                });

        if (!empty($selectedCompany?->companyContact?->name)) {
            return $selectedCompany->companyContact->name;
        }

        if ($order->relationLoaded('client') && !empty($order->client?->companyContact?->name)) {
            return $order->client->companyContact->name;
        }

        return null;
    }
}
