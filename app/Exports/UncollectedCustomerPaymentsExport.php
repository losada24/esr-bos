<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesServiceExcelStyle;
use App\Models\Biweekly;
use App\Models\HistoryPendingPayment;
use App\Support\UncollectedCustomerPaymentsReportBuilder;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;

class UncollectedCustomerPaymentsExport implements FromView, WithColumnFormatting, WithEvents
{
    use AppliesServiceExcelStyle;

    public function __construct(private int $biweeklyId)
    {
    }

    public function view(): View
    {
        $biweeklys = HistoryPendingPayment::with('installationTeam')
            ->where('biweekly_id', $this->biweeklyId)
            ->where('type_history', 'INSTALLER')
            ->get();

        $report = UncollectedCustomerPaymentsReportBuilder::build($biweeklys);

        $biweekly = Biweekly::findOrFail($this->biweeklyId);
        $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');

        return view('excels.uncollected-payments-report', [
            'biweeklys' => $report['uncollected'],
            'biweeklys1' => $report['final_payment_pending'],
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
