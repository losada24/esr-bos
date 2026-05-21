<?php

namespace App\Http\Controllers\Commission;

use App\Exports\CommissionReportExport;
use App\Enum\CommissionPeriodStatusEnum;
use App\Enum\CommissionBeneficiaryRelationEnum;
use App\Enum\CommissionBeneficiarySourceEnum;
use App\Enum\CommissionCalculationTypeEnum;
use App\Enum\CommissionPaymentKindEnum;
use App\Enum\CommissionPaymentStatusEnum;
use App\Enum\CommissionSplitTypeEnum;
use App\Enum\CommissionStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\StatusUserEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\BulkPayCommissionPaymentsRequest;
use App\Http\Requests\Commission\StoreOrderCommissionPaymentRequest;
use App\Http\Requests\Commission\StoreOrderCommissionRequest;
use App\Http\Requests\Commission\UpdateOrderCommissionPaymentRequest;
use App\Http\Requests\Commission\UpdateOrderCommissionRequest;
use App\Models\CommissionPeriod;
use App\Models\ExternalCommissionBeneficiary;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderCommissionAudit;
use App\Models\OrderCommissionPayment;
use App\Models\OrderStatus;
use App\Models\Referral;
use App\Models\User;
use App\Support\Commissions\CommissionAuditLogger;
use App\Support\Commissions\CommissionCalculator;
use App\Support\Commissions\CommissionHistoryPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class CommissionReportController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Commission/Index', $this->buildIndexData($request));
    }

    public function pdf(Request $request)
    {
        $data = $this->buildIndexData($request);
        $view = ($data['selectedView'] ?? 'commissions') === 'payments'
            ? 'pdf.commission-payments'
            : 'pdf.commissions';
        $pdf = Pdf::loadView($view, $data)->setPaper('A4', 'landscape');

        return $pdf->stream('commissions-report.pdf');
    }

    public function excel(Request $request)
    {
        $data = $this->buildIndexData($request);

        return Excel::download(
            new CommissionReportExport($data),
            'Commissions Report.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function bulkPay(BulkPayCommissionPaymentsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $period = CommissionPeriod::query()->findOrFail($data['commission_period_id']);

        if ($period->status !== CommissionPeriodStatusEnum::OPEN->value) {
            return back()->with('error', 'Only open commission periods can receive payments.');
        }

        $payments = OrderCommissionPayment::query()
            ->with('commission')
            ->whereIn('id', $data['payment_ids'])
            ->where('status', CommissionPaymentStatusEnum::REVIEW->value)
            ->whereNull('commission_period_id')
            ->get();

        if ($payments->isEmpty()) {
            return back()->with('error', 'No review payments were selected.');
        }

        DB::transaction(function () use ($payments, $period) {
            foreach ($payments as $payment) {
                $before = $payment->toArray();

                $payment->update([
                    'commission_period_id' => $period->id,
                    'updated_by' => Auth::id(),
                ]);

                CommissionAuditLogger::log('payment.assigned_to_period', [
                    'before' => $before,
                    'after' => $payment->fresh()->toArray(),
                ], $payment->commission, $payment, $period);
            }
        });

        return back()->with('success', 'Selected review payments were assigned to the period.');
    }

    public function editOrder(Order $order, CommissionHistoryPresenter $historyPresenter): Response
    {
        $order->load([
            'owners:id,name,email',
            'changeOrderPayment:id,order_id,amount',
            'orderCommissions' => fn ($query) => $query
                ->with(['payments' => fn ($paymentQuery) => $paymentQuery->orderBy('sequence'), 'nextPayment'])
                ->orderBy('id'),
        ]);

        $activeUsers = User::query()
            ->select('id', 'name', 'email')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name')
            ->limit(500)
            ->get();

        $remeasurers = User::role(RoleEnum::REMEASURER->value)
            ->select('id', 'name', 'email')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->orderBy('name')
            ->limit(500)
            ->get();

        $referrals = Referral::query()
            ->select('id', 'name', 'email', 'phone', 'type')
            ->orderBy('name')
            ->limit(500)
            ->get();

        $externals = ExternalCommissionBeneficiary::query()
            ->select('id', 'name', 'email', 'phone', 'company_name')
            ->where('active', true)
            ->orderBy('name')
            ->limit(500)
            ->get();

        $historyEntries = OrderCommissionAudit::query()
            ->with([
                'user:id,name',
                'payment' => fn ($paymentQuery) => $paymentQuery->withTrashed()->with([
                    'commission' => fn ($commissionQuery) => $commissionQuery->withTrashed()->with([
                        'order' => fn ($orderQuery) => $orderQuery->withTrashed(),
                    ]),
                ]),
                'commission' => fn ($commissionQuery) => $commissionQuery->withTrashed()->with([
                    'order' => fn ($orderQuery) => $orderQuery->withTrashed(),
                ]),
                'period',
            ])
            ->whereHas('commission', function ($commissionQuery) use ($order) {
                $commissionQuery->where('order_id', $order->id);
            })
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (OrderCommissionAudit $audit) => $historyPresenter->serializeAudit($audit))
            ->values();

        return Inertia::render('Commission/EditOrder', [
            'order' => [
                'id' => $order->id,
                'name' => $order->name,
                'status' => $order->status,
                'project_amount' => (float) ($order->project_amount ?? 0),
                'change_order_amount' => (float) ($order->changeOrderPayment?->amount ?? 0),
                'commission_project_amount' => $this->resolveProjectAmountForNewCommission($order),
                'has_paid_regular_commission_payment' => $this->orderHasAnyPaidRegularCommissionPayment($order),
                'cost_city_fee' => (float) ($order->cost_city_fee ?? 0),
                'owners' => $order->owners->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
            'commissions' => $order->orderCommissions->map(fn (OrderCommission $commission) => $this->serializeCommission($commission))->values(),
            'historyEntries' => $historyEntries,
            'activeUsers' => $activeUsers,
            'remeasurers' => $remeasurers,
            'referrals' => $referrals,
            'externalBeneficiaries' => $externals,
            'enums' => [
                'beneficiarySourceTypes' => array_column(CommissionBeneficiarySourceEnum::cases(), 'value'),
                'beneficiaryRelations' => array_column(CommissionBeneficiaryRelationEnum::cases(), 'value'),
                'calculationTypes' => array_column(CommissionCalculationTypeEnum::cases(), 'value'),
                'paymentKinds' => array_column(CommissionPaymentKindEnum::cases(), 'value'),
                'paymentStatuses' => array_column(CommissionPaymentStatusEnum::cases(), 'value'),
                'splitTypes' => array_column(CommissionSplitTypeEnum::cases(), 'value'),
                'commissionStatuses' => array_column(CommissionStatusEnum::cases(), 'value'),
            ],
        ]);
    }

    public function storeCommission(StoreOrderCommissionRequest $request, CommissionCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();
        $order = Order::findOrFail($data['order_id']);

        return DB::transaction(function () use ($data, $order, $calculator) {
            [$beneficiarySourceType, $beneficiarySourceId, $name, $email] = $this->resolveBeneficiaryPayload($data, $order);

            $existing = OrderCommission::query()
                ->where('order_id', $order->id)
                ->where('beneficiary_source_type', $beneficiarySourceType)
                ->where('beneficiary_source_id', $beneficiarySourceId)
                ->exists();

            if ($existing) {
                return back()->with('error', 'This beneficiary already has a commission for the selected order.');
            }

            $commission = OrderCommission::create([
                'order_id' => $order->id,
                'beneficiary_source_type' => $beneficiarySourceType,
                'beneficiary_source_id' => $beneficiarySourceId,
                'beneficiary_relation' => $data['beneficiary_relation'],
                'beneficiary_name_snapshot' => $name,
                'beneficiary_email_snapshot' => $email,
                'status' => $data['status'] ?? CommissionStatusEnum::OPEN->value,
                'calculation_type' => $data['calculation_type'],
                'fee_amount_snapshot' => $data['fee_amount_snapshot'] ?? (float) ($order->cost_city_fee ?? 0),
                'financing_fee_amount' => $data['financing_fee_amount'] ?? 0,
                'percentage_value' => $data['percentage_value'] ?? null,
                'fixed_amount' => $data['fixed_amount'] ?? null,
                'other_cost_amount' => $data['other_cost_amount'] ?? 0,
                'other_cost_notes' => $data['other_cost_notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $payments = collect($data['payments'] ?? $this->defaultPaymentsForRelation($data['beneficiary_relation']));

            $payments->values()->each(function (array $payment, int $index) use ($commission) {
                $status = $payment['status'] ?? CommissionPaymentStatusEnum::OPEN->value;

                if (
                    $commission->status === CommissionStatusEnum::CANCELED->value
                    && $status !== CommissionPaymentStatusEnum::PAID->value
                ) {
                    $status = CommissionPaymentStatusEnum::CANCELED->value;
                }

                OrderCommissionPayment::create([
                    'order_commission_id' => $commission->id,
                    'sequence' => $index + 1,
                    'status' => $status,
                    'payment_kind' => CommissionPaymentKindEnum::REGULAR->value,
                    'split_type' => $payment['split_type'],
                    'split_value' => $payment['split_value'],
                    'other_cost_amount' => $payment['other_cost_amount'] ?? 0,
                    'other_cost_notes' => $payment['other_cost_notes'] ?? null,
                    'notes' => $payment['notes'] ?? null,
                    'paid_at' => $status === CommissionPaymentStatusEnum::PAID->value
                        ? ($payment['paid_at'] ?? now()->toDateString())
                        : null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            });

            $calculator->refreshCommission($commission);

            CommissionAuditLogger::log('commission.created', [
                'commission' => $commission->fresh()->toArray(),
            ], $commission);

            return back()->with('success', 'Commission created successfully.');
        });
    }

    public function updateCommission(UpdateOrderCommissionRequest $request, OrderCommission $commission, CommissionCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();

        if (
            $commission->status === CommissionStatusEnum::FULLY_PAID->value
            && round((float) ($data['other_cost_amount'] ?? 0), 2) !== round((float) $commission->other_cost_amount, 2)
        ) {
            return back()->with('error', 'Other Cost cannot be changed after the commission is fully paid. Use an extra adjustment payment instead.');
        }

        return DB::transaction(function () use ($data, $commission, $calculator) {
            [$beneficiarySourceType, $beneficiarySourceId, $name, $email] = $this->resolveBeneficiaryPayload($data, $commission->order);

            $duplicate = OrderCommission::query()
                ->where('order_id', $commission->order_id)
                ->where('beneficiary_source_type', $beneficiarySourceType)
                ->where('beneficiary_source_id', $beneficiarySourceId)
                ->whereKeyNot($commission->id)
                ->exists();

            if ($duplicate) {
                return back()->with('error', 'This beneficiary already has a commission for the selected order.');
            }

            $before = $commission->fresh()->toArray();
            $nextStatus = $data['status'] ?? $commission->status;

            $commission->update([
                'beneficiary_source_type' => $beneficiarySourceType,
                'beneficiary_source_id' => $beneficiarySourceId,
                'beneficiary_relation' => $data['beneficiary_relation'],
                'beneficiary_name_snapshot' => $name,
                'beneficiary_email_snapshot' => $email,
                'status' => $nextStatus,
                'calculation_type' => $data['calculation_type'],
                'fee_amount_snapshot' => $data['fee_amount_snapshot'] ?? $commission->fee_amount_snapshot,
                'financing_fee_amount' => $data['financing_fee_amount'] ?? $commission->financing_fee_amount,
                'percentage_value' => $data['percentage_value'] ?? null,
                'fixed_amount' => $data['fixed_amount'] ?? null,
                'other_cost_amount' => $data['other_cost_amount'] ?? 0,
                'other_cost_notes' => $data['other_cost_notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            if ($nextStatus === CommissionStatusEnum::CANCELED->value) {
                $commission->payments()
                    ->where('status', '!=', CommissionPaymentStatusEnum::PAID->value)
                    ->where('status', '!=', CommissionPaymentStatusEnum::CANCELED->value)
                    ->get()
                    ->each(function (OrderCommissionPayment $payment) {
                        $payment->update([
                            'status' => CommissionPaymentStatusEnum::CANCELED->value,
                            'paid_at' => null,
                            'updated_by' => Auth::id(),
                        ]);
                    });
            }

            $calculator->refreshCommission($commission);

            CommissionAuditLogger::log('commission.updated', [
                'before' => $before,
                'after' => $commission->fresh()->toArray(),
            ], $commission);

            return back()->with('success', 'Commission updated successfully.');
        });
    }

    public function destroyCommission(OrderCommission $commission): RedirectResponse
    {
        $commission->load('payments');

        if (! $this->canDeleteCommission($commission)) {
            return back()->with('error', 'Only commissions without paid payments or assigned periods can be deleted.');
        }

        return DB::transaction(function () use ($commission) {
            $before = $commission->fresh('payments')->toArray();

            $commission->payments->each(function (OrderCommissionPayment $payment) {
                $payment->delete();
            });

            $commission->delete();

            CommissionAuditLogger::log('commission.deleted', [
                'before' => $before,
                'deleted_commission_id' => $commission->id,
            ], $commission);

            return back()->with('success', 'Commission deleted successfully.');
        });
    }

    public function storePayment(StoreOrderCommissionPaymentRequest $request, OrderCommission $commission, CommissionCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();
        $paymentKind = $this->normalizePaymentKind($data['payment_kind'] ?? null);

        if (
            $paymentKind === CommissionPaymentKindEnum::EXTRA_ADJUSTMENT->value
            && $commission->status !== CommissionStatusEnum::FULLY_PAID->value
        ) {
            return back()->with('error', 'Extra adjustment payments can only be added after the commission is fully paid.');
        }

        if (
            $paymentKind === CommissionPaymentKindEnum::REGULAR->value
            && $commission->status === CommissionStatusEnum::FULLY_PAID->value
        ) {
            return back()->with('error', 'Fully paid commissions only allow extra adjustment payments.');
        }

        return DB::transaction(function () use ($data, $commission, $calculator, $paymentKind) {
            $sequence = ((int) $commission->payments()->max('sequence')) + 1;
            $status = $data['status'];

            $payment = OrderCommissionPayment::create([
                'order_commission_id' => $commission->id,
                'sequence' => $sequence,
                'status' => $status,
                'payment_kind' => $paymentKind,
                'split_type' => $paymentKind === CommissionPaymentKindEnum::EXTRA_ADJUSTMENT->value
                    ? CommissionSplitTypeEnum::FIXED->value
                    : $data['split_type'],
                'split_value' => $data['split_value'],
                'other_cost_amount' => $data['other_cost_amount'] ?? 0,
                'other_cost_notes' => $data['other_cost_notes'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_at' => $status === CommissionPaymentStatusEnum::PAID->value
                    ? ($data['paid_at'] ?? now()->toDateString())
                    : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $calculator->refreshCommission($commission);

            CommissionAuditLogger::log('payment.created', [
                'payment' => $payment->fresh()->toArray(),
            ], $commission, $payment);

            return back()->with('success', 'Payment created successfully.');
        });
    }

    public function updatePayment(UpdateOrderCommissionPaymentRequest $request, OrderCommissionPayment $payment, CommissionCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();
        $commission = $payment->commission;

        return DB::transaction(function () use ($data, $payment, $commission, $calculator) {
            $before = $payment->fresh()->toArray();
            $status = $data['status'];
            $paymentKind = $this->paymentKindValue($payment);

            $payment->update([
                'status' => $status,
                'split_type' => $paymentKind === CommissionPaymentKindEnum::EXTRA_ADJUSTMENT->value
                    ? CommissionSplitTypeEnum::FIXED->value
                    : $data['split_type'],
                'split_value' => $data['split_value'],
                'other_cost_amount' => $data['other_cost_amount'] ?? 0,
                'other_cost_notes' => $data['other_cost_notes'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_at' => $status === CommissionPaymentStatusEnum::PAID->value
                    ? ($data['paid_at'] ?? ($payment->paid_at ?: now()->toDateString()))
                    : null,
                'updated_by' => Auth::id(),
            ]);

            $calculator->refreshCommission($commission);

            CommissionAuditLogger::log('payment.updated', [
                'before' => $before,
                'after' => $payment->fresh()->toArray(),
            ], $commission, $payment);

            return back()->with('success', 'Payment updated successfully.');
        });
    }

    public function destroyPayment(OrderCommissionPayment $payment, CommissionCalculator $calculator): RedirectResponse
    {
        $commission = $payment->commission()->with('payments')->firstOrFail();

        if (! $this->canDeletePayment($payment, $commission)) {
            return back()->with('error', 'Only payments that are not paid or assigned to a period can be deleted, and the commission must keep at least one regular payment.');
        }

        return DB::transaction(function () use ($payment, $commission, $calculator) {
            $before = $payment->fresh()->toArray();

            $payment->delete();

            $commission->payments()
                ->orderBy('sequence')
                ->get()
                ->values()
                ->each(function (OrderCommissionPayment $remainingPayment, int $index) {
                    $remainingPayment->update([
                        'sequence' => $index + 1,
                        'updated_by' => Auth::id(),
                    ]);
                });

            $calculator->refreshCommission($commission->fresh());

            CommissionAuditLogger::log('payment.deleted', [
                'before' => $before,
                'deleted_payment_id' => $payment->id,
            ], $commission);

            return back()->with('success', 'Payment deleted successfully.');
        });
    }

    private function resolveDateRange(Request $request): array
    {
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        return [$startDate, $endDate];
    }

    private function buildIndexData(Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $selectedView = $request->input('view') === 'payments' ? 'payments' : 'commissions';
        $selectedStatus = $request->filled('status') ? $request->input('status') : null;
        $commissionStatus = $request->filled('commission_status') ? $request->input('commission_status') : null;
        $beneficiarySearch = trim((string) $request->input('beneficiary', ''));

        $availableStatuses = [
            OrderStatusEnum::ACCOUNT_RECEIPT->value,
            OrderStatusEnum::COMPLETE->value,
        ];

        $statusRows = OrderStatus::query()
            ->with([
                'order' => fn ($query) => $query
                    ->with([
                        'owners:id,name',
                        'orderCommissions.payments',
                        'orderCommissions.nextPayment',
                        'orderStatus' => fn ($query) => $query
                            ->whereIn('status', $availableStatuses)
                            ->orderByDesc('created_at'),
                    ])
                    ->select('id', 'order_number', 'invoice_number', 'name', 'status', 'project_amount', 'cost_city_fee', 'method_of_payment', 'type_of_financing'),
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when(
                $selectedStatus,
                fn ($query) => $query->where('status', $selectedStatus),
                fn ($query) => $query->whereHas('order.orderCommissions')
            )
            ->orderByDesc('created_at')
            ->get(['id', 'order_id', 'status', 'created_at'])
            ->unique('order_id')
            ->values();

        $rows = $statusRows
            ->flatMap(function (OrderStatus $statusRow) use ($selectedStatus) {
                $order = $statusRow->order;
                if (! $order) {
                    return [];
                }

                $owners = $order->owners->pluck('name')->filter()->implode(', ');
                $commissions = $order->orderCommissions;
                $accountingStatus = $this->resolveAccountingStatus($statusRow, $selectedStatus);

                if ($commissions->isEmpty()) {
                    return [[
                        'key' => 'order:' . $order->id,
                        'order_id' => $order->id,
                        'order_status' => $order->status,
                        'order_name' => $order->name,
                        'owner_names' => $owners,
                        'accounting_status' => $accountingStatus['status'],
                        'accounting_status_date' => $accountingStatus['date'],
                        'beneficiary_name' => null,
                        'beneficiary_relation' => null,
                        'commission_id' => null,
                        'commission_status' => null,
                        'commission_total' => 0,
                        'paid_amount' => 0,
                        'pending_amount' => 0,
                        'next_payment_amount' => 0,
                        'next_payment_status' => null,
                    ]];
                }

                return $commissions->map(function (OrderCommission $commission) use ($order, $owners, $accountingStatus) {
                    $nextPayment = $commission->nextPayment;
                    $commissionTotal = $this->resolveCommissionTotal($commission);

                    return [
                        'key' => 'commission:' . $commission->id,
                        'order_id' => $order->id,
                        'order_status' => $order->status,
                        'order_name' => $order->name,
                        'owner_names' => $owners,
                        'accounting_status' => $accountingStatus['status'],
                        'accounting_status_date' => $accountingStatus['date'],
                        'beneficiary_name' => $commission->beneficiary_name_snapshot,
                        'beneficiary_relation' => $commission->beneficiary_relation,
                        'commission_id' => $commission->id,
                        'commission_status' => $commission->status,
                        'commission_total' => $commissionTotal,
                        'paid_amount' => (float) $commission->paid_amount,
                        'pending_amount' => (float) $commission->pending_amount,
                        'next_payment_amount' => (float) ($nextPayment?->total_to_pay ?? 0),
                        'next_payment_status' => $nextPayment?->status,
                    ];
                });
            })
            ->filter(function (array $row) use ($commissionStatus, $beneficiarySearch) {
                if ($commissionStatus && $row['commission_status'] !== $commissionStatus) {
                    return false;
                }

                if (
                    ! $commissionStatus
                    && in_array($row['commission_status'], [
                        CommissionStatusEnum::FULLY_PAID->value,
                        CommissionStatusEnum::CANCELED->value,
                    ], true)
                ) {
                    return false;
                }

                if ($beneficiarySearch !== '') {
                    return str_contains(strtolower((string) ($row['beneficiary_name'] ?? '')), strtolower($beneficiarySearch));
                }

                return true;
            })
            ->values();

        $reviewPayments = $statusRows
            ->flatMap(function (OrderStatus $statusRow) use ($selectedStatus) {
                $order = $statusRow->order;
                if (! $order) {
                    return [];
                }

                $owners = $order->owners->pluck('name')->filter()->implode(', ');
                $accountingStatus = $this->resolveAccountingStatus($statusRow, $selectedStatus);

                return $order->orderCommissions->flatMap(function (OrderCommission $commission) use ($order, $owners, $accountingStatus) {
                    if ($commission->status === CommissionStatusEnum::CANCELED->value) {
                        return [];
                    }

                    $commissionTotal = $this->resolveCommissionTotal($commission);

                    return $commission->payments
                        ->where('status', CommissionPaymentStatusEnum::REVIEW->value)
                        ->where('commission_period_id', null)
                        ->map(function (OrderCommissionPayment $payment) use ($commission, $commissionTotal, $order, $owners, $accountingStatus) {
                    return [
                                'id' => $payment->id,
                                'order_id' => $order->id,
                                'order_status' => $order->status,
                                'order_name' => $order->name,
                                'order_number' => $order->order_number,
                                'invoice_number' => $order->invoice_number,
                                'owner_names' => $owners,
                                'accounting_status' => $accountingStatus['status'],
                                'accounting_status_date' => $accountingStatus['date'],
                                'project_payment_method' => $order->method_of_payment,
                                'type_of_financing' => $order->type_of_financing,
                                'project_amount' => $commission->project_amount_snapshot !== null
                                    ? (float) $commission->project_amount_snapshot
                                    : (float) ($order->project_amount ?? 0),
                                'commission_id' => $commission->id,
                                'commission_status' => $commission->status,
                                'commission_total' => $commissionTotal,
                                'commission_fee' => (float) $commission->fee_amount_snapshot,
                                'commission_financing_fee' => (float) $commission->financing_fee_amount,
                                'commission_base' => (float) $commission->base_amount_snapshot,
                                'commission_percentage' => $commission->percentage_value !== null ? (float) $commission->percentage_value : null,
                                'commission_paid' => (float) $commission->paid_amount,
                                'commission_pending' => (float) $commission->pending_amount,
                                'beneficiary_name' => $commission->beneficiary_name_snapshot,
                                'beneficiary_relation' => $commission->beneficiary_relation,
                                'sequence' => $payment->sequence,
                                'payment_kind' => $this->paymentKindValue($payment),
                                'payment_status' => $payment->status,
                                'payment_amount' => (float) $payment->total_to_pay,
                                'payment_base_amount' => (float) $payment->payment_base_amount,
                                'payment_other_cost_amount' => (float) $payment->other_cost_amount,
                                'paid_at' => $this->formatDateValue($payment->paid_at),
                                'commission_period_id' => $payment->commission_period_id,
                            ];
                        });
                });
            })
            ->filter(function (array $row) use ($beneficiarySearch) {
                if ($beneficiarySearch !== '') {
                    return str_contains(strtolower((string) ($row['beneficiary_name'] ?? '')), strtolower($beneficiarySearch));
                }

                return true;
            })
            ->values();

        $periods = CommissionPeriod::query()
            ->where('status', CommissionPeriodStatusEnum::OPEN->value)
            ->orderByDesc('start_date')
            ->get(['id', 'label', 'start_date', 'end_date'])
            ->map(fn (CommissionPeriod $period) => [
                'id' => $period->id,
                'label' => $period->label,
                'start_date' => optional($period->start_date)->toDateString(),
                'end_date' => optional($period->end_date)->toDateString(),
            ])
            ->values();

        return [
            'rows' => $rows,
            'reviewPayments' => $reviewPayments,
            'periods' => $periods,
            'totals' => [
                'orders' => $statusRows->count(),
                'commissions' => $rows->whereNotNull('commission_id')->count(),
                'total_commission' => round($rows->sum('commission_total'), 2),
                'total_paid' => round($rows->sum('paid_amount'), 2),
                'total_pending' => round($rows->sum('pending_amount'), 2),
            ],
            'selectedStatus' => $selectedStatus,
            'availableStatuses' => $availableStatuses,
            'selectedCommissionStatus' => $commissionStatus,
            'availableCommissionStatuses' => array_column(CommissionStatusEnum::cases(), 'value'),
            'selectedView' => $selectedView,
            'beneficiarySearch' => $beneficiarySearch,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ];
    }

    private function resolveAccountingStatus(OrderStatus $statusRow, ?string $selectedStatus): array
    {
        if ($selectedStatus) {
            return [
                'status' => $statusRow->status,
                'date' => $statusRow->created_at?->toDateTimeString(),
            ];
        }

        $accountingStatus = $statusRow->order?->orderStatus->first();

        return [
            'status' => $accountingStatus?->status ?? 'N/A',
            'date' => $accountingStatus?->created_at?->toDateTimeString(),
        ];
    }

    private function resolveCommissionTotal(OrderCommission $commission): float
    {
        $storedTotal = round((float) ($commission->total_amount ?? 0), 2);
        if ($storedTotal > 0) {
            return $storedTotal;
        }

        $calculatedTotal = round(
            (float) ($commission->commission_amount ?? 0) + (float) ($commission->other_cost_amount ?? 0),
            2
        );

        if ($calculatedTotal > 0) {
            return $calculatedTotal;
        }

        return round(
            $commission->payments
                ->where('status', '!=', CommissionPaymentStatusEnum::CANCELED->value)
                ->sum(fn (OrderCommissionPayment $payment) => (float) $payment->total_to_pay),
            2
        );
    }

    private function resolveBeneficiaryPayload(array $data, ?Order $order = null): array
    {
        $sourceType = $data['beneficiary_source_type'];
        $sourceId = $data['beneficiary_source_id'] ?? null;

        if (($data['beneficiary_relation'] ?? null) === CommissionBeneficiaryRelationEnum::OWNER->value) {
            if ($sourceType !== CommissionBeneficiarySourceEnum::USER->value) {
                throw ValidationException::withMessages([
                    'beneficiary_source_type' => 'Owner commissions must use a user already assigned as an owner on the order.',
                ]);
            }

            if (! $order) {
                throw ValidationException::withMessages([
                    'order_id' => 'Order is required to validate owner commissions.',
                ]);
            }

            $isOwnerAssignedToOrder = $order->owners()
                ->where('users.id', (int) $sourceId)
                ->exists();

            if (! $isOwnerAssignedToOrder) {
                throw ValidationException::withMessages([
                    'beneficiary_source_id' => 'The selected user is not an owner assigned to this order.',
                ]);
            }
        }

        if (($data['beneficiary_relation'] ?? null) === CommissionBeneficiaryRelationEnum::REMEASURER->value) {
            if ($sourceType !== CommissionBeneficiarySourceEnum::USER->value) {
                throw ValidationException::withMessages([
                    'beneficiary_source_type' => 'Remeasurer commissions must use a user with the remeasurer role.',
                ]);
            }

            $remeasurer = User::findOrFail((int) $sourceId);

            if (! $remeasurer->hasRole(RoleEnum::REMEASURER->value)) {
                throw ValidationException::withMessages([
                    'beneficiary_source_id' => 'The selected user does not have the remeasurer role.',
                ]);
            }
        }

        if ($sourceType === CommissionBeneficiarySourceEnum::USER->value) {
            $user = User::findOrFail((int) $sourceId);

            return [$sourceType, $user->id, $user->name, $user->email];
        }

        if ($sourceType === CommissionBeneficiarySourceEnum::REFERRAL->value) {
            $referral = Referral::findOrFail((int) $sourceId);

            return [$sourceType, $referral->id, $referral->name, $referral->email];
        }

        $external = null;
        if (! empty($data['external_beneficiary_id'])) {
            $external = ExternalCommissionBeneficiary::findOrFail((int) $data['external_beneficiary_id']);
        }

        if (! $external) {
            $external = ExternalCommissionBeneficiary::create([
                'name' => $data['external_name'],
                'email' => $data['external_email'] ?? null,
                'phone' => $data['external_phone'] ?? null,
                'company_name' => $data['external_company_name'] ?? null,
                'active' => true,
            ]);
        }

        return [$sourceType, $external->id, $external->name, $external->email];
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

    private function serializeCommission(OrderCommission $commission): array
    {
        $commission->loadMissing(['payments', 'nextPayment']);
        $extraPayments = $commission->payments->filter(
            fn (OrderCommissionPayment $payment) => $this->paymentKindValue($payment) === CommissionPaymentKindEnum::EXTRA_ADJUSTMENT->value
        );
        $activeExtraPayments = $extraPayments->where('status', '!=', CommissionPaymentStatusEnum::CANCELED->value);

        return [
            'id' => $commission->id,
            'beneficiary_source_type' => $commission->beneficiary_source_type,
            'beneficiary_source_id' => $commission->beneficiary_source_id,
            'beneficiary_relation' => $commission->beneficiary_relation,
            'beneficiary_name_snapshot' => $commission->beneficiary_name_snapshot,
            'beneficiary_email_snapshot' => $commission->beneficiary_email_snapshot,
            'status' => $commission->status,
            'calculation_type' => $commission->calculation_type,
            'percentage_value' => $commission->percentage_value !== null ? (float) $commission->percentage_value : null,
            'fixed_amount' => $commission->fixed_amount !== null ? (float) $commission->fixed_amount : null,
            'project_amount_snapshot' => (float) $commission->project_amount_snapshot,
            'fee_amount_snapshot' => (float) $commission->fee_amount_snapshot,
            'financing_fee_amount' => (float) $commission->financing_fee_amount,
            'base_amount_snapshot' => (float) $commission->base_amount_snapshot,
            'commission_amount' => (float) $commission->commission_amount,
            'other_cost_amount' => (float) $commission->other_cost_amount,
            'other_cost_notes' => $commission->other_cost_notes,
            'total_amount' => (float) $commission->total_amount,
            'paid_amount' => (float) $commission->paid_amount,
            'pending_amount' => (float) $commission->pending_amount,
            'next_payment_id' => $commission->next_payment_id,
            'can_delete' => $this->canDeleteCommission($commission),
            'change_order_amount_applied' => round(
                (float) $commission->project_amount_snapshot - (float) ($commission->order?->project_amount ?? 0),
                2
            ),
            'extra_payments_count' => $extraPayments->count(),
            'extra_payments_total' => round($activeExtraPayments->sum(fn (OrderCommissionPayment $payment) => (float) $payment->total_to_pay), 2),
            'extra_paid_amount' => round(
                $extraPayments->where('status', CommissionPaymentStatusEnum::PAID->value)
                    ->sum(fn (OrderCommissionPayment $payment) => (float) $payment->total_to_pay),
                2
            ),
            'extra_pending_amount' => round(
                $activeExtraPayments->where('status', '!=', CommissionPaymentStatusEnum::PAID->value)
                    ->sum(fn (OrderCommissionPayment $payment) => (float) $payment->total_to_pay),
                2
            ),
            'payments' => $commission->payments->map(fn (OrderCommissionPayment $payment) => [
                'id' => $payment->id,
                'sequence' => $payment->sequence,
                'status' => $payment->status,
                'payment_kind' => $this->paymentKindValue($payment),
                'split_type' => $payment->split_type,
                'split_value' => (float) $payment->split_value,
                'payment_base_amount' => (float) $payment->payment_base_amount,
                'other_cost_amount' => (float) $payment->other_cost_amount,
                'other_cost_notes' => $payment->other_cost_notes,
                'total_to_pay' => (float) $payment->total_to_pay,
                'commission_period_id' => $payment->commission_period_id,
                'paid_at' => $this->formatDateValue($payment->paid_at),
                'notes' => $payment->notes,
                'can_delete' => $this->canDeletePayment($payment, $commission),
            ])->values(),
        ];
    }

    private function canDeletePayment(OrderCommissionPayment $payment, OrderCommission $commission): bool
    {
        $commission->loadMissing('payments');

        if (
            $payment->status === CommissionPaymentStatusEnum::PAID->value
            || ! empty($payment->commission_period_id)
        ) {
            return false;
        }

        if ($this->paymentKindValue($payment) === CommissionPaymentKindEnum::EXTRA_ADJUSTMENT->value) {
            return true;
        }

        $remainingRegularPayments = $commission->payments
            ->reject(fn (OrderCommissionPayment $candidate) => (int) $candidate->id === (int) $payment->id)
            ->filter(fn (OrderCommissionPayment $candidate) => $this->paymentKindValue($candidate) === CommissionPaymentKindEnum::REGULAR->value);

        return $remainingRegularPayments->isNotEmpty();
    }

    private function canDeleteCommission(OrderCommission $commission): bool
    {
        $commission->loadMissing('payments');

        return ! $commission->payments->contains(fn (OrderCommissionPayment $payment) => $payment->status === CommissionPaymentStatusEnum::PAID->value)
            && ! $commission->payments->contains(fn (OrderCommissionPayment $payment) => ! empty($payment->commission_period_id));
    }

    private function normalizePaymentKind(?string $paymentKind): string
    {
        return $paymentKind === CommissionPaymentKindEnum::EXTRA_ADJUSTMENT->value
            ? CommissionPaymentKindEnum::EXTRA_ADJUSTMENT->value
            : CommissionPaymentKindEnum::REGULAR->value;
    }

    private function paymentKindValue(OrderCommissionPayment $payment): string
    {
        return $this->normalizePaymentKind($payment->payment_kind);
    }

    private function defaultPaymentsForRelation(string $relation): array
    {
        if ($relation === CommissionBeneficiaryRelationEnum::REMEASURER->value) {
            return [[
                'split_type' => CommissionSplitTypeEnum::PERCENTAGE->value,
                'split_value' => 100,
                'status' => CommissionPaymentStatusEnum::OPEN->value,
                'other_cost_amount' => 0,
                'other_cost_notes' => null,
                'notes' => null,
                'paid_at' => null,
            ]];
        }

        return [
            [
                'split_type' => CommissionSplitTypeEnum::PERCENTAGE->value,
                'split_value' => 50,
                'status' => CommissionPaymentStatusEnum::OPEN->value,
                'other_cost_amount' => 0,
                'other_cost_notes' => null,
                'notes' => null,
                'paid_at' => null,
            ],
            [
                'split_type' => CommissionSplitTypeEnum::PERCENTAGE->value,
                'split_value' => 50,
                'status' => CommissionPaymentStatusEnum::OPEN->value,
                'other_cost_amount' => 0,
                'other_cost_notes' => null,
                'notes' => null,
                'paid_at' => null,
            ],
        ];
    }

    private function resolveProjectAmountForNewCommission(Order $order): float
    {
        $order->loadMissing([
            'changeOrderPayment:id,order_id,amount',
            'orderCommissions.payments',
        ]);

        if ($this->orderHasAnyPaidRegularCommissionPayment($order)) {
            return round((float) ($order->project_amount ?? 0), 2);
        }

        return round(
            (float) ($order->project_amount ?? 0) + (float) ($order->changeOrderPayment?->amount ?? 0),
            2
        );
    }

    private function orderHasAnyPaidRegularCommissionPayment(Order $order): bool
    {
        return $order->orderCommissions
            ->flatMap(fn (OrderCommission $commission) => $commission->payments)
            ->contains(fn (OrderCommissionPayment $payment) => $this->paymentKindValue($payment) === CommissionPaymentKindEnum::REGULAR->value
                && $payment->status === CommissionPaymentStatusEnum::PAID->value);
    }
}
