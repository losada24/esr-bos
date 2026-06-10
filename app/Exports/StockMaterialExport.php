<?php

namespace App\Exports;

use App\Exports\Concerns\AppliesServiceExcelStyle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMaterialExport implements FromView, WithStyles, WithEvents
{
    use AppliesServiceExcelStyle;

    public function __construct(
        private readonly array $data
    ) {
    }

    public function view(): View
    {
        return view('excels.stock-material', $this->data);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->setAutoFilter('A4:G4');

        for ($column = 1; $column <= Coordinate::columnIndexFromString('G'); $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        return [
            'A1:G1' => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF0F0F0'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            'A4:G4' => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE0E0E0'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            'A5:G1000' => [
                'font' => ['name' => 'Tahoma', 'size' => 8],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            'A:B' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            'C:G' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
