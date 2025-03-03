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
        $payments = $this->getOrdersByInstallerBiweekly($this->id);
        $installerName = $payments->first()['installation_team'];
        $companyName = $payments->first()['company_name'];
        $biweekly = $payments->first()['biweekly'];
       
        
        //dd($payments);
        
        return view('excels.installer-list', [
          'payments' => $payments,
          'installer' =>  $installerName,
          'company' => $companyName,
          'biweekly' => $biweekly,
        ]);
    }

    public function styles(Worksheet $sheet)
    {  
      //$sheet->setTitle( );
     
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
