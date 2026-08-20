<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Support\OrderFinancialEventLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderPaymentController extends Controller
{
    public function storeCityFee(Request $request, Order $order)
    {
        if (!$order->city_permits) {
            return response()->json([
                'message' => 'City permits are not enabled for this order.',
            ], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $cityFeePayment = $order->cityFeePayment()->withTrashed()->first();
        if ($cityFeePayment && $cityFeePayment->trashed()) {
            $cityFeePayment->restore();
        }

        if ($cityFeePayment && strtoupper((string) $cityFeePayment->status) === 'PAID') {
            return response()->json([
                'message' => 'City fee payment is already paid.',
            ], 409);
        }

        $amount = round((float) $validated['amount'], 2);
        $previousAmount = round((float) ($order->cost_city_fee ?? 0), 2);

        $order->forceFill([
            'cost_city_fee' => $amount,
        ])->save();

        $cityFeePayment = $cityFeePayment ?: new OrderPayment([
            'order_id' => $order->id,
            'type' => 'CITY_FEE',
            'status' => 'PENDING',
        ]);

        $cityFeePayment->forceFill([
            'order_id' => $order->id,
            'type' => 'CITY_FEE',
            'amount' => $amount,
            'note' => $validated['note'] ?? 'City Fee',
            'status' => $cityFeePayment->status ?: 'PENDING',
        ])->save();

        $cityFeePayment->load('paidBy');

        OrderFinancialEventLogger::log(
            $order,
            'CITY_FEE_PAYMENT_SAVED',
            'City fee payment saved',
            [
                'order_payment_id' => $cityFeePayment->id,
                'before_cost_city_fee' => $previousAmount,
                'after_cost_city_fee' => $amount,
                'status' => $cityFeePayment->status,
                'note' => $cityFeePayment->note,
            ]
        );

        return response()->json([
            'payment' => $this->serializePayment($cityFeePayment),
            'cost_city_fee' => (float) $order->cost_city_fee,
        ]);
    }

    public function update(Request $request, OrderPayment $orderPayment)
    {
        $previousStatus = $orderPayment->status;
        $validated = $request->validate([
            'status' => ['required', Rule::in(['PENDING', 'PAID'])],
        ]);

        $orderPayment->status = $validated['status'];

        if ($validated['status'] === 'PAID') {
            if ($previousStatus !== 'PAID') {
                $orderPayment->paid_by_id = auth()->id();
            }
            $orderPayment->paid_at = $orderPayment->paid_at ?? now();
        }

        if ($validated['status'] === 'PENDING') {
            $orderPayment->paid_at = null;
            $orderPayment->paid_by_id = null;
        }

        $orderPayment->save();
        $orderPayment->load('paidBy');

        if ($previousStatus !== $orderPayment->status) {
            $orderPayment->loadMissing('order');
            if ($orderPayment->order) {
                OrderFinancialEventLogger::log(
                    $orderPayment->order,
                    $orderPayment->type === 'CITY_FEE' ? 'CITY_FEE_STATUS_UPDATED' : 'CHANGE_ORDER_STATUS_UPDATED',
                    "{$orderPayment->type} payment status changed from {$previousStatus} to {$orderPayment->status}",
                    [
                        'order_payment_id' => $orderPayment->id,
                        'type' => $orderPayment->type,
                        'amount' => (float) $orderPayment->amount,
                        'before_status' => $previousStatus,
                        'after_status' => $orderPayment->status,
                        'paid_at' => $orderPayment->paid_at?->toISOString(),
                        'paid_by' => $orderPayment->paidBy
                            ? ['id' => $orderPayment->paidBy->id, 'name' => $orderPayment->paidBy->name]
                            : null,
                    ]
                );
            }
        }

        return response()->json([
            'payment' => $this->serializePayment($orderPayment),
        ]);
    }

    private function serializePayment(OrderPayment $orderPayment): array
    {
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
        ];
    }
}
