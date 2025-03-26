<?php

namespace App\Exports;

use App\Models\Biweekly;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Traits\Reports;
use Carbon\Carbon;

class InstallerExport implements FromView, WithStyles
{
  use Reports;

  public $id;
  public $biweekly;

  public function __construct($id, $biweekly)
  {
    $this->id = $id;
    $this->biweekly = $biweekly;
  }

  public function view(): View
  {
    $orders = $this->getOrdersByInstaller($this->id, $status = null, $startDate = null, $endDate = null, $orderStatu = null);
    $biweekly = Biweekly::find((int)$this->biweekly);
    $installerName = $orders->first()['installer'] ?? '';
    $companyName = $orders->first()['company_name'] ?? '';
    return view('excels.installer-list', [
      'installer' =>  $installerName,
      'company' => $companyName,
      'orders' => $orders,
      'biweeklyTitle' => $biweekly,
      'biweekly' => Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D'),
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
    ];
  }
}
