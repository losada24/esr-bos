<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServiceControlExport implements FromView, WithStyles
{
    public function __construct(
        private readonly array $data
    ) {
    }

    public function view(): View
    {
        return view('excels.service-control', $this->data);
    }

    public function styles(Worksheet $sheet): array
    {
        $isBm = ($this->data['filters']['type'] ?? 'services') === 'bm';
        $lastColumn = $isBm ? 'I' : 'T';

        $sheet->setAutoFilter("A4:{$lastColumn}4");

        return [
            "A1:{$lastColumn}1" => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF0F0F0'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            "A4:{$lastColumn}4" => [
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
            "A5:{$lastColumn}1000" => [
                'font' => ['name' => 'Tahoma', 'size' => 8],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            'A:C' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ],
            $isBm ? 'D:I' : 'D:T' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
