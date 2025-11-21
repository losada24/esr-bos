<?php

namespace App\Http\Controllers;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class OrderProcessingController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $processingStatuses = $this->processingStatuses();
        $pipelineStatusMap = $this->statusPipelineMap();
        $queryStatuses = array_keys($pipelineStatusMap);

        $ordersQuery = Order::with(['client.companyContact', 'owners', 'user', 'tags'])
            ->whereIn('status', $queryStatuses);

        if ($this->isOwnerRestricted($user)) {
            $ordersQuery->whereHas('owners', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        $orders = $ordersQuery->get();

        $data = collect($processingStatuses)->map(function (string $status) use ($orders, $pipelineStatusMap) {
            $ordersByStatus = $orders->filter(function (Order $order) use ($status, $pipelineStatusMap) {
                $pipelineStatus = $pipelineStatusMap[$order->status] ?? $order->status;
                return $pipelineStatus === $status;
            });

            return [
                'id' => $status,
                'title' => $status,
                'tasks' => $ordersByStatus->map(function (Order $order) {
                    return [
                        'id' => $order->id,
                        'title' => $order->name ?? 'No Title',
                        'client_id' => $order->client_id,
                        'date_edited' => optional($order->updated_at)->format('M d, Y h:i A'),
                        'date' => optional($order->created_at)->format('M d, Y h:i A'),
                        'schedule_appointment' => $order->schedule_appointment
                            ? Carbon::parse($order->schedule_appointment)->format('M d, Y h:i A')
                            : null,
                        'schedule_appointment_iso' => $order->schedule_appointment
                            ? Carbon::parse($order->schedule_appointment)->format('Y-m-d\TH:i')
                            : null,
                        'phone' => $order->client->phone ?? null,
                        'is_supply' => (bool) ($order->is_supply ?? false),
                        'project_amount' => $order->project_amount ? (float) $order->project_amount : null,
                        'down_payment' => $order->down_payment ? (float) $order->down_payment : null,
                        'job_address' => $order->job_address,
                        'city' => $order->city,
                        'job_state' => $order->job_state,
                        'job_zip' => $order->job_zip,
                        'method_of_payment' => $order->method_of_payment,
                        'type_of_financing' => $order->type_of_financing,
                        'owner_ids' => $order->owners->pluck('id')->values(),
                        'owners' => $order->owners->map(fn ($owner) => [
                            'id' => $owner->id,
                            'name' => $owner->name,
                        ])->values(),
                        'tags' => ($order->tags ?? collect())->map(function ($tag) {
                            return [
                                'name' => $tag->name,
                                'color' => $tag->color,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        });

        return Inertia::render('OrderProcessing/Index', [
            'data' => $data,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function processingStatuses(): array
    {
        return [
            OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
            OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
            OrderStatusEnum::FILE_REVIEW->value,
            OrderStatusEnum::CLOSED_WON->value,
        ];
    }

    /**
     * Map order statuses to their corresponding column in the processing board.
     *
     * @return array<string, string>
     */
    private function statusPipelineMap(): array
    {
        return [
            OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value => OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
            OrderStatusEnum::CONTRACT_SIGNED_BY_CLIENT->value => OrderStatusEnum::RECTIFICATION_OF_MEASURES_AND_HOA->value,
            OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value => OrderStatusEnum::ORDER_MATERIALS_AND_FILE_ORGANIZATION->value,
            OrderStatusEnum::FILE_REVIEW->value => OrderStatusEnum::FILE_REVIEW->value,
            OrderStatusEnum::CLOSED_WON->value => OrderStatusEnum::CLOSED_WON->value,
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
        ]);
    }
}
