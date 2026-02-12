<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderPaymentController extends Controller
{
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

        return response()->json([
            'payment' => [
                'id' => $orderPayment->id,
                'order_id' => $orderPayment->order_id,
                'type' => $orderPayment->type,
                'amount' => $orderPayment->amount,
                'note' => $orderPayment->note,
                'status' => $orderPayment->status,
                'paid_at' => $orderPayment->paid_at?->toISOString(),
                'paid_by' => $orderPayment->paidBy
                    ? ['id' => $orderPayment->paidBy->id, 'name' => $orderPayment->paidBy->name]
                    : null,
            ],
        ]);
    }
}
