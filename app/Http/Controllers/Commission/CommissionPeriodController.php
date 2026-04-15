<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\StoreCommissionPeriodRequest;
use App\Models\CommissionPeriod;
use App\Support\Commissions\CloseCommissionPeriod;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CommissionPeriodController extends Controller
{
    public function index(Request $request): Response
    {
        $periods = CommissionPeriod::query()
            ->with('snapshot')
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('CommissionPeriod/Index', [
            'periods' => $periods->through(function (CommissionPeriod $period) {
                $summary = $period->snapshot?->data['summary'] ?? null;

                return [
                    'id' => $period->id,
                    'label' => $period->label,
                    'status' => $period->status,
                    'start_date' => optional($period->start_date)->toDateString(),
                    'end_date' => optional($period->end_date)->toDateString(),
                    'closed_at' => optional($period->closed_at)->toDateTimeString(),
                    'snapshot_summary' => $summary,
                ];
            }),
        ]);
    }

    public function store(StoreCommissionPeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        CommissionPeriod::create([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'label' => $data['label'] ?: $startDate->format('M d') . ' to ' . $endDate->format('M d'),
            'status' => 'OPEN',
        ]);

        return back()->with('success', 'Commission period created successfully.');
    }

    public function show(CommissionPeriod $commissionPeriod): Response
    {
        $commissionPeriod->load('snapshot');

        return Inertia::render('CommissionPeriod/Show', [
            'period' => [
                'id' => $commissionPeriod->id,
                'label' => $commissionPeriod->label,
                'status' => $commissionPeriod->status,
                'start_date' => optional($commissionPeriod->start_date)->toDateString(),
                'end_date' => optional($commissionPeriod->end_date)->toDateString(),
                'closed_at' => optional($commissionPeriod->closed_at)->toDateTimeString(),
                'snapshot' => $commissionPeriod->snapshot?->data,
            ],
        ]);
    }

    public function close(CommissionPeriod $commissionPeriod, CloseCommissionPeriod $closeCommissionPeriod): RedirectResponse
    {
        $closeCommissionPeriod->handle($commissionPeriod);

        return redirect()
            ->route('commission-periods.show', $commissionPeriod)
            ->with('success', 'Commission period closed successfully.');
    }
}
