<?php

namespace App\Exports;

use App\Models\Delivery;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Traits\Reports;

class SupervisorExportPayment implements FromView, WithStyles
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
      $rows = $sheet->getRowIterator(5);  // Comienza desde la fila 2 si los encabezados están en la fila 1

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
            'A4:O4' => [
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
                      'horizontal' => 'center', // Derecha
                      'vertical' => 'center',  // Centro vertical
                  ],
              ],
              'M' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
                'N' => [
                  'alignment' => [
                      'horizontal' => 'center', // Derecha
                      'vertical' => 'center',  // Centro vertical
                  ],
              ],
              'O' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
            // Cambiar tamaño de fuente para toda la hoja
            'A5:O100' => [
              'font' => [
                  'name' => 'Tahoma', // Cambia a tu tipo de letra preferido
                  'size' => 8,
              ],
          ],
      
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
}
