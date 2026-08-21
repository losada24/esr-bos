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
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UncollectedCustomerPaymentsExport implements FromView, WithColumnFormatting, WithEvents
{
    use AppliesServiceExcelStyle {
        registerEvents as registerServiceExcelStyleEvents;
    }

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
        $grandTotalPayment = $biweeklys
            ->flatMap(fn ($biweekly) => collect(data_get($biweekly, 'data', [])))
            ->sum(fn ($biweeklyData) => (float) data_get($biweeklyData, 'total_payment_amount', 0));

        $biweekly = Biweekly::findOrFail($this->biweeklyId);
        $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');

        return view('excels.uncollected-payments-report', [
            'biweeklys' => $report['uncollected'],
            'biweeklys1' => $report['final_payment_pending'],
            'biweeklyTitle' => $biweeklyTitle,
            'grandTotalPayment' => $grandTotalPayment,
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'F' => '"$"#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        $events = $this->registerServiceExcelStyleEvents();

        $events[AfterSheet::class] = function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();

            $this->applyServiceExcelStyle($sheet);
            $this->applyUncollectedPercentFormatting($sheet);
        };

        return $events;
    }

    private function applyUncollectedPercentFormatting(Worksheet $sheet): void
    {
        $percentRow = $this->findRowByLabel($sheet, '% of Grand Total Payment:');

        if ($percentRow === null) {
            return;
        }

        $sheet->getStyle("F{$percentRow}")->getNumberFormat()->setFormatCode('0.00%');

        $green = new Conditional();
        $green->setConditionType(Conditional::CONDITION_EXPRESSION);
        $green->setConditions(["\$F{$percentRow}<15%"]);
        $green->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFC6EFCE');

        $red = new Conditional();
        $red->setConditionType(Conditional::CONDITION_EXPRESSION);
        $red->setConditions(["\$F{$percentRow}>15%"]);
        $red->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFC7CE');

        $sheet->getStyle("A{$percentRow}:G{$percentRow}")->setConditionalStyles([$green, $red]);
    }

    private function findRowByLabel(Worksheet $sheet, string $label): ?int
    {
        $lastRow = $sheet->getHighestDataRow();

        for ($row = 1; $row <= $lastRow; $row++) {
            if (trim((string) $sheet->getCell("A{$row}")->getValue()) === $label) {
                return $row;
            }
        }

        return null;
    }
}
