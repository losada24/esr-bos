<?php

namespace App\Http\Controllers\Commission;

use App\Exports\CommissionReportExport;
use App\Enum\CommissionBeneficiaryRelationEnum;
use App\Enum\CommissionBeneficiarySourceEnum;
use App\Enum\CommissionCalculationTypeEnum;
use App\Enum\CommissionPaymentStatusEnum;
use App\Enum\CommissionSplitTypeEnum;
use App\Enum\CommissionStatusEnum;
use App\Enum\OrderStatusEnum;
use App\Enum\StatusUserEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\StoreOrderCommissionPaymentRequest;
use App\Http\Requests\Commission\StoreOrderCommissionRequest;
use App\Http\Requests\Commission\UpdateOrderCommissionPaymentRequest;
use App\Http\Requests\Commission\UpdateOrderCommissionRequest;
use App\Models\ExternalCommissionBeneficiary;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderCommissionPayment;
use App\Models\OrderStatus;
use App\Models\Referral;
use App\Models\User;
use App\Support\Commissions\CommissionAuditLogger;
use App\Support\Commissions\CommissionCalculator;
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
        $pdf = Pdf::loadView('pdf.commissions', $data)->setPaper('A4', 'landscape');

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

    public function editOrder(Order $order): Response
    {
        $order->load([
            'owners:id,name,email',
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

        return Inertia::render('Commission/EditOrder', [
            'order' => [
                'id' => $order->id,
                'name' => $order->name,
                'status' => $order->status,
                'project_amount' => (float) ($order->project_amount ?? 0),
                'cost_city_fee' => (float) ($order->cost_city_fee ?? 0),
                'owners' => $order->owners->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
            'commissions' => $order->orderCommissions->map(fn (OrderCommission $commission) => $this->serializeCommission($commission))->values(),
            'activeUsers' => $activeUsers,
            'referrals' => $referrals,
            'externalBeneficiaries' => $externals,
            'enums' => [
                'beneficiarySourceTypes' => array_column(CommissionBeneficiarySourceEnum::cases(), 'value'),
                'beneficiaryRelations' => array_column(CommissionBeneficiaryRelationEnum::cases(), 'value'),
                'calculationTypes' => array_column(CommissionCalculationTypeEnum::cases(), 'value'),
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
                'percentage_value' => $data['percentage_value'] ?? null,
                'fixed_amount' => $data['fixed_amount'] ?? null,
                'other_cost_amount' => $data['other_cost_amount'] ?? 0,
                'other_cost_notes' => $data['other_cost_notes'] ?? null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $payments = collect($data['payments'] ?? [
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
            ]);

            $payments->values()->each(function (array $payment, int $index) use ($commission) {
                $status = $payment['status'] ?? CommissionPaymentStatusEnum::OPEN->value;

                OrderCommissionPayment::create([
                    'order_commission_id' => $commission->id,
                    'sequence' => $index + 1,
                    'status' => $status,
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

            $commission->update([
                'beneficiary_source_type' => $beneficiarySourceType,
                'beneficiary_source_id' => $beneficiarySourceId,
                'beneficiary_relation' => $data['beneficiary_relation'],
                'beneficiary_name_snapshot' => $name,
                'beneficiary_email_snapshot' => $email,
                'status' => $data['status'] ?? $commission->status,
                'calculation_type' => $data['calculation_type'],
                'fee_amount_snapshot' => $data['fee_amount_snapshot'] ?? $commission->fee_amount_snapshot,
                'percentage_value' => $data['percentage_value'] ?? null,
                'fixed_amount' => $data['fixed_amount'] ?? null,
                'other_cost_amount' => $data['other_cost_amount'] ?? 0,
                'other_cost_notes' => $data['other_cost_notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $calculator->refreshCommission($commission);

            CommissionAuditLogger::log('commission.updated', [
                'before' => $before,
                'after' => $commission->fresh()->toArray(),
            ], $commission);

            return back()->with('success', 'Commission updated successfully.');
        });
    }

    public function storePayment(StoreOrderCommissionPaymentRequest $request, OrderCommission $commission, CommissionCalculator $calculator): RedirectResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $commission, $calculator) {
            $sequence = ((int) $commission->payments()->max('sequence')) + 1;
            $status = $data['status'];

            $payment = OrderCommissionPayment::create([
                'order_commission_id' => $commission->id,
                'sequence' => $sequence,
                'status' => $status,
                'split_type' => $data['split_type'],
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

            $payment->update([
                'status' => $status,
                'split_type' => $data['split_type'],
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
                    ->with(['owners:id,name', 'orderCommissions.payments', 'orderCommissions.nextPayment'])
                    ->select('id', 'name', 'status', 'project_amount', 'cost_city_fee'),
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', $selectedStatus ? [$selectedStatus] : $availableStatuses)
            ->orderByDesc('created_at')
            ->get(['id', 'order_id', 'status', 'created_at'])
            ->unique('order_id')
            ->values();

        $rows = $statusRows
            ->flatMap(function (OrderStatus $statusRow) {
                $order = $statusRow->order;
                if (! $order) {
                    return [];
                }

                $owners = $order->owners->pluck('name')->filter()->implode(', ');
                $commissions = $order->orderCommissions;

                if ($commissions->isEmpty()) {
                    return [[
                        'key' => 'order:' . $order->id,
                        'order_id' => $order->id,
                        'order_status' => $order->status,
                        'order_name' => $order->name,
                        'owner_names' => $owners,
                        'accounting_status' => $statusRow->status,
                        'accounting_status_date' => $statusRow->created_at?->toDateTimeString(),
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

                return $commissions->map(function (OrderCommission $commission) use ($order, $owners, $statusRow) {
                    $nextPayment = $commission->nextPayment;

                    return [
                        'key' => 'commission:' . $commission->id,
                        'order_id' => $order->id,
                        'order_status' => $order->status,
                        'order_name' => $order->name,
                        'owner_names' => $owners,
                        'accounting_status' => $statusRow->status,
                        'accounting_status_date' => $statusRow->created_at?->toDateTimeString(),
                        'beneficiary_name' => $commission->beneficiary_name_snapshot,
                        'beneficiary_relation' => $commission->beneficiary_relation,
                        'commission_id' => $commission->id,
                        'commission_status' => $commission->status,
                        'commission_total' => (float) $commission->total_amount,
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

                if ($beneficiarySearch !== '') {
                    return str_contains(strtolower((string) ($row['beneficiary_name'] ?? '')), strtolower($beneficiarySearch));
                }

                return true;
            })
            ->values();

        return [
            'rows' => $rows,
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
            'beneficiarySearch' => $beneficiarySearch,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ];
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

    private function serializeCommission(OrderCommission $commission): array
    {
        $commission->loadMissing(['payments', 'nextPayment']);

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
            'base_amount_snapshot' => (float) $commission->base_amount_snapshot,
            'commission_amount' => (float) $commission->commission_amount,
            'other_cost_amount' => (float) $commission->other_cost_amount,
            'other_cost_notes' => $commission->other_cost_notes,
            'total_amount' => (float) $commission->total_amount,
            'paid_amount' => (float) $commission->paid_amount,
            'pending_amount' => (float) $commission->pending_amount,
            'next_payment_id' => $commission->next_payment_id,
            'payments' => $commission->payments->map(fn (OrderCommissionPayment $payment) => [
                'id' => $payment->id,
                'sequence' => $payment->sequence,
                'status' => $payment->status,
                'split_type' => $payment->split_type,
                'split_value' => (float) $payment->split_value,
                'payment_base_amount' => (float) $payment->payment_base_amount,
                'other_cost_amount' => (float) $payment->other_cost_amount,
                'other_cost_notes' => $payment->other_cost_notes,
                'total_to_pay' => (float) $payment->total_to_pay,
                'commission_period_id' => $payment->commission_period_id,
                'paid_at' => optional($payment->paid_at)->toDateString(),
                'notes' => $payment->notes,
            ])->values(),
        ];
    }
}
