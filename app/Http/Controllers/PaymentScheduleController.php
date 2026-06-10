<?php

namespace App\Http\Controllers;

use App\Models\PaymentInstallment;
use App\Support\OrderFinancialEventLogger;
use App\Support\PaymentInstallmentPresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentScheduleController extends Controller
{
    public function updateInstallment(Request $request, PaymentInstallment $installment)
    {
        $validated = $request->validate([
            'due_date' => ['nullable', 'date'],
        ]);

        $schedule = $installment->schedule()->with('order')->first();
        if (!$schedule || !$schedule->order) {
            throw ValidationException::withMessages([
                'due_date' => 'Payment schedule not found for this installment.',
            ]);
        }

        $hasRecordedPayments = $schedule->installments()->whereHas('movements')->exists();
        if ($hasRecordedPayments && strtoupper((string) $installment->status) !== 'PENDING') {
            throw ValidationException::withMessages([
                'due_date' => 'Only pending installments can be edited after payments are recorded.',
            ]);
        }

        $previousDueDate = $installment->due_date?->format('Y-m-d');
        $nextDueDate = $validated['due_date'] ?? null;
        $installment->due_date = $nextDueDate;
        $installment->save();

        if ($previousDueDate !== $nextDueDate) {
            OrderFinancialEventLogger::log(
                $schedule->order,
                'INSTALLMENT_DUE_DATE_UPDATED',
                "Updated due date for installment '{$installment->label}'",
                [
                    'installment_id' => $installment->id,
                    'installment_label' => $installment->label,
                    'before_due_date' => $previousDueDate,
                    'after_due_date' => $nextDueDate,
                ]
            );
        }

        $installment->load(['paidBy', 'movements.paidBy']);

        return response()->json([
            'installment' => PaymentInstallmentPresenter::installment($installment),
        ]);
    }
}
