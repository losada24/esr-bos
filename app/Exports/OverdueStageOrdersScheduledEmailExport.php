<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverdueStageOrdersScheduledEmailExport implements FromView, WithEvents
{
    public function __construct(public array $data)
    {
    }

    public function view(): View
    {
        return view('excels.overdue-stage-orders-scheduled-email', $this->data);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->styleOverdueRows($sheet);
            },
        ];
    }

    private function styleOverdueRows(Worksheet $sheet): void
    {
        $lastRow = $sheet->getHighestDataRow();
        $lastColumn = $sheet->getHighestDataColumn();
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        $amountColumn = null;
        $headerRow = null;

        for ($row = 1; $row <= $lastRow; $row++) {
            for ($column = 1; $column <= $lastColumnIndex; $column++) {
                $value = trim((string) $sheet->getCellByColumnAndRow($column, $row)->getValue());

                if (strcasecmp($value, 'Amount') === 0) {
                    $amountColumn = $column;
                }

                if (strcasecmp($value, 'Row Type') === 0) {
                    $headerRow = $row;
                }

                if ($amountColumn !== null && $headerRow !== null) {
                    break 2;
                }
            }
        }

        $sheet->freezePane('A6');
        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getFont()->setName('Arial')->setSize(12);
        $sheet->getStyle('A1:I1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A2:I4')->getFont()->setSize(12)->setBold(true);

        if ($headerRow !== null) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
            $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF374151'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);
        }

        for ($row = 1; $row <= $lastRow; $row++) {
            $rowType = trim((string) $sheet->getCell("A{$row}")->getValue());

            if (strcasecmp($rowType, 'Order') === 0) {
                $sheet->getRowDimension($row)->setRowHeight(30);
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFEE2E2'],
                    ],
                    'font' => [
                        'size' => 12,
                        'color' => ['argb' => 'FF111827'],
                    ],
                ]);
            }

            if (strcasecmp($rowType, 'Status Total') === 0) {
                $sheet->getRowDimension($row)->setRowHeight(28);
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF111827']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF7ED'],
                    ],
                ]);
            }

            if (strcasecmp($rowType, 'Seller Total') === 0) {
                $sheet->getRowDimension($row)->setRowHeight(26);
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFEFF6FF'],
                    ],
                ]);
            }

            if (strcasecmp($rowType, 'Total') === 0) {
                $sheet->getRowDimension($row)->setRowHeight(30);
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF2563EB'],
                    ],
                ]);
            }
        }

        if ($amountColumn !== null) {
            $amountColumnLetter = Coordinate::stringFromColumnIndex($amountColumn);
            $sheet->getStyle("{$amountColumnLetter}:{$amountColumnLetter}")
                ->getNumberFormat()
                ->setFormatCode('"$"#,##0.00');
        }

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(42);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(22);
    }
}
