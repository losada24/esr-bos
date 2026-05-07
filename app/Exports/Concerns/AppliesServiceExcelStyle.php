<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait AppliesServiceExcelStyle
{
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $this->applyServiceExcelStyle($event->sheet->getDelegate());
            },
        ];
    }

    private function applyServiceExcelStyle(Worksheet $sheet): void
    {
        $lastColumn = $sheet->getHighestDataColumn();
        $lastRow = $sheet->getHighestDataRow();
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);

        if ($lastColumn === 'A' && $lastRow === 1 && $sheet->getCell('A1')->getValue() === null) {
            return;
        }

        $headerRow = $this->detectServiceStyleHeaderRow($sheet, $lastColumnIndex, $lastRow);

        for ($column = 1; $column <= $lastColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }

        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
            'font' => ['name' => 'Tahoma', 'size' => 8],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF0F0F0'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        if ($headerRow !== null) {
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$headerRow}");
            $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
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
            ]);

            $totalsRow = $this->appendCostTotalsRow($sheet, $headerRow, $lastColumnIndex, $lastRow);

            if ($totalsRow !== null) {
                $lastRow = $totalsRow;
                $sheet->getStyle("A{$totalsRow}:{$lastColumn}{$totalsRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFF0F0F0'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
            }
        }

        $leftAlignedLastColumn = Coordinate::stringFromColumnIndex(min(3, $lastColumnIndex));
        $sheet->getStyle("A:{$leftAlignedLastColumn}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        if ($lastColumnIndex > 3) {
            $centerAlignedFirstColumn = Coordinate::stringFromColumnIndex(4);
            $sheet->getStyle("{$centerAlignedFirstColumn}:{$lastColumn}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }

    private function appendCostTotalsRow(Worksheet $sheet, int $headerRow, int $lastColumnIndex, int $lastRow): ?int
    {
        if ($headerRow >= $lastRow) {
            return null;
        }

        if ($this->hasExistingTotalRow($sheet, $headerRow, $lastColumnIndex, $lastRow)) {
            return null;
        }

        $totals = [];

        for ($column = 1; $column <= $lastColumnIndex; $column++) {
            $header = (string) $sheet->getCellByColumnAndRow($column, $headerRow)->getValue();

            if (! $this->isCostHeader($header)) {
                continue;
            }

            $total = 0.0;
            $values = 0;

            for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
                if ($this->isExistingTotalRow($sheet, $row, $lastColumnIndex)) {
                    continue;
                }

                $amount = $this->parseCostValue($sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue());

                if ($amount === null) {
                    continue;
                }

                $total += $amount;
                $values++;
            }

            if ($values > 0) {
                $totals[$column] = $total;
            }
        }

        if ($totals === []) {
            return null;
        }

        $totalsRow = $lastRow + 1;
        $sheet->setCellValueByColumnAndRow(1, $totalsRow, 'Total');

        foreach ($totals as $column => $total) {
            $sheet->setCellValueByColumnAndRow($column, $totalsRow, $total);
            $sheet->getStyleByColumnAndRow($column, $totalsRow)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        }

        return $totalsRow;
    }

    private function isCostHeader(string $header): bool
    {
        $header = strtolower(trim($header));

        if ($header === '') {
            return false;
        }

        if (preg_match('/percent|percentage|%|status|date|day|days|qty|quantity|count|orders|invoice|number|#/', $header) === 1) {
            return false;
        }

        return preg_match('/cost|amount|payment|paid|pending|commission|fee|base|total|value/', $header) === 1;
    }

    private function parseCostValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || preg_match('/[A-Za-z]/', $value) === 1) {
            return null;
        }

        $normalized = str_replace([',', '$', ' '], '', $value);

        if (preg_match('/^\(?-?\d+(\.\d+)?\)?$/', $normalized) !== 1) {
            return null;
        }

        $isNegative = str_starts_with($normalized, '(') && str_ends_with($normalized, ')');
        $normalized = trim($normalized, '()');

        return (float) $normalized * ($isNegative ? -1 : 1);
    }

    private function isExistingTotalRow(Worksheet $sheet, int $row, int $lastColumnIndex): bool
    {
        $columnsToCheck = min(3, $lastColumnIndex);

        for ($column = 1; $column <= $columnsToCheck; $column++) {
            $value = strtolower(trim((string) $sheet->getCellByColumnAndRow($column, $row)->getValue()));

            if ($value === 'total' || str_starts_with($value, 'total ')) {
                return true;
            }
        }

        return false;
    }

    private function hasExistingTotalRow(Worksheet $sheet, int $headerRow, int $lastColumnIndex, int $lastRow): bool
    {
        for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
            if ($this->isExistingTotalRow($sheet, $row, $lastColumnIndex)) {
                return true;
            }
        }

        return false;
    }

    private function detectServiceStyleHeaderRow(Worksheet $sheet, int $lastColumnIndex, int $lastRow): ?int
    {
        $headerRow = null;
        $highestFilledCells = 1;

        for ($row = 2; $row <= $lastRow; $row++) {
            $filledCells = 0;

            for ($column = 1; $column <= $lastColumnIndex; $column++) {
                $value = $sheet->getCellByColumnAndRow($column, $row)->getValue();

                if ($value !== null && $value !== '') {
                    $filledCells++;
                }
            }

            if ($filledCells > $highestFilledCells) {
                $highestFilledCells = $filledCells;
                $headerRow = $row;
            }
        }

        return $headerRow;
    }
}
