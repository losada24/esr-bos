<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Models\CommissionPeriod;
use App\Models\OrderCommission;
use App\Models\OrderCommissionAudit;
use App\Models\OrderCommissionPayment;
use App\Support\Commissions\CommissionHistoryPresenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommissionHistoryController extends Controller
{
    public function index(Request $request, CommissionHistoryPresenter $presenter): Response
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $filters = $this->resolveHistoryFilters($request, $startDate, $endDate);

        $eventsQuery = OrderCommissionAudit::query();
        $this->applyAuditFilters($eventsQuery, $filters);

        $commissionsQuery = OrderCommission::withTrashed()
            ->with([
                'order' => fn ($orderQuery) => $orderQuery->withTrashed(),
            ])
            ->whereHas('audits', function (Builder $auditQuery) use ($filters) {
                $this->applyAuditFilters($auditQuery, $filters);
            })
            ->withCount([
                'audits as matching_audits_count' => function (Builder $auditQuery) use ($filters) {
                    $this->applyAuditFilters($auditQuery, $filters);
                },
            ])
            ->addSelect([
                'latest_changed_at' => $this->latestAuditSubquery('changed_at', $filters),
            ])
            ->orderByDesc('latest_changed_at')
            ->orderByDesc('id');

        $commissions = $commissionsQuery
            ->paginate(25)
            ->withQueryString();

        $this->applyAuditFilters($latestAuditsQuery = OrderCommissionAudit::query()
            ->with($this->auditRelations())
            ->whereIn('order_commission_id', $commissions->getCollection()->pluck('id')->all())
            ->orderByDesc('changed_at')
            ->orderByDesc('id'), $filters);

        $latestAudits = $latestAuditsQuery
            ->get()
            ->groupBy('order_commission_id')
            ->map(fn ($group) => $group->first());

        $commissions->setCollection(
            $commissions->getCollection()->map(function (OrderCommission $commission) use ($latestAudits, $presenter) {
                $latestAudit = $latestAudits->get($commission->id);
                $latestSerialized = $latestAudit ? $presenter->serializeAudit($latestAudit) : null;
                $order = $commission->order;

                return [
                    'id' => $commission->id,
                    'order_id' => $order?->id,
                    'order_name' => $order?->name,
                    'order_number' => $order?->order_number,
                    'invoice_number' => $order?->invoice_number,
                    'commission_status' => $commission->status,
                    'beneficiary_name' => $commission->beneficiary_name_snapshot,
                    'beneficiary_relation' => $commission->beneficiary_relation,
                    'commission_total' => (float) ($commission->total_amount ?? 0),
                    'events_count' => (int) ($commission->matching_audits_count ?? 0),
                    'latest_changed_at' => $latestSerialized['changed_at'] ?? null,
                    'latest_action' => $latestSerialized['action_label'] ?? null,
                    'latest_summary' => $latestSerialized['summary'] ?? null,
                    'latest_user_name' => $latestSerialized['user_name'] ?? null,
                    'latest_period_label' => $latestSerialized['period_label'] ?? null,
                ];
            })
        );

        return Inertia::render('Commission/History', [
            'commissions' => $commissions,
            'periods' => $this->historyPeriods(),
            'filters' => $filters,
            'availableActions' => $this->availableActions(),
            'totals' => [
                'commissions' => $commissionsQuery->count(),
                'events' => $eventsQuery->count(),
            ],
        ]);
    }

    public function show(Request $request, int $commissionId, CommissionHistoryPresenter $presenter): Response
    {
        $commission = OrderCommission::withTrashed()
            ->with([
                'order' => fn ($orderQuery) => $orderQuery->withTrashed(),
                'payments' => fn ($paymentQuery) => $paymentQuery->withTrashed()->orderBy('sequence'),
            ])
            ->findOrFail($commissionId);

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $filters = $this->resolveHistoryFilters($request, $startDate, $endDate);

        $query = OrderCommissionAudit::query()
            ->with($this->auditRelations())
            ->where('order_commission_id', $commission->id)
            ->orderByDesc('changed_at')
            ->orderByDesc('id');

        $this->applyAuditFilters($query, $filters);

        $totalEvents = (clone $query)->count();

        $audits = $query
            ->paginate(25)
            ->withQueryString()
            ->through(fn (OrderCommissionAudit $audit) => $presenter->serializeAudit($audit));

        return Inertia::render('Commission/HistoryShow', [
            'commission' => [
                'id' => $commission->id,
                'status' => $commission->status,
                'beneficiary_name' => $commission->beneficiary_name_snapshot,
                'beneficiary_relation' => $commission->beneficiary_relation,
                'commission_total' => (float) ($commission->total_amount ?? 0),
                'paid_amount' => (float) ($commission->paid_amount ?? 0),
                'pending_amount' => (float) ($commission->pending_amount ?? 0),
                'order_id' => $commission->order?->id,
                'order_name' => $commission->order?->name,
                'order_number' => $commission->order?->order_number,
                'invoice_number' => $commission->order?->invoice_number,
                'order_status' => $commission->order?->status,
                'payments_count' => $commission->payments->count(),
            ],
            'audits' => $audits,
            'periods' => $this->historyPeriods(),
            'filters' => $filters,
            'availableActions' => $this->availableActions(),
            'totals' => [
                'events' => $totalEvents,
            ],
        ]);
    }

    public function paidHistory(Request $request, CommissionHistoryPresenter $presenter): Response
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $search = trim((string) $request->input('search', ''));
        $periodId = $request->filled('period_id') ? (int) $request->input('period_id') : null;

        $query = OrderCommissionPayment::query()
            ->with([
                'commission' => fn ($commissionQuery) => $commissionQuery->withTrashed()->with([
                    'order' => fn ($orderQuery) => $orderQuery->withTrashed(),
                ]),
                'period',
            ])
            ->where('status', 'PAID')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        if ($periodId !== null) {
            $query->where('commission_period_id', $periodId);
        }

        if ($search !== '') {
            $query->where(function ($paidQuery) use ($search) {
                $paidQuery
                    ->whereHas('commission', function ($commissionQuery) use ($search) {
                        $commissionQuery
                            ->where('beneficiary_name_snapshot', 'like', '%' . $search . '%')
                            ->orWhereHas('order', function ($orderQuery) use ($search) {
                                $orderQuery
                                    ->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('order_number', 'like', '%' . $search . '%')
                                    ->orWhere('invoice_number', 'like', '%' . $search . '%');
                            });
                    })
                    ->orWhereHas('period', function ($periodQuery) use ($search) {
                        $periodQuery->where('label', 'like', '%' . $search . '%');
                    });
            });
        }

        $totalsQuery = clone $query;

        $payments = $query
            ->paginate(25)
            ->withQueryString()
            ->through(fn (OrderCommissionPayment $payment) => $presenter->serializePaidPayment($payment));

        $totalPaid = round($totalsQuery->get()->sum(fn (OrderCommissionPayment $payment) => (float) $payment->total_to_pay), 2);

        return Inertia::render('Commission/PaidHistory', [
            'payments' => $payments,
            'periods' => $this->historyPeriods(),
            'filters' => [
                'search' => $search,
                'period_id' => $periodId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'totals' => [
                'payments' => $payments->total(),
                'total_paid' => $totalPaid,
            ],
        ]);
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

    private function resolveHistoryFilters(Request $request, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'search' => trim((string) $request->input('search', '')),
            'order' => trim((string) $request->input('order', '')),
            'period_id' => $request->filled('period_id') ? (int) $request->input('period_id') : null,
            'user' => trim((string) $request->input('user', '')),
            'action' => $request->filled('action') ? (string) $request->input('action') : '',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    private function applyAuditFilters(Builder $query, array $filters): void
    {
        $query->whereBetween('changed_at', [
            Carbon::parse($filters['start_date'])->startOfDay(),
            Carbon::parse($filters['end_date'])->endOfDay(),
        ]);

        if ($filters['action'] !== '') {
            $query->where('action', $filters['action']);
        }

        if ($filters['user'] !== '') {
            $query->whereHas('user', function ($userQuery) use ($filters) {
                $userQuery->where('name', 'like', '%' . $filters['user'] . '%');
            });
        }

        if ($filters['order'] !== '') {
            $query->whereHas('commission', function ($commissionQuery) use ($filters) {
                $commissionQuery->whereHas('order', function ($orderQuery) use ($filters) {
                    $orderQuery
                        ->where('name', 'like', '%' . $filters['order'] . '%')
                        ->orWhere('order_number', 'like', '%' . $filters['order'] . '%')
                        ->orWhere('invoice_number', 'like', '%' . $filters['order'] . '%')
                        ->orWhere('id', $filters['order']);
                });
            });
        }

        if ($filters['period_id'] !== null) {
            $query->where('commission_period_id', $filters['period_id']);
        }

        if ($filters['search'] !== '') {
            $query->where(function ($historyQuery) use ($filters) {
                $historyQuery
                    ->where('action', 'like', '%' . $filters['search'] . '%')
                    ->orWhereHas('commission', function ($commissionQuery) use ($filters) {
                        $commissionQuery
                            ->where('beneficiary_name_snapshot', 'like', '%' . $filters['search'] . '%')
                            ->orWhereHas('order', function ($orderQuery) use ($filters) {
                                $orderQuery
                                    ->where('name', 'like', '%' . $filters['search'] . '%')
                                    ->orWhere('order_number', 'like', '%' . $filters['search'] . '%')
                                    ->orWhere('invoice_number', 'like', '%' . $filters['search'] . '%');
                            });
                    })
                    ->orWhereHas('period', function ($periodQuery) use ($filters) {
                        $periodQuery->where('label', 'like', '%' . $filters['search'] . '%');
                    });
            });
        }
    }

    private function auditRelations(): array
    {
        return [
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
        ];
    }

    private function latestAuditSubquery(string $column, array $filters): Builder
    {
        $query = OrderCommissionAudit::query()
            ->select($column)
            ->whereColumn('order_commission_id', 'order_commissions.id');

        $this->applyAuditFilters($query, $filters);

        return $query
            ->orderByDesc('changed_at')
            ->orderByDesc('id')
            ->limit(1);
    }

    private function historyPeriods()
    {
        return CommissionPeriod::withTrashed()
            ->orderByDesc('start_date')
            ->get(['id', 'label', 'status'])
            ->map(fn (CommissionPeriod $period) => [
                'id' => $period->id,
                'label' => $period->label,
                'status' => $period->status,
            ])
            ->values();
    }

    private function availableActions(): array
    {
        return [
            'commission.created',
            'commission.updated',
            'commission.deleted',
            'payment.created',
            'payment.updated',
            'payment.deleted',
            'payment.bulk_paid',
            'payment.assigned_to_period',
            'payment.removed_from_period',
            'payment.closed_in_period',
        ];
    }
}
