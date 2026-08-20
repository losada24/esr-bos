<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enum\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Support\PaymentInstallmentPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileOrderController extends Controller
{
    private function mobileClientIds(Request $request)
    {
        return Client::query()
            ->where('mobile_user_id', $request->user()->id)
            ->pluck('id')
            ->values();
    }

    private function isAuthorizedCustomer(Request $request): bool
    {
        $user = $request->user();

        return $user && $user->hasRole(RoleEnum::CUSTOMER->value);
    }

    private function clientColumns(): array
    {
        return [
            'id',
            'name',
            'phone',
            'email',
            'other_phone',
            'secondary_email',
            'source',
            'company_contact_id',
            'referral_id',
        ];
    }

    private function qualifiedClientColumns(string $table = 'clients'): array
    {
        return array_map(
            fn (string $column) => $table . '.' . $column,
            $this->clientColumns()
        );
    }

    private function orderColumns(): array
    {
        return [
            'id',
            'client_id',
            'order_number',
            'name',
            'order_type',
            'status',
            'city_permits',
            'cost_city_fee',
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
        ];
    }

    private function orderRelations(bool $includeDetails = false, bool $includeClientReferrals = false): array
    {
        $relations = [
            'client:' . implode(',', $this->clientColumns()),
            'client.companyContact:id,name',
            'owners:id,name,email,phone',
            'supervisor:id,name,email,phone',
            'installationTeams:id,company_name,phone,user_id',
            'installationTeams.user:id,name,email,phone',
        ];

        if ($includeDetails) {
            $relations = [
                ...$relations,
                'attachments:id,attachable_id,attachable_type,filename,file_path,file_type,created_at',
                'orderStatus:id,order_id,status,created_at',
                'paymentSchedule:id,order_id,schedule_type,total_amount',
                'paymentSchedule.installments:id,payment_schedule_id,position,label,percentage,amount,due_date,status,paid_at,paid_by',
                'paymentSchedule.installments.paidBy:id,name',
                'paymentSchedule.installments.movements:id,payment_installment_id,amount,paid_at,paid_by,method,note,created_at,updated_at',
                'paymentSchedule.installments.movements.paidBy:id,name',
                'changeOrderPayment:id,order_id,type,amount,note,status,paid_at,paid_by_id,created_at,updated_at',
                'changeOrderPayment.paidBy:id,name',
                'cityFeePayment:id,order_id,type,amount,note,status,paid_at,paid_by_id,created_at,updated_at',
                'cityFeePayment.paidBy:id,name',
            ];
        }

        if ($includeClientReferrals) {
            $relations[] = 'client.referredClients:' . implode(',', $this->qualifiedClientColumns());
            $relations[] = 'client.referredClients.companyContact:id,name';
            $relations['client.referredClients.orders'] = function ($query) use ($includeDetails) {
                $query
                    ->select($this->orderColumns())
                    ->with($this->orderRelations($includeDetails))
                    ->orderByDesc('updated_at');
            };
        }

        return $relations;
    }

    private function serializeClient(?Client $client, bool $includeReferredClients = false, bool $includeDetailedOrders = false): ?array
    {
        if (! $client) {
            return null;
        }

        $payload = [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'other_phone' => $client->other_phone,
            'secondary_email' => $client->secondary_email,
            'source' => $client->source,
            'company_contact' => $client->companyContact ? [
                'id' => $client->companyContact->id,
                'name' => $client->companyContact->name,
            ] : null,
        ];

        if ($includeReferredClients) {
            $payload['referred_clients'] = ($client->relationLoaded('referredClients') ? $client->referredClients : collect())
                ->map(fn (Client $referredClient) => $this->serializeReferredClient($referredClient, $includeDetailedOrders))
                ->values();
        }

        return $payload;
    }

    private function serializeReferredClient(Client $client, bool $includeDetailedOrders = false): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'other_phone' => $client->other_phone,
            'secondary_email' => $client->secondary_email,
            'source' => $client->source,
            'company_contact' => $client->companyContact ? [
                'id' => $client->companyContact->id,
                'name' => $client->companyContact->name,
            ] : null,
            'orders' => ($client->relationLoaded('orders') ? $client->orders : collect())
                ->map(fn (Order $order) => $this->serializeOrder($order, $includeDetailedOrders))
                ->values(),
        ];
    }

    private function serializeReferredClientSummary(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
            'email' => $client->email,
            'other_phone' => $client->other_phone,
            'secondary_email' => $client->secondary_email,
            'source' => $client->source,
            'company_contact' => $client->companyContact ? [
                'id' => $client->companyContact->id,
                'name' => $client->companyContact->name,
            ] : null,
            'orders_count' => $client->orders_count ?? 0,
        ];
    }

    private function serializeOrderPayment(?OrderPayment $orderPayment): ?array
    {
        if (! $orderPayment) {
            return null;
        }

        return [
            'id' => $orderPayment->id,
            'order_id' => $orderPayment->order_id,
            'type' => $orderPayment->type,
            'amount' => (float) $orderPayment->amount,
            'note' => $orderPayment->note,
            'status' => $orderPayment->status,
            'paid_at' => $orderPayment->paid_at?->toISOString(),
            'paid_by' => $orderPayment->paidBy
                ? ['id' => $orderPayment->paidBy->id, 'name' => $orderPayment->paidBy->name]
                : null,
            'created_at' => $orderPayment->created_at?->toISOString(),
            'updated_at' => $orderPayment->updated_at?->toISOString(),
        ];
    }

    private function referredClientsQuery($clientIds)
    {
        return Client::query()
            ->whereHas('referral', function ($query) use ($clientIds) {
                $query->whereIn('client_id', $clientIds);
            });
    }

    private function serializeOrder(Order $order, bool $includeDetails = false, bool $includeClientReferrals = false): array
    {
        $payload = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'name' => $order->name,
            'order_type' => $order->order_type,
            'status' => $order->status,
            'city_permits' => (bool) $order->city_permits,
            'phone' => $order->client?->phone,
            'cost_city_fee' => $order->cost_city_fee,
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
            'client' => $this->serializeClient($order->client, $includeClientReferrals, $includeDetails),
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

        if ($includeDetails) {
            $payload['attachments'] = $order->attachments;
            $payload['status_history'] = $order->orderStatus;
            $payload['payment_schedule'] = PaymentInstallmentPresenter::schedule($order->paymentSchedule);
            $payload['change_order_payment'] = $this->serializeOrderPayment($order->changeOrderPayment);
            $payload['city_fee_payment'] = $this->serializeOrderPayment($order->cityFeePayment);
        }

        return $payload;
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->isAuthorizedCustomer($request)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $clientIds = $this->mobileClientIds($request);

        if ($clientIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $orders = Order::query()
            ->with($this->orderRelations())
            ->whereIn('client_id', $clientIds)
            ->orderByDesc('updated_at')
            ->get($this->orderColumns());

        $payload = $orders->map(fn (Order $order) => $this->serializeOrder($order));

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function referredClients(Request $request): JsonResponse
    {
        if (! $this->isAuthorizedCustomer($request)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $clientIds = $this->mobileClientIds($request);

        if ($clientIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $referredClients = $this->referredClientsQuery($clientIds)
            ->with('companyContact:id,name')
            ->withCount('orders')
            ->orderBy('name')
            ->get($this->qualifiedClientColumns());

        return response()->json([
            'data' => $referredClients
                ->map(fn (Client $client) => $this->serializeReferredClientSummary($client))
                ->values(),
        ]);
    }

    public function referredClientOrders(Request $request, Client $client): JsonResponse
    {
        if (! $this->isAuthorizedCustomer($request)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $clientIds = $this->mobileClientIds($request);

        if ($clientIds->isEmpty()) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $referredClient = $this->referredClientsQuery($clientIds)
            ->whereKey($client->id)
            ->with('companyContact:id,name')
            ->first($this->qualifiedClientColumns());

        if (! $referredClient) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $orders = Order::query()
            ->with($this->orderRelations(includeDetails: true))
            ->where('client_id', $referredClient->id)
            ->orderByDesc('updated_at')
            ->get($this->orderColumns());

        return response()->json([
            'data' => [
                'client' => $this->serializeReferredClientSummary($referredClient),
                'orders' => $orders
                    ->map(fn (Order $order) => $this->serializeOrder($order, true))
                    ->values(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if (! $this->isAuthorizedCustomer($request)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $client = Client::query()
            ->where('mobile_user_id', $request->user()->id)
            ->where('id', $order->client_id)
            ->first();

        if (!$client) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        return response()->json([
            'data' => $this->serializeOrder(
                $order->loadMissing($this->orderRelations(includeDetails: true)),
                true
            ),
        ]);
    }
}
