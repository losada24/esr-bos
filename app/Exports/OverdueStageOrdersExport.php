<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesServiceExcelStyle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverdueStageOrdersExport implements FromView, WithEvents
{
    use AppliesServiceExcelStyle;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('excels.overdue-stage-orders', $this->data);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->applyServiceExcelStyle($sheet);
                $this->styleOverdueRows($sheet);
            },
        ];
    }

    private function styleOverdueRows(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestDataRow();
        $lastColumn = $sheet->getHighestDataColumn();
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        $headerRow = null;
        $overdueColumn = null;
        $amountColumn = null;

        for ($row = 1; $row <= $lastRow; $row++) {
            for ($column = 1; $column <= $lastColumnIndex; $column++) {
                $value = trim((string) $sheet->getCellByColumnAndRow($column, $row)->getValue());

                if (strcasecmp($value, 'Overdue') === 0) {
                    $headerRow = $row;
                    $overdueColumn = $column;
                }

                if (strcasecmp($value, 'Amount') === 0) {
                    $headerRow = $row;
                    $amountColumn = $column;
                }

                if ($overdueColumn !== null && $amountColumn !== null) {
                    break 2;
                }
            }
        }

        if ($headerRow === null || $overdueColumn === null) {
            return;
        }

        for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
            $firstColumnValue = trim((string) $sheet->getCell("A{$row}")->getValue());

            if (strcasecmp($firstColumnValue, 'Total') === 0) {
                if ($amountColumn !== null && $row > $headerRow + 1) {
                    $amountColumnLetter = Coordinate::stringFromColumnIndex($amountColumn);
                    $sheet->setCellValue(
                        "{$amountColumnLetter}{$row}",
                        "=SUBTOTAL(109,{$amountColumnLetter}" . ($headerRow + 1) . ":{$amountColumnLetter}" . ($row - 1) . ')'
                    );
                    $sheet->getStyle("{$amountColumnLetter}" . ($headerRow + 1) . ":{$amountColumnLetter}{$row}")
                        ->getNumberFormat()
                        ->setFormatCode('"$"#,##0.00');
                }

                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF0F0F0'],
                    ],
                ]);

                continue;
            }

            $overdueValue = trim((string) $sheet->getCellByColumnAndRow($overdueColumn, $row)->getCalculatedValue());

            if (strcasecmp($overdueValue, 'Yes') !== 0) {
                continue;
            }

            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFEE2E2'],
                ],
                'font' => [
                    'color' => ['argb' => 'FF7F1D1D'],
                ],
            ]);
        }
    }
}
