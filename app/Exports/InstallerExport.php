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

class InstallerExport implements FromView, WithStyles
{
  use Reports;

    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function view(): View 
    {
       // $payments = $this->getOrdersByInstallerBiweekly($this->id);
       $orders = $this->getOrdersByInstaller($this->id, $status=null , $startDate=null, $endDate=null, $orderStatu=null);
        //$installerName = $payments->first()['installation_team'] ?? '';
        $installerName = $orders->first()['installer'] ?? '';
        //$companyName = $payments->first()['company_name'] ?? '';
        $companyName = $orders->first()['company_name'] ?? '';
       //$biweekly = $payments->first()['biweekly'] ?? '';
       
        
        //dd($orders->first()['installer']);
        
        return view('excels.installer-list', [
          //'payments' => $payments,
          'installer' =>  $installerName,
          'company' => $companyName,
          //'biweekly' => $biweekly,
          'orders' => $orders,
          //'installer' =>  $installerName,
          //'company' => $companyName,
        ]);
    }

    public function styles(Worksheet $sheet)
    {  
      //$sheet->setTitle( );

      $rows = $sheet->getRowIterator(5);  // Comienza desde la fila 2 si los encabezados están en la fila 1

      foreach ($rows as $row) {
          // Obtener los valores enteros de Qty date (columna B) y Planning date (columna C)
          $cellQtyDate = $sheet->getCell('I' . $row->getRowIndex())->getValue(); 
          //dd( $cellQtyDate); // Suponiendo que Qty date está en la columna B
          $cellPlanningDate = $sheet->getCell('I' . $row->getRowIndex())->getValue();  // Suponiendo que Planning date está en la columna C
  
          // Comparar los valores enteros
          if ($cellQtyDate == '0.00%') {
              // Si Qty date es mayor que Planning date, cambia el color del texto de la celda Qty date (columna B) a rojo
              $sheet->getStyle('D' . $row->getRowIndex())->applyFromArray([
                'fill' => [
                  'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                  'startColor' => ['argb' => 'FFFFFF00'], // Amarillo para el fondo (en formato ARGB)
              ],
              ]);
          }
      }
     
        return [
            // Estilos para toda la tabla
            'A4:T4' => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                
            ],
            'D:E' => [
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
                        'horizontal' => 'left', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
                'H:L' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
               /* 'I:J' => [
                  'alignment' => [
                      'horizontal' => 'center', // Derecha
                      'vertical' => 'center',  // Centro vertical
                  ],
              ],*/
              /*'K' => [
                    'alignment' => [
                        'horizontal' => 'right', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],*/
                'T' => [
                  'alignment' => [
                      'horizontal' => 'center', // Derecha
                      'vertical' => 'center',  // Centro vertical
                  ],
              ],
              'M' => [
                    'alignment' => [
                        'horizontal' => 'left', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
                'N:P' => [
                  'alignment' => [
                      'horizontal' => 'right', // Derecha
                      'vertical' => 'center',  // Centro vertical
                  ],
              ],
              'Q:R' => [
                    'alignment' => [
                        'horizontal' => 'left', // Derecha
                        'vertical' => 'center',  // Centro vertical
                    ],
                ],
            // Cambiar tamaño de fuente para toda la hoja
            'A5:T100' => [
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
