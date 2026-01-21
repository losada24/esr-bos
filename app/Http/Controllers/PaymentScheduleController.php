<?php

namespace App\Http\Controllers;

use App\Enum\PaymentInstallmentStatusEnum;
use App\Models\PaymentInstallment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentScheduleController extends Controller
{
    public function updateInstallment(Request $request, PaymentInstallment $installment)
    {
        $previousStatus = $installment->status;
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_map(
                fn (PaymentInstallmentStatusEnum $status) => $status->value,
                PaymentInstallmentStatusEnum::cases()
            ))],
            'due_date' => ['nullable', 'date'],
        ]);

        $installment->status = $validated['status'];
        $installment->due_date = $validated['due_date'] ?? null;

        if ($validated['status'] === PaymentInstallmentStatusEnum::PAID->value) {
            if ($previousStatus !== PaymentInstallmentStatusEnum::PAID->value) {
                $installment->paid_by = auth()->id();
            }
            $installment->paid_at = $installment->paid_at ?? now();
        }

        if ($validated['status'] === PaymentInstallmentStatusEnum::PENDING->value) {
            $installment->paid_at = null;
            $installment->paid_by = null;
        }

        $installment->save();
        $installment->load('paidBy');

        return response()->json([
            'installment' => [
                'id' => $installment->id,
                'label' => $installment->label,
                'percentage' => $installment->percentage,
                'amount' => $installment->amount,
                'due_date' => $installment->due_date?->format('Y-m-d'),
                'status' => $installment->status,
                'paid_at' => $installment->paid_at?->toISOString(),
                'position' => $installment->position,
                'paid_by' => $installment->paidBy
                    ? ['id' => $installment->paidBy->id, 'name' => $installment->paidBy->name]
                    : null,
            ],
        ]);
    }
}
