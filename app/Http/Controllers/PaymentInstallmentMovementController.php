<?php

namespace App\Http\Controllers;

use App\Models\PaymentInstallment;
use App\Models\PaymentInstallmentMovement;
use App\Support\OrderFinancialEventLogger;
use App\Support\PaymentInstallmentAccounting;
use App\Support\PaymentInstallmentPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentInstallmentMovementController extends Controller
{
    private const EPSILON = 0.01;

    public function store(Request $request, PaymentInstallment $installment)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'paid_at' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $amount = round((float) $validated['amount'], 2);
        $remainingCapacity = $this->remainingScheduleCapacity($installment);
        if ($amount - $remainingCapacity > self::EPSILON) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds remaining schedule total. Maximum allowed now is ' . number_format($remainingCapacity, 2, '.', ''),
            ]);
        }

        $movement = DB::transaction(function () use ($validated, $installment) {
            $movement = $installment->movements()->create([
                'amount' => round((float) $validated['amount'], 2),
                'paid_at' => $validated['paid_at'] ?? now(),
                'paid_by' => auth()->id(),
                'method' => $validated['method'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);

            $installment->syncPaymentState();

            $installment->loadMissing('schedule.order');
            $order = $installment->schedule?->order;
            if ($order) {
                OrderFinancialEventLogger::log(
                    $order,
                    'INSTALLMENT_PAYMENT_ADDED',
                    "Payment recorded for installment '{$installment->label}'",
                    [
                        'installment_id' => $installment->id,
                        'installment_label' => $installment->label,
                        'movement_id' => $movement->id,
                        'amount' => (float) $movement->amount,
                        'paid_at' => $movement->paid_at?->toISOString(),
                        'method' => $movement->method,
                        'note' => $movement->note,
                    ]
                );
            }

            return $movement;
        });

        $movement->load('paidBy');
        $installment->load(['paidBy', 'movements.paidBy']);

        return response()->json([
            'movement' => [
                'id' => $movement->id,
                'amount' => (float) $movement->amount,
                'paid_at' => $movement->paid_at?->toISOString(),
                'method' => $movement->method,
                'note' => $movement->note,
                'paid_by' => $movement->paidBy
                    ? ['id' => $movement->paidBy->id, 'name' => $movement->paidBy->name]
                    : null,
                'created_at' => $movement->created_at?->toISOString(),
                'updated_at' => $movement->updated_at?->toISOString(),
            ],
            'installment' => PaymentInstallmentPresenter::installment($installment),
        ]);
    }

    public function update(Request $request, PaymentInstallmentMovement $movement)
    {
        if ($this->isCreditTransferMovement($movement)) {
            throw ValidationException::withMessages([
                'movement' => 'Credit transfer movements cannot be edited directly.',
            ]);
        }

        $validated = $request->validate([
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'paid_at' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $installment = $movement->installment;
        $before = [
            'amount' => round((float) $movement->amount, 2),
            'paid_at' => $movement->paid_at?->toISOString(),
            'method' => $movement->method,
            'note' => $movement->note,
        ];
        $currentAmount = round((float) $movement->amount, 2);
        $nextAmount = array_key_exists('amount', $validated)
            ? round((float) $validated['amount'], 2)
            : $currentAmount;
        $delta = $nextAmount - $currentAmount;
        if ($delta > self::EPSILON) {
            $remainingCapacity = $this->remainingScheduleCapacity($installment);
            if ($delta - $remainingCapacity > self::EPSILON) {
                throw ValidationException::withMessages([
                    'amount' => 'Amount exceeds remaining schedule total. Maximum allowed now is ' . number_format($currentAmount + $remainingCapacity, 2, '.', ''),
                ]);
            }
        }

        DB::transaction(function () use ($validated, $movement, $installment, $before) {
            if (array_key_exists('amount', $validated)) {
                $movement->amount = round((float) $validated['amount'], 2);
            }

            if (array_key_exists('paid_at', $validated)) {
                $movement->paid_at = $validated['paid_at'] ?? now();
            }

            if (array_key_exists('method', $validated)) {
                $movement->method = $validated['method'];
            }

            if (array_key_exists('note', $validated)) {
                $movement->note = $validated['note'];
            }

            $movement->save();
            $installment->syncPaymentState();

            $installment->loadMissing('schedule.order');
            $order = $installment->schedule?->order;
            if ($order) {
                OrderFinancialEventLogger::log(
                    $order,
                    'INSTALLMENT_PAYMENT_UPDATED',
                    "Payment movement updated for installment '{$installment->label}'",
                    [
                        'installment_id' => $installment->id,
                        'installment_label' => $installment->label,
                        'movement_id' => $movement->id,
                        'before' => $before,
                        'after' => [
                            'amount' => (float) $movement->amount,
                            'paid_at' => $movement->paid_at?->toISOString(),
                            'method' => $movement->method,
                            'note' => $movement->note,
                        ],
                    ]
                );
            }
        });

        $movement->load('paidBy');
        $installment->load(['paidBy', 'movements.paidBy']);

        return response()->json([
            'movement' => [
                'id' => $movement->id,
                'amount' => (float) $movement->amount,
                'paid_at' => $movement->paid_at?->toISOString(),
                'method' => $movement->method,
                'note' => $movement->note,
                'paid_by' => $movement->paidBy
                    ? ['id' => $movement->paidBy->id, 'name' => $movement->paidBy->name]
                    : null,
                'created_at' => $movement->created_at?->toISOString(),
                'updated_at' => $movement->updated_at?->toISOString(),
            ],
            'installment' => PaymentInstallmentPresenter::installment($installment),
        ]);
    }

    public function void(Request $request, PaymentInstallmentMovement $movement)
    {
        if ($this->isCreditTransferMovement($movement)) {
            throw ValidationException::withMessages([
                'movement' => 'Credit transfer movements cannot be deleted directly.',
            ]);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $installment = $movement->installment;
        $before = [
            'movement_id' => $movement->id,
            'amount' => (float) $movement->amount,
            'paid_at' => $movement->paid_at?->toISOString(),
            'method' => $movement->method,
            'note' => $movement->note,
        ];

        DB::transaction(function () use ($validated, $movement, $installment, $before) {
            if (!empty($validated['note'])) {
                $movement->note = trim($movement->note . PHP_EOL . '[VOID] ' . $validated['note']);
                $movement->save();
            }

            $movement->delete();
            $installment->syncPaymentState();

            $installment->loadMissing('schedule.order');
            $order = $installment->schedule?->order;
            if ($order) {
                OrderFinancialEventLogger::log(
                    $order,
                    'INSTALLMENT_PAYMENT_VOIDED',
                    "Payment movement deleted for installment '{$installment->label}'",
                    [
                        'installment_id' => $installment->id,
                        'installment_label' => $installment->label,
                        'void_note' => $validated['note'] ?? null,
                        'movement' => $before,
                    ]
                );
            }
        });

        $installment->load(['paidBy', 'movements.paidBy']);

        return response()->json([
            'installment' => PaymentInstallmentPresenter::installment($installment),
        ]);
    }

    public function transferCredit(Request $request, PaymentInstallment $installment)
    {
        $validated = $request->validate([
            'target_installment_id' => ['required', 'integer', 'exists:payment_installments,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $source = $installment->load('schedule.order');
        $target = PaymentInstallment::query()
            ->with('schedule.order')
            ->findOrFail((int) $validated['target_installment_id']);

        if ((int) $source->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'target_installment_id' => 'Select a different installment to receive the credit.',
            ]);
        }

        if (!$source->schedule || !$target->schedule || (int) $source->payment_schedule_id !== (int) $target->payment_schedule_id) {
            throw ValidationException::withMessages([
                'target_installment_id' => 'Credit can only be applied within the same payment schedule.',
            ]);
        }

        $amount = round((float) $validated['amount'], 2);
        $sourceSummary = PaymentInstallmentAccounting::summarize(
            (float) $source->amount,
            (float) $source->movements()->sum('amount')
        );
        $targetSummary = PaymentInstallmentAccounting::summarize(
            (float) $target->amount,
            (float) $target->movements()->sum('amount')
        );

        $sourceCredit = round((float) $sourceSummary['credit'], 2);
        $targetBalance = round((float) $targetSummary['balance'], 2);

        if ($sourceCredit <= self::EPSILON) {
            throw ValidationException::withMessages([
                'amount' => 'This installment does not have credit available to transfer.',
            ]);
        }

        if ($targetBalance <= self::EPSILON) {
            throw ValidationException::withMessages([
                'target_installment_id' => 'The selected installment has no balance due.',
            ]);
        }

        if ($amount - $sourceCredit > self::EPSILON) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds available credit. Maximum allowed is ' . number_format($sourceCredit, 2, '.', ''),
            ]);
        }

        if ($amount - $targetBalance > self::EPSILON) {
            throw ValidationException::withMessages([
                'amount' => 'Amount exceeds target balance. Maximum allowed is ' . number_format($targetBalance, 2, '.', ''),
            ]);
        }

        DB::transaction(function () use ($source, $target, $amount, $validated) {
            $note = trim((string) ($validated['note'] ?? ''));
            $sourceNote = trim(sprintf(
                "[CREDIT_TRANSFER] Credit applied to installment '%s'.%s",
                $target->label,
                $note !== '' ? PHP_EOL . $note : ''
            ));
            $targetNote = trim(sprintf(
                "[CREDIT_TRANSFER] Credit applied from installment '%s'.%s",
                $source->label,
                $note !== '' ? PHP_EOL . $note : ''
            ));

            $sourceMovement = $source->movements()->create([
                'amount' => -1 * $amount,
                'paid_at' => now(),
                'paid_by' => auth()->id(),
                'method' => 'CREDIT_TRANSFER',
                'note' => $sourceNote,
            ]);

            $targetMovement = $target->movements()->create([
                'amount' => $amount,
                'paid_at' => now(),
                'paid_by' => auth()->id(),
                'method' => 'CREDIT_TRANSFER',
                'note' => $targetNote,
            ]);

            $source->syncPaymentState();
            $target->syncPaymentState();

            $order = $source->schedule?->order;
            if ($order) {
                OrderFinancialEventLogger::log(
                    $order,
                    'INSTALLMENT_CREDIT_TRANSFERRED',
                    "Credit transferred from installment '{$source->label}' to '{$target->label}'",
                    [
                        'source_installment_id' => $source->id,
                        'source_installment_label' => $source->label,
                        'source_movement_id' => $sourceMovement->id,
                        'target_installment_id' => $target->id,
                        'target_installment_label' => $target->label,
                        'target_movement_id' => $targetMovement->id,
                        'amount' => $amount,
                        'note' => $note !== '' ? $note : null,
                    ]
                );
            }
        });

        $source->load(['paidBy', 'movements.paidBy']);
        $target->load(['paidBy', 'movements.paidBy']);

        return response()->json([
            'source_installment' => PaymentInstallmentPresenter::installment($source),
            'target_installment' => PaymentInstallmentPresenter::installment($target),
        ]);
    }

    private function remainingScheduleCapacity(PaymentInstallment $installment): float
    {
        $schedule = $installment->schedule()
            ->with('order.changeOrderPayment')
            ->first();
        if (!$schedule) {
            throw ValidationException::withMessages([
                'amount' => 'Payment schedule not found for this installment.',
            ]);
        }

        $scheduleTotal = round((float) $schedule->total_amount, 2);
        $changeOrderAmount = round((float) ($schedule->order?->changeOrderPayment?->amount ?? 0), 2);
        if ($changeOrderAmount < 0) {
            $scheduleTotal = max(0, round($scheduleTotal + $changeOrderAmount, 2));
        }

        $paidTotal = (float) PaymentInstallmentMovement::query()
            ->whereHas('installment', function ($query) use ($schedule) {
                $query->where('payment_schedule_id', $schedule->id);
            })
            ->sum('amount');

        return max(0, round($scheduleTotal - $paidTotal, 2));
    }

    private function isCreditTransferMovement(PaymentInstallmentMovement $movement): bool
    {
        return strtoupper((string) $movement->method) === 'CREDIT_TRANSFER';
    }
}
