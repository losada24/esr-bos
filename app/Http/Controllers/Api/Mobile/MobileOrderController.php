<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enum\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(RoleEnum::CUSTOMER->value)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $clientIds = Client::query()
            ->where('mobile_user_id', $user->id)
            ->pluck('id')
            ->values();

        if ($clientIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $orders = Order::query()
            ->with([
                'owners:id,name,email,phone',
                'supervisor:id,name,email,phone',
                'installationTeams:id,company_name,phone,user_id',
                'installationTeams.user:id,name,email,phone',
            ])
            ->whereIn('client_id', $clientIds)
            ->orderByDesc('updated_at')
            ->get([
                'id',
                'order_number',
                'name',
                'order_type',
                'status',
                'city_permits',
                'project_amount',
                'down_payment',
                'job_address',
                'city',
                'job_state',
                'job_zip',
                'method_of_payment',
                'type_of_financing',
                'service',
                'contract_signing_date',
                'delivery_date',
                'installation_date',
                'schedule_appointment',
                'is_supply',
                'eta_date',
                'created_at',
                'updated_at',
            ]);

        $payload = $orders->map(function (Order $order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'name' => $order->name,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'city_permits' => (bool) $order->city_permits,
                'project_amount' => $order->project_amount,
                'down_payment' => $order->down_payment,
                'job_address' => $order->job_address,
                'city' => $order->city,
                'job_state' => $order->job_state,
                'job_zip' => $order->job_zip,
                'method_of_payment' => $order->method_of_payment,
                'type_of_financing' => $order->type_of_financing,
                'service' => $order->service,
                'contract_signing_date' => $order->contract_signing_date,
                'delivery_date' => $order->delivery_date,
                'installation_date' => $order->installation_date,
                'schedule_appointment' => $order->schedule_appointment,
                'is_supply' => (bool) $order->is_supply,
                'eta_date' => $order->eta_date,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'owners' => $order->owners->map(fn ($owner) => [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'phone' => $owner->phone,
                ])->values(),
                'supervisor' => $order->supervisor ? [
                    'id' => $order->supervisor->id,
                    'name' => $order->supervisor->name,
                    'email' => $order->supervisor->email,
                    'phone' => $order->supervisor->phone,
                ] : null,
                'installers' => $order->installationTeams->map(fn ($installer) => [
                    'id' => $installer->id,
                    'name' => $installer->user?->name ?? $installer->company_name,
                    'email' => $installer->user?->email,
                    'phone' => $installer->user?->phone ?? $installer->phone,
                    'company_name' => $installer->company_name,
                ])->values(),
            ];
        });

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(RoleEnum::CUSTOMER->value)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $client = Client::query()
            ->where('mobile_user_id', $user->id)
            ->where('id', $order->client_id)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $order->loadMissing([
            'attachments:id,attachable_id,attachable_type,filename,file_path,file_type,created_at',
            'orderStatus:id,order_id,status,created_at',
            'owners:id,name,email,phone',
            'supervisor:id,name,email,phone',
            'installationTeams:id,company_name,phone,user_id',
            'installationTeams.user:id,name,email,phone',
            'paymentSchedule:id,order_id,schedule_type,total_amount',
            'paymentSchedule.installments:id,payment_schedule_id,position,label,percentage,amount,due_date,status,paid_at,paid_by',
            'paymentSchedule.installments.paidBy:id,name',
        ]);

        $paymentSchedule = null;
        if ($order->paymentSchedule) {
            $paymentSchedule = [
                'schedule_type' => $order->paymentSchedule->schedule_type,
                'total_amount' => $order->paymentSchedule->total_amount,
                'installments' => $order->paymentSchedule->installments
                    ->sortBy('position')
                    ->values()
                    ->map(fn ($installment) => [
                        'id' => $installment->id,
                        'position' => $installment->position,
                        'label' => $installment->label,
                        'percentage' => $installment->percentage,
                        'amount' => $installment->amount,
                        'due_date' => $installment->due_date,
                        'status' => $installment->status,
                        'paid_at' => $installment->paid_at,
                        'paid_by' => $installment->paidBy ? [
                            'id' => $installment->paidBy->id,
                            'name' => $installment->paidBy->name,
                        ] : null,
                    ]),
            ];
        }

        return response()->json([
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'name' => $order->name,
                'order_type' => $order->order_type,
                'status' => $order->status,
                'city_permits' => (bool) $order->city_permits,
                'project_amount' => $order->project_amount,
                'down_payment' => $order->down_payment,
                'job_address' => $order->job_address,
                'city' => $order->city,
                'job_state' => $order->job_state,
                'job_zip' => $order->job_zip,
                'method_of_payment' => $order->method_of_payment,
                'type_of_financing' => $order->type_of_financing,
                'service' => $order->service,
                'contract_signing_date' => $order->contract_signing_date,
                'delivery_date' => $order->delivery_date,
                'installation_date' => $order->installation_date,
                'schedule_appointment' => $order->schedule_appointment,
                'is_supply' => (bool) $order->is_supply,
                'eta_date' => $order->eta_date,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'attachments' => $order->attachments,
                'status_history' => $order->orderStatus,
                'payment_schedule' => $paymentSchedule,
                'owners' => $order->owners->map(fn ($owner) => [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'phone' => $owner->phone,
                ])->values(),
                'supervisor' => $order->supervisor ? [
                    'id' => $order->supervisor->id,
                    'name' => $order->supervisor->name,
                    'email' => $order->supervisor->email,
                    'phone' => $order->supervisor->phone,
                ] : null,
                'installers' => $order->installationTeams->map(fn ($installer) => [
                    'id' => $installer->id,
                    'name' => $installer->user?->name ?? $installer->company_name,
                    'email' => $installer->user?->email,
                    'phone' => $installer->user?->phone ?? $installer->phone,
                    'company_name' => $installer->company_name,
                ])->values(),
            ],
        ]);
    }
}
