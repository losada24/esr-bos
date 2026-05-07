<?php

namespace App\Http\Controllers\Commission;

use App\Exports\CommissionPeriodExport;
use App\Enum\CommissionPaymentKindEnum;
use App\Enum\CommissionPaymentStatusEnum;
use App\Enum\CommissionPeriodStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\StoreCommissionPeriodRequest;
use App\Models\CommissionPeriod;
use App\Models\Order;
use App\Models\OrderCommissionPayment;
use App\Models\OrderStatus;
use App\Support\Commissions\CommissionAuditLogger;
use App\Support\Commissions\CloseCommissionPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class CommissionPeriodController extends Controller
{
    public function index(Request $request): Response
    {
        $periods = CommissionPeriod::query()
            ->with('snapshot')
            ->withCount('payments')
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('CommissionPeriod/Index', [
            'periods' => $periods->through(function (CommissionPeriod $period) {
                $summary = $period->snapshot?->data['summary'] ?? null;

                if ($summary === null && $period->status === CommissionPeriodStatusEnum::OPEN->value) {
                    $payments = OrderCommissionPayment::query()
                        ->where('commission_period_id', $period->id)
                        ->whereIn('status', [
                            CommissionPaymentStatusEnum::REVIEW->value,
                            CommissionPaymentStatusEnum::PAID->value,
                        ])
                        ->with('commission')
                        ->get();

                    $summary = [
                        'payments_count' => $payments->count(),
                        'beneficiaries_count' => $payments
                            ->map(fn (OrderCommissionPayment $payment) => $payment->commission
                                ? $payment->commission->beneficiary_source_type . ':' . $payment->commission->beneficiary_source_id
                                : null)
                            ->filter(fn (?string $key) => $key !== null)
                            ->unique()
                            ->count(),
                        'total_paid' => round((float) $payments->sum('total_to_pay'), 2),
                    ];
                }

                $canReopenOrDeleteClosed = $this->canReopenOrDeleteClosedPeriod($period, $summary);

                return [
                    'id' => $period->id,
                    'label' => $period->label,
                    'status' => $period->status,
                    'start_date' => optional($period->start_date)->toDateString(),
                    'end_date' => optional($period->end_date)->toDateString(),
                    'closed_at' => optional($period->closed_at)->toDateTimeString(),
                    'payments_count' => (int) $period->payments_count,
                    'snapshot_summary' => $summary,
                    'can_edit' => $period->status === CommissionPeriodStatusEnum::OPEN->value,
                    'can_edit_dates' => $period->status === CommissionPeriodStatusEnum::OPEN->value && (int) $period->payments_count === 0,
                    'can_delete' => (
                        $period->status === CommissionPeriodStatusEnum::OPEN->value
                        && (int) $period->payments_count === 0
                        && $period->snapshot === null
                    ) || $canReopenOrDeleteClosed,
                    'can_reopen' => $canReopenOrDeleteClosed,
                ];
            }),
        ]);
    }

    public function store(StoreCommissionPeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $this->ensurePeriodRangeAvailable($startDate, $endDate);

        $period = CommissionPeriod::create([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'label' => $data['label'] ?: $startDate->format('M d') . ' to ' . $endDate->format('M d'),
            'status' => CommissionPeriodStatusEnum::OPEN->value,
        ]);

        CommissionAuditLogger::log('period.created', [
            'after' => $period->toArray(),
        ], null, null, $period, 'commission-periods');

        return back()->with('success', 'Commission period created successfully.');
    }

    public function update(StoreCommissionPeriodRequest $request, CommissionPeriod $commissionPeriod): RedirectResponse
    {
        if ($commissionPeriod->status !== CommissionPeriodStatusEnum::OPEN->value) {
            return back()->with('error', 'Only open commission periods can be edited.');
        }

        $data = $request->validated();
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $hasPayments = $commissionPeriod->payments()->exists();

        if (
            $hasPayments
            && (
                $commissionPeriod->start_date?->toDateString() !== $startDate->toDateString()
                || $commissionPeriod->end_date?->toDateString() !== $endDate->toDateString()
            )
        ) {
            return back()->with('error', 'Periods with assigned payments can only update the label.');
        }

        $effectiveStartDate = $hasPayments ? Carbon::parse($commissionPeriod->start_date) : $startDate;
        $effectiveEndDate = $hasPayments ? Carbon::parse($commissionPeriod->end_date) : $endDate;

        $this->ensurePeriodRangeAvailable($effectiveStartDate, $effectiveEndDate, $commissionPeriod->id);

        $before = $commissionPeriod->toArray();

        $commissionPeriod->update([
            'start_date' => $effectiveStartDate->toDateString(),
            'end_date' => $effectiveEndDate->toDateString(),
            'label' => $data['label'] ?: $effectiveStartDate->format('M d') . ' to ' . $effectiveEndDate->format('M d'),
        ]);

        CommissionAuditLogger::log('period.updated', [
            'before' => $before,
            'after' => $commissionPeriod->fresh()->toArray(),
        ], null, null, $commissionPeriod, 'commission-periods');

        return back()->with('success', 'Commission period updated successfully.');
    }

    public function show(CommissionPeriod $commissionPeriod): Response
    {
        return Inertia::render('CommissionPeriod/Show', [
            'period' => $this->buildShowPayload($commissionPeriod),
        ]);
    }

    public function pdf(Request $request, CommissionPeriod $commissionPeriod)
    {
        [$beneficiarySourceType, $beneficiarySourceId] = $this->resolveBeneficiaryFilter($request);

        $data = [
            'period' => $this->buildShowPayload($commissionPeriod, $beneficiarySourceType, $beneficiarySourceId),
        ];

        $pdf = Pdf::loadView('pdf.commission-period', $data)->setPaper('A4', 'landscape');

        return $pdf->stream($this->buildExportFileName('pdf', $commissionPeriod, $beneficiarySourceType, $beneficiarySourceId));
    }

    public function excel(Request $request, CommissionPeriod $commissionPeriod)
    {
        [$beneficiarySourceType, $beneficiarySourceId] = $this->resolveBeneficiaryFilter($request);
        $periodPayload = $this->buildShowPayload($commissionPeriod, $beneficiarySourceType, $beneficiarySourceId);
        $selectedBeneficiaryName = $periodPayload['selected_beneficiary']['beneficiary_name'] ?? null;

        return Excel::download(
            new CommissionPeriodExport([
                'period' => $periodPayload,
            ]),
            $this->buildExportFileName('xlsx', $commissionPeriod, $beneficiarySourceType, $beneficiarySourceId, $selectedBeneficiaryName),
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function close(CommissionPeriod $commissionPeriod, CloseCommissionPeriod $closeCommissionPeriod): RedirectResponse
    {
        $before = $commissionPeriod->toArray();
        $closeCommissionPeriod->handle($commissionPeriod);

        CommissionAuditLogger::log('period.closed', [
            'before' => $before,
            'after' => $commissionPeriod->fresh()->toArray(),
        ], null, null, $commissionPeriod, 'commission-periods');

        return redirect()
            ->route('commission-periods.show', $commissionPeriod)
            ->with('success', 'Commission period closed successfully.');
    }

    public function unassignPayment(CommissionPeriod $commissionPeriod, OrderCommissionPayment $payment): RedirectResponse
    {
        if ($commissionPeriod->status !== CommissionPeriodStatusEnum::OPEN->value) {
            return back()->with('error', 'Only open commission periods can remove review payments.');
        }

        if ((int) $payment->commission_period_id !== (int) $commissionPeriod->id) {
            return back()->with('error', 'This payment does not belong to the selected commission period.');
        }

        if ($payment->status !== CommissionPaymentStatusEnum::REVIEW->value) {
            return back()->with('error', 'Only review payments can be removed from an open commission period.');
        }

        $before = $payment->toArray();

        $payment->update([
            'commission_period_id' => null,
            'updated_by' => Auth::id(),
        ]);

        CommissionAuditLogger::log('payment.removed_from_period', [
            'before' => $before,
            'after' => $payment->fresh()->toArray(),
        ], $payment->commission, $payment, $commissionPeriod);

        return back()->with('success', 'Payment removed from the commission period.');
    }

    public function reopen(CommissionPeriod $commissionPeriod): RedirectResponse
    {
        if ($commissionPeriod->status !== CommissionPeriodStatusEnum::CLOSED->value) {
            return back()->with('error', 'Only closed commission periods can be reopened.');
        }

        $commissionPeriod->loadCount('payments')->load('snapshot');
        $summary = $commissionPeriod->snapshot?->data['summary'] ?? null;

        if (! $this->canReopenOrDeleteClosedPeriod($commissionPeriod, $summary)) {
            return back()->with('error', 'Only closed periods without payments can be reopened.');
        }

        $before = $commissionPeriod->toArray();

        $commissionPeriod->update([
            'status' => CommissionPeriodStatusEnum::OPEN->value,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        $commissionPeriod->snapshot()?->delete();

        CommissionAuditLogger::log('period.reopened', [
            'before' => $before,
            'after' => $commissionPeriod->fresh()->toArray(),
        ], null, null, $commissionPeriod, 'commission-periods');

        return back()->with('success', 'Commission period reopened successfully.');
    }

    public function destroy(CommissionPeriod $commissionPeriod): RedirectResponse
    {
        $commissionPeriod->loadCount('payments')->load('snapshot');
        $summary = $commissionPeriod->snapshot?->data['summary'] ?? null;
        $canDeleteOpen = $commissionPeriod->status === CommissionPeriodStatusEnum::OPEN->value
            && (int) $commissionPeriod->payments_count === 0
            && $commissionPeriod->snapshot === null;
        $canDeleteClosed = $this->canReopenOrDeleteClosedPeriod($commissionPeriod, $summary);

        if (! $canDeleteOpen && ! $canDeleteClosed) {
            return back()->with('error', 'Only empty commission periods can be deleted.');
        }

        $before = $commissionPeriod->toArray();

        CommissionAuditLogger::log('period.deleted', [
            'before' => $before,
        ], null, null, $commissionPeriod, 'commission-periods');

        $commissionPeriod->forceDelete();

        return back()->with('success', 'Commission period deleted successfully.');
    }

    private function formatDateValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->toDateString();
        }

        return Carbon::parse((string) $value)->toDateString();
    }

    private function buildShowPayload(
        CommissionPeriod $commissionPeriod,
        ?string $beneficiarySourceType = null,
        ?string $beneficiarySourceId = null
    ): array
    {
        $commissionPeriod->load('snapshot');
        $snapshot = $commissionPeriod->status === CommissionPeriodStatusEnum::OPEN->value
            ? $this->buildOpenPeriodData($commissionPeriod)
            : $commissionPeriod->snapshot?->data;

        if ($snapshot && ! empty($snapshot['payments'])) {
            $paymentRecords = OrderCommissionPayment::withTrashed()
                ->with([
                    'commission' => fn ($commissionQuery) => $commissionQuery
                        ->withTrashed()
                        ->with([
                            'order' => fn ($orderQuery) => $orderQuery
                                ->withTrashed()
                                ->with(['owners:id,name', 'orderStatus']),
                        ]),
                ])
                ->whereIn('id', collect($snapshot['payments'])->pluck('payment_id')->filter()->all())
                ->get()
                ->keyBy('id');

            $snapshot['payments'] = collect($snapshot['payments'])
                ->map(function (array $payment) use ($paymentRecords) {
                    $paymentRecord = $paymentRecords->get($payment['payment_id'] ?? null);
                    $commissionRecord = $paymentRecord?->commission;
                    $orderRecord = $commissionRecord?->order;

                    if (empty($payment['paid_at']) && $paymentRecord) {
                        $payment['paid_at'] = $this->formatDateValue($paymentRecord->paid_at);
                    }

                    if ($paymentRecord) {
                        $payment['payment_kind'] ??= $paymentRecord->payment_kind ?: CommissionPaymentKindEnum::REGULAR->value;
                        $payment['status'] ??= $paymentRecord->status;
                        $payment['split_type'] ??= $paymentRecord->split_type;
                        $payment['split_value'] ??= (float) $paymentRecord->split_value;
                        $payment['payment_base_amount'] ??= (float) $paymentRecord->payment_base_amount;
                        $payment['payment_other_cost_amount'] ??= (float) $paymentRecord->other_cost_amount;
                        $payment['payment_total_to_pay'] ??= (float) $paymentRecord->total_to_pay;
                        $payment['payment_notes'] ??= $paymentRecord->notes;
                        $payment['can_unassign'] ??= false;
                    }

                    if ($orderRecord) {
                        $payment['accounting_status'] ??= $this->resolveAccountingStatusValue($orderRecord);
                        $payment['accounting_status_date'] ??= $this->resolveAccountingStatusDate($orderRecord);
                        $payment['order'] = array_merge([
                            'id' => $orderRecord->id,
                            'name' => $orderRecord->name,
                            'status' => $orderRecord->status,
                            'order_number' => $orderRecord->order_number,
                            'invoice_number' => $orderRecord->invoice_number,
                            'project_payment_method' => $orderRecord->method_of_payment,
                            'type_of_financing' => $orderRecord->type_of_financing,
                            'project_amount' => (float) (($commissionRecord?->project_amount_snapshot ?? null) ?? ($orderRecord->project_amount ?? 0)),
                            'cost_city_fee' => (float) ($orderRecord->cost_city_fee ?? 0),
                            'owners' => $orderRecord->owners->pluck('name')->values()->all(),
                        ], $payment['order'] ?? []);
                    }

                    if ($commissionRecord) {
                        $payment['commission'] = array_merge([
                            'id' => $commissionRecord->id,
                            'status' => $commissionRecord->status,
                            'calculation_type' => $commissionRecord->calculation_type,
                            'percentage_value' => $commissionRecord->percentage_value !== null ? (float) $commissionRecord->percentage_value : null,
                            'fixed_amount' => $commissionRecord->fixed_amount !== null ? (float) $commissionRecord->fixed_amount : null,
                            'project_amount_snapshot' => (float) ($commissionRecord->project_amount_snapshot ?? 0),
                            'fee_amount_snapshot' => (float) ($commissionRecord->fee_amount_snapshot ?? 0),
                            'financing_fee_amount' => (float) ($commissionRecord->financing_fee_amount ?? 0),
                            'base_amount_snapshot' => (float) ($commissionRecord->base_amount_snapshot ?? 0),
                            'commission_amount' => (float) ($commissionRecord->commission_amount ?? 0),
                            'commission_other_cost_amount' => (float) ($commissionRecord->other_cost_amount ?? 0),
                            'commission_total_amount' => (float) ($commissionRecord->total_amount ?? 0),
                            'commission_paid_amount' => (float) ($commissionRecord->paid_amount ?? 0),
                            'commission_pending_amount' => (float) ($commissionRecord->pending_amount ?? 0),
                            'beneficiary_source_type' => $commissionRecord->beneficiary_source_type,
                            'beneficiary_source_id' => $commissionRecord->beneficiary_source_id,
                            'beneficiary_relation' => $commissionRecord->beneficiary_relation,
                            'beneficiary_name' => $commissionRecord->beneficiary_name_snapshot,
                            'beneficiary_email' => $commissionRecord->beneficiary_email_snapshot,
                        ], $payment['commission'] ?? []);
                    }

                    return $payment;
                })
                ->values()
                ->all();
        }

        $beneficiaryTotals = collect($snapshot['summary']['beneficiary_totals'] ?? [])
            ->map(function (array $item) use ($snapshot) {
                if (isset($item['beneficiary_source_type'], $item['beneficiary_source_id'])) {
                    return $item;
                }

                $matchedPayment = collect($snapshot['payments'] ?? [])
                    ->first(function (array $payment) use ($item) {
                        return ($payment['commission']['beneficiary_name'] ?? null) === ($item['beneficiary_name'] ?? null)
                            && ($payment['commission']['beneficiary_relation'] ?? null) === ($item['beneficiary_relation'] ?? null);
                    });

                if (! $matchedPayment) {
                    return $item;
                }

                $item['beneficiary_source_type'] = $matchedPayment['commission']['beneficiary_source_type'] ?? null;
                $item['beneficiary_source_id'] = $matchedPayment['commission']['beneficiary_source_id'] ?? null;

                return $item;
            })
            ->values();

        $selectedBeneficiary = null;

        if ($beneficiarySourceType !== null && $beneficiarySourceId !== null) {
            $filteredPayments = collect($snapshot['payments'] ?? [])
                ->filter(function (array $payment) use ($beneficiarySourceType, $beneficiarySourceId) {
                    return (string) ($payment['commission']['beneficiary_source_type'] ?? '') === $beneficiarySourceType
                        && (string) ($payment['commission']['beneficiary_source_id'] ?? '') === $beneficiarySourceId;
                })
                ->values();

            $beneficiaryTotals = $beneficiaryTotals
                ->filter(function (array $item) use ($beneficiarySourceType, $beneficiarySourceId) {
                    return (string) ($item['beneficiary_source_type'] ?? '') === $beneficiarySourceType
                        && (string) ($item['beneficiary_source_id'] ?? '') === $beneficiarySourceId;
                })
                ->values();

            $selectedBeneficiary = $beneficiaryTotals->first();

            $snapshot['payments'] = $filteredPayments->all();
            $snapshot['summary'] = [
                'payments_count' => $filteredPayments->count(),
                'orders_count' => $filteredPayments->pluck('order.id')->filter()->unique()->count(),
                'commissions_count' => $filteredPayments->pluck('commission.id')->filter()->unique()->count(),
                'beneficiaries_count' => $beneficiaryTotals->count(),
                'total_paid' => round($filteredPayments->sum('payment_total_to_pay'), 2),
                'beneficiary_totals' => $beneficiaryTotals->all(),
            ];
        } elseif ($snapshot) {
            $snapshot['summary']['beneficiary_totals'] = $beneficiaryTotals->all();
        }

        return [
            'id' => $commissionPeriod->id,
            'label' => $commissionPeriod->label,
            'status' => $commissionPeriod->status,
            'start_date' => optional($commissionPeriod->start_date)->toDateString(),
            'end_date' => optional($commissionPeriod->end_date)->toDateString(),
            'closed_at' => optional($commissionPeriod->closed_at)->toDateTimeString(),
            'snapshot' => $snapshot,
            'selected_beneficiary' => $selectedBeneficiary,
        ];
    }

    private function buildOpenPeriodData(CommissionPeriod $commissionPeriod): array
    {
        $payments = OrderCommissionPayment::query()
            ->with([
                'commission' => fn ($commissionQuery) => $commissionQuery
                    ->withTrashed()
                    ->with([
                        'order' => fn ($orderQuery) => $orderQuery
                            ->withTrashed()
                            ->with(['owners:id,name', 'orderStatus']),
                    ]),
            ])
            ->where('commission_period_id', $commissionPeriod->id)
            ->whereIn('status', [
                CommissionPaymentStatusEnum::REVIEW->value,
                CommissionPaymentStatusEnum::PAID->value,
            ])
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $paymentRows = $payments
            ->map(fn (OrderCommissionPayment $payment) => $this->serializePeriodPayment($payment, true))
            ->values()
            ->all();

        $beneficiaryTotals = $payments
            ->filter(fn (OrderCommissionPayment $payment) => $payment->commission !== null)
            ->groupBy(fn (OrderCommissionPayment $payment) => $payment->commission->beneficiary_source_type . ':' . $payment->commission->beneficiary_source_id)
            ->map(function ($group) {
                /** @var OrderCommissionPayment $first */
                $first = $group->first();
                $commission = $first->commission;

                return [
                    'beneficiary_source_type' => $commission->beneficiary_source_type,
                    'beneficiary_source_id' => $commission->beneficiary_source_id,
                    'beneficiary_relation' => $commission->beneficiary_relation,
                    'beneficiary_name' => $commission->beneficiary_name_snapshot,
                    'beneficiary_email' => $commission->beneficiary_email_snapshot,
                    'total_paid' => round($group->sum('total_to_pay'), 2),
                    'payments_count' => $group->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'summary' => [
                'payments_count' => count($paymentRows),
                'orders_count' => collect($paymentRows)->pluck('order.id')->filter()->unique()->count(),
                'commissions_count' => collect($paymentRows)->pluck('commission.id')->filter()->unique()->count(),
                'beneficiaries_count' => count($beneficiaryTotals),
                'total_paid' => round(collect($paymentRows)->sum('payment_total_to_pay'), 2),
                'beneficiary_totals' => $beneficiaryTotals,
            ],
            'payments' => $paymentRows,
        ];
    }

    private function serializePeriodPayment(OrderCommissionPayment $payment, bool $isOpenPeriod = false): array
    {
        $commission = $payment->commission;
        $order = $commission?->order;

        return [
            'payment_id' => $payment->id,
            'sequence' => $payment->sequence,
            'payment_kind' => $payment->payment_kind ?: CommissionPaymentKindEnum::REGULAR->value,
            'status' => $payment->status,
            'paid_at' => $this->formatDateValue($payment->paid_at),
            'split_type' => $payment->split_type,
            'split_value' => (float) $payment->split_value,
            'payment_base_amount' => (float) $payment->payment_base_amount,
            'payment_other_cost_amount' => (float) $payment->other_cost_amount,
            'payment_total_to_pay' => (float) $payment->total_to_pay,
            'payment_notes' => $payment->notes,
            'can_unassign' => $isOpenPeriod && $payment->status === CommissionPaymentStatusEnum::REVIEW->value,
            'accounting_status' => $order ? $this->resolveAccountingStatusValue($order) : null,
            'accounting_status_date' => $order ? $this->resolveAccountingStatusDate($order) : null,
            'order' => [
                'id' => $order?->id,
                'name' => $order?->name,
                'status' => $order?->status,
                'order_number' => $order?->order_number,
                'invoice_number' => $order?->invoice_number,
                'project_payment_method' => $order?->method_of_payment,
                'type_of_financing' => $order?->type_of_financing,
                'project_amount' => (float) (($commission?->project_amount_snapshot ?? null) ?? ($order?->project_amount ?? 0)),
                'cost_city_fee' => (float) ($order?->cost_city_fee ?? 0),
                'owners' => $order?->owners?->pluck('name')->values()->all() ?? [],
            ],
            'commission' => [
                'id' => $commission?->id,
                'status' => $commission?->status,
                'calculation_type' => $commission?->calculation_type,
                'percentage_value' => $commission?->percentage_value !== null ? (float) $commission->percentage_value : null,
                'fixed_amount' => $commission?->fixed_amount !== null ? (float) $commission->fixed_amount : null,
                'project_amount_snapshot' => (float) ($commission?->project_amount_snapshot ?? 0),
                'fee_amount_snapshot' => (float) ($commission?->fee_amount_snapshot ?? 0),
                'financing_fee_amount' => (float) ($commission?->financing_fee_amount ?? 0),
                'base_amount_snapshot' => (float) ($commission?->base_amount_snapshot ?? 0),
                'commission_amount' => (float) ($commission?->commission_amount ?? 0),
                'commission_other_cost_amount' => (float) ($commission?->other_cost_amount ?? 0),
                'commission_total_amount' => (float) ($commission?->total_amount ?? 0),
                'commission_paid_amount' => (float) ($commission?->paid_amount ?? 0),
                'commission_pending_amount' => (float) ($commission?->pending_amount ?? 0),
                'beneficiary_source_type' => $commission?->beneficiary_source_type,
                'beneficiary_source_id' => $commission?->beneficiary_source_id,
                'beneficiary_relation' => $commission?->beneficiary_relation,
                'beneficiary_name' => $commission?->beneficiary_name_snapshot,
                'beneficiary_email' => $commission?->beneficiary_email_snapshot,
            ],
        ];
    }

    private function resolveBeneficiaryFilter(Request $request): array
    {
        $beneficiarySourceType = $request->filled('beneficiary_source_type')
            ? (string) $request->input('beneficiary_source_type')
            : null;
        $beneficiarySourceId = $request->filled('beneficiary_source_id')
            ? (string) $request->input('beneficiary_source_id')
            : null;

        return [$beneficiarySourceType, $beneficiarySourceId];
    }

    private function buildExportFileName(
        string $extension,
        CommissionPeriod $commissionPeriod,
        ?string $beneficiarySourceType,
        ?string $beneficiarySourceId,
        ?string $beneficiaryName = null
    ): string {
        if ($beneficiarySourceType !== null && $beneficiarySourceId !== null && $beneficiaryName !== null) {
            return $this->sanitizeExportFileName($commissionPeriod->label . ' - ' . $beneficiaryName) . '.' . $extension;
        }

        $fileName = 'commission-period-' . $commissionPeriod->id;
        if ($beneficiarySourceType !== null && $beneficiarySourceId !== null) {
            $fileName .= '-beneficiary-' . strtolower($beneficiarySourceType) . '-' . $beneficiarySourceId;
        }

        return $fileName . '.' . $extension;
    }

    private function sanitizeExportFileName(string $fileName): string
    {
        $fileName = trim((string) preg_replace('/[\\\\\/:*?"<>|]+/', '-', $fileName));
        $fileName = trim((string) preg_replace('/\s+/', ' ', $fileName));

        return $fileName !== '' ? $fileName : 'commission-period';
    }

    private function resolveAccountingStatusValue(Order $order): ?string
    {
        return $this->resolveAccountingStatusRecord($order)?->status ?? $order->status;
    }

    private function resolveAccountingStatusDate(Order $order): ?string
    {
        return $this->resolveAccountingStatusRecord($order)?->created_at?->toDateTimeString();
    }

    private function resolveAccountingStatusRecord(Order $order): ?OrderStatus
    {
        return $order->orderStatus
            ->whereIn('status', [
                OrderStatusEnum::ACCOUNT_RECEIPT->value,
                OrderStatusEnum::COMPLETE->value,
            ])
            ->sortByDesc(function (OrderStatus $orderStatus) {
                return sprintf(
                    '%010d-%010d',
                    $orderStatus->created_at?->timestamp ?? 0,
                    $orderStatus->id
                );
            })
            ->first();
    }

    private function ensurePeriodRangeAvailable(Carbon $startDate, Carbon $endDate, ?int $ignoreId = null): void
    {
        $query = CommissionPeriod::withTrashed()
            ->whereDate('start_date', $startDate->toDateString())
            ->whereDate('end_date', $endDate->toDateString());

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if (! $query->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'start_date' => 'A commission period already exists for the selected date range.',
            'end_date' => 'A commission period already exists for the selected date range.',
        ]);
    }

    private function canReopenOrDeleteClosedPeriod(CommissionPeriod $period, ?array $summary): bool
    {
        $snapshotPaymentsCount = (int) ($summary['payments_count'] ?? 0);

        return $period->status === CommissionPeriodStatusEnum::CLOSED->value
            && (int) $period->payments_count === 0
            && $snapshotPaymentsCount === 0;
    }
}
