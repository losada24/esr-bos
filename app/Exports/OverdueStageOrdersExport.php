<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverdueStageOrdersExport implements FromView, WithEvents
{
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
                $this->styleSheet($event->sheet->getDelegate());
            },
        ];
    }

    private function styleSheet(Worksheet $sheet): void
    {
        $lastColumn = $sheet->getHighestDataColumn();
        $lastRow = $sheet->getHighestDataRow();
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        $headerRow = 6;

        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('K3:N3');
        $sheet->mergeCells('I4:N4');
        $sheet->freezePane('A7');
        $sheet->setAutoFilter("A{$headerRow}:N{$headerRow}");

        for ($column = 1; $column <= $lastColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $sheet->getStyle("A1:N1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'name' => 'Tahoma',
                'size' => 16,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2F63DF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A2:N2')->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Tahoma', 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFB8C2CF'],
            ],
        ]);

        $sheet->getStyle('A3:N4')->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Tahoma', 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD0D0D0'],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:N{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFE5E7EB'],
                'name' => 'Tahoma',
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF2F3745'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A1:N{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFE5E7EB'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A7:N{$lastRow}")->applyFromArray([
            'font' => ['name' => 'Tahoma', 'size' => 10],
        ]);

        $sheet->getStyle("E7:E{$lastRow}")->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('B4')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('D4')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('F4')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle('H4')->getNumberFormat()->setFormatCode('"$"#,##0.00');
        $sheet->getStyle("F7:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($row = 7; $row <= $lastRow; $row++) {
            $rowType = trim((string) $sheet->getCell("A{$row}")->getValue());
            $range = "A{$row}:N{$row}";

            if ($rowType === 'Status Total') {
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFC8C3BA'],
                    ],
                ]);
            } elseif ($rowType === 'Seller Total') {
                $sheet->getStyle($range)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFBFC5CC'],
                    ],
                ]);
            } elseif ($rowType === 'Order') {
                $deadlineStatus = trim((string) $sheet->getCell("J{$row}")->getValue());
                $fillColor = match ($deadlineStatus) {
                    'Overdue Extended' => 'FFFEF3C7',
                    'Overdue' => 'FFFEE2E2',
                    default => 'FFECFDF5',
                };

                $sheet->getStyle($range)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => $fillColor],
                    ],
                ]);
            }
        }
    }
}
