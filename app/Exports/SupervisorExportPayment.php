<?php

namespace App\Exports;

use App\Models\Delivery;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Traits\Reports;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SupervisorExportPayment implements FromView, WithStyles, WithColumnFormatting, WithEvents
{
  use Reports;

    public $id, $status, $name, $start, $end;

    public function __construct($id, $status, $name, $start, $end)
    {
        $this->id = $id;
        $this->status = $status;
        $this->name = $name;
        $this->start = $start;
        $this->end = $end;
    }

    public function view(): View 
    {
        $orders = $this->getOrdersBySupervisor($this->id, ['status' => $this->status]);
       //dd($orders);
        
        return view('excels.supervisor-list', [
          'orders' => $orders,
          'supervisor' => User::find($this->id),
        ]);
    }

    public function styles(Worksheet $sheet)
    {  
      $sheet->setTitle( User::find($this->id)->name);
      // Obtener todas las filas
      $highestRow = $sheet->getHighestRow();
        $rows = $sheet->getRowIterator(5, $highestRow);
      //$rows = $sheet->getRowIterator(5);  // Comienza desde la fila 2 si los encabezados están en la fila 1

      foreach ($rows as $row) {
          // Obtener los valores enteros de Qty date (columna B) y Planning date (columna C)
          $cellQtyDate = $sheet->getCell('J' . $row->getRowIndex())->getValue();  // Suponiendo que Qty date está en la columna B
          $cellPlanningDate = $sheet->getCell('I' . $row->getRowIndex())->getValue();  // Suponiendo que Planning date está en la columna C
  
          // Comparar los valores enteros
          if ($cellQtyDate > $cellPlanningDate) {
              // Si Qty date es mayor que Planning date, cambia el color del texto de la celda Qty date (columna B) a rojo
              $sheet->getStyle('J' . $row->getRowIndex())->applyFromArray([
                  'font' => [
                      'color' => ['argb' => 'FFFF0000'],  // Rojo para el texto (en formato ARGB)
                  ],
              ]);
          }
      }
        return [
            // Estilos para toda la tabla
            'A4:N4' => [
                'font' => [
                    'bold' => true,
                    'size' => 14,
                ],
                
            ],
            'B' => [
                      'alignment' => [
                          'horizontal' => 'left', // Derecha
                          'vertical' => 'center',  // Centro vertical
                      ],
                  ],
                  
                  'C' => [
                      'alignment' => [
                          'horizontal' => 'center', // Derecha
                          'vertical' => 'center',  // Centro vertical
                      ],
                  ],
                  'F' => [
                    'alignment' => [
                        'horizontal' => 'center', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
                'G:H' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
                'I:J' => [
                  'alignment' => [
                      'horizontal' => 'center', // Derecha
                      'vertical' => 'center',  // Centro vertical
                  ],
              ],
              'K' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
              'L' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
                'M' => [
                  'alignment' => [
                      'horizontal' => 'center', // Derecha
                      'vertical' => 'center',  // Centro vertical
                  ],
              ],
              'N' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
            // Cambiar tamaño de fuente para toda la hoja
            /*'A5:O50' => [
              'font' => [
                  'name' => 'Tahoma', // Cambia a tu tipo de letra preferido
                  'size' => 8,
              ],
          ],*/
      
            // Agregar bordes
           /* 'A1:D100' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],*/
        ];
    }

    public function columnFormats(): array
    {
        return [
           'L' => '"$"#,##0.00', // Puedes cambiarlo según tu moneda
            'K' => '"$"###,##0.00', // Puedes cambiarlo según tu moneda
            
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
              $sheet = $event->sheet->getDelegate();

              // Aplica autofiltro si quieres
              $sheet->setAutoFilter('M4:N1000');
  
              // Detectar última fila REAL (ignorando celdas con solo formato)
              $startRow = 5;
              $endRow = 1000;
              $lastRow = $startRow;
              for ($row = $startRow; $row <= $endRow; $row++) {
                $value = trim((string) $sheet->getCell("A{$row}")->getValue());
                  if ($value !== '') {
                      $lastRow = $row;
                  }
              }
    
              // Estilo general (ahora que sabemos el lastRow real)
              $sheet->getStyle("A5:N{$lastRow}")->applyFromArray([
                  'font' => [
                      'name' => 'Tahoma',
                      'size' => 8,
                  ],
              ]);
  
              // Fila de totales justo debajo de los datos
              $totalRow = $lastRow + 1;
              //dd($totalRow);
  
              $sheet->setCellValue("I{$totalRow}", 'Se actualiza con el filtro');
              $sheet->getStyle("I{$totalRow}")->getFont()->setItalic(true);
              $sheet->setCellValue("J{$totalRow}", 'TOTAL:');
              $sheet->getStyle("J{$totalRow}")->getFont()->setBold(true);
  
              $sheet->setCellValue("K{$totalRow}", "=SUBTOTAL(9,K{$startRow}:K{$lastRow})");
              $sheet->setCellValue("L{$totalRow}", "=SUBTOTAL(9,L{$startRow}:L{$lastRow})");
              $sheet->getStyle("K{$totalRow}")
                  ->getNumberFormat()
                  ->setFormatCode('"$"#,##0.00');

              $sheet->getStyle("L{$totalRow}")
                  ->getNumberFormat()
                  ->setFormatCode('"$"#,##0.00');
  
              $sheet->getStyle("K{$totalRow}:L{$totalRow}")->getFont()->setBold(true);
  
              // Fondo gris para la fila de totales
              $sheet->getStyle("I{$totalRow}:L{$totalRow}")
                  ->getFill()->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('D9D9D9');
            },
        ];
    }
}
