<?php

namespace App\Exports;

use App\Models\Biweekly;
use App\Models\HistoryPendingPayment;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class UncollectedCustomerPaymentsExport implements FromView, WithColumnFormatting
{
    public function __construct(private int $biweeklyId)
    {
    }

    public function view(): View
    {
        $biweeklys = HistoryPendingPayment::with('installationTeam')
            ->where('biweekly_id', $this->biweeklyId)
            ->where('type_history', 'INSTALLER')
            ->get();

        $uncollected = collect();
        $uncollect1 = collect();

        foreach ($biweeklys as $uncollectBiweekly) {
            $items = collect(data_get($uncollectBiweekly, 'data', []));
            foreach ($items as $uncollectItem) {
                $payments = collect(data_get($uncollectItem, 'installation_payments', []));
                $lastPayment = $payments->last();

                if (!$lastPayment) {
                    continue;
                }

                $percent = (int) data_get($lastPayment, 'percentage_payment', 0);
                $partialPending = (int) data_get($uncollectItem, 'partial_payment_installation', 0) === 0;
                $finalPending = (int) data_get($uncollectItem, 'final_payment_installation', 0) === 0;

                if (($percent >= 1 && $percent <= 100 && $partialPending && $finalPending) || ($percent === 20 && $partialPending && $finalPending)) {
                    $uncollected->push($uncollectItem);
                }

                if ($percent === 20 && $finalPending && !$partialPending) {
                    $uncollect1->push($uncollectItem);
                }

                if ($percent === 100 && $finalPending && !$partialPending) {
                    $uncollect1->push($uncollectItem);
                }
            }
        }

        $biweekly = Biweekly::findOrFail($this->biweeklyId);
        $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');

        return view('excels.uncollected-payments-report', [
            'biweeklys' => $uncollected,
            'biweeklys1' => $uncollect1,
            'biweeklyTitle' => $biweeklyTitle,
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'F' => '"$"#,##0.00',
        ];
    }
}
