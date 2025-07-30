<?php

namespace App\Http\Controllers;

use App\Actions\CreateBiweekly;
use App\Actions\UpdateBiweekly;
use App\Exports\PaymentBiweeklyExport;
use App\Models\Biweekly;
use App\Models\HistoryPendingPayment;
use App\Models\InstallationPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BiweeklyController extends Controller
{

  public function index()
  {
    return Inertia::render('Biweekly/Index', [
      'biweeklies' => Biweekly::orderBy('id', 'desc')->paginate()->withQueryString(),
    ]);
  }
  public function create()
  {
    // Retornar la vista con los datos
    return Inertia::render('Biweekly/Create', []);
  }

  public function store(Request $request, CreateBiweekly $createBiweekly)
  {
    $createBiweekly->handle($request);
    return redirect()->route('biweekly.index')
      ->with('success', 'Order updated successfully.');
  }

  public function edit($id)
  {   // Cargar la orden junto con los campos relacionados

    $biweekly = Biweekly::findOrFail($id);
    $period[] = $biweekly->start_biweekly_period;
    $period[] = $biweekly->end_biweekly_period;
    //dd( $period );
    // Retornar la vista con los datos
    return Inertia::render('Biweekly/Edit', [
      'biweekly' => $biweekly,
      'period' => $period
    ]);
  }

  public function update(Request $request, UpdateBiweekly $updateBiweekly)
  {
    
    $biweekly = Biweekly::findOrFail((int)$request->input('id'));
  
    $updateBiweekly->handle($request, $biweekly);
    return redirect()->route('biweekly.index')
      ->with('success', 'Biweekly updated successfully.');
  }

  public function showInstallerBiweekly($id)
  {  
        $biweekly = HistoryPendingPayment::with('installationTeam')
        ->where('biweekly_id', $id)
        ->where('type_history', 'INSTALLER')
        ->get();
        //dd($biweekly);

        $biweekly1 = Biweekly::find($id);
        $biweeklyTitle = Carbon::parse($biweekly1->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly1->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
      
        return Inertia::render('Biweekly/ShowInstaller', [
          'biweeklies' => $biweekly,
          'biweekly_id' => $id,
          'biweeklyTitle' => $biweeklyTitle,
        ]);
  }
  public function showPdfBiweekly($biweeklyId,$installerId)
  {       
           $biweekly = HistoryPendingPayment::with('installationTeam')
            ->where('biweekly_id', $biweeklyId)
            ->where('installation_team_id', $installerId)
            ->where('type_history', 'INSTALLER')
            ->get();

            //dd($biweekly);
            $biweeklys= $biweekly[0]['data'];
          

            $installerName =$biweekly[0]['data'][0]['installer'] ?? '';
            $companyName = $biweekly[0]['data'][0]['company_name'] ?? '';
            $biweekly = Biweekly::find($biweeklyId);
            $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
            $pdf = Pdf::loadView('pdf.review-list-orders', ['biweeklys' => $biweeklys, 'company' => $companyName, 'installer' => $installerName,  'biweeklyTitle' => $biweeklyTitle])->setPaper('A2', 'landscape');
            $pdfName = 'Review-Payment' .$installerName. '-' .$biweeklyTitle .  '.pdf';
            return $pdf->stream($pdfName);
  }

  public function exportBiweeklyPayment($biweeklyId,$installerId)
  {  
        return Excel::download(
          new PaymentBiweeklyExport($biweeklyId, $installerId),
          'Biweekly ' . $installerId . ' to ' . $installerId . '.xlsx',
          \Maatwebsite\Excel\Excel::XLSX
        );
  }

  /*public function showPdfBiweeklyPayment($installerId,$biweeklyId)
  {       
            $biweekly = InstallationPayment::with('order')
            ->where('biweekly_id', $biweeklyId)
            ->where('installation_team_id', $installerId)
            ->get();
            //$biweeklys= $biweekly[0]['data'];
            //dd($installerId,$biweeklyId);
            dd($biweekly);
            $installerName = $biweekly[0]['data'][0]['installer'] ?? '';
            $companyName = $biweekly[0]['data'][0]['company_name'] ?? '';
            $biweekly = Biweekly::find($biweeklyId);
            $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
            $pdf = Pdf::loadView('pdf.review-list-orders', ['biweeklys' => $biweeklys, 'company' => $companyName, 'installer' => $installerName,  'biweeklyTitle' => $biweeklyTitle])->setPaper('A2', 'landscape');
            $pdfName = 'Review-Payment' .$installerName. '-' .$biweeklyTitle .  '.pdf';
            return $pdf->stream($pdfName);
  }*/


  public function showPdfBiweeklyPaymentResumen($installerId,$biweeklyId)
  {       
           $biweekly = HistoryPendingPayment::with('installationTeam')
            ->where('biweekly_id', $biweeklyId)
            ->where('installation_team_id', $installerId)
            ->where('type_history', 'INSTALLER')
            ->get();
            $biweeklys= $biweekly[0]['data'];
            //dd($biweeklys);

            $installerName =$biweekly[0]['data'][0]['installer'] ?? '';
            $companyName = $biweekly[0]['data'][0]['company_name'] ?? '';
            $biweekly = Biweekly::find($biweeklyId);
            $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
            $pdf = Pdf::loadView('pdf.resume-payment', ['biweeklys' => $biweeklys, 'company' => $companyName, 'installer' => $installerName,  'biweeklyTitle' => $biweeklyTitle])->setPaper('A2', 'landscape');
            $pdfName = 'Resumen-Payment' .$installerName. '-' .$biweeklyTitle .  '.pdf';
            return $pdf->stream($pdfName);
  }

  public function showPdfBiweeklyPaymentResumenGeneral($biweeklyId)
  {       
           $biweeklys = HistoryPendingPayment::with('installationTeam')
            ->where('biweekly_id', $biweeklyId)
            ->where('type_history', 'INSTALLER')
            ->get();

            //dd($biweeklys [0]['data']);

            $installerName =$biweekly[0]['data'][0]['installer'] ?? '';
            $companyName = $biweekly[0]['data'][0]['company_name'] ?? '';
            $biweekly = Biweekly::find($biweeklyId);
            $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
            $pdf = Pdf::loadView('pdf.resume-general-payment', ['biweeklys' => $biweeklys,'biweeklyTitle' => $biweeklyTitle])->setPaper('A2', 'landscape');
            $pdfName = 'Resumen-General-Payment' .$installerName. '-' .$biweeklyTitle .  '.pdf';
            return $pdf->stream($pdfName);
  }

  
  public function showPdfBiweeklyPaymentExtraWork($biweeklyId)
  {       
           $extraworks = InstallationPayment::with(['installationTeam', 'order.supervisor','order.owners'])
            ->where('biweekly_id', $biweeklyId)
             ->where('extra_work', '>', 0.00)
             ->where('payment_status', 'PAID')
            ->get();

            //dd($extraworks);

            $biweekly = Biweekly::find($biweeklyId);
            $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
            $pdf = Pdf::loadView('pdf.resume-payment-extrawork', ['extraworks' => $extraworks,'biweeklyTitle' => $biweeklyTitle])->setPaper('A2', 'landscape');
            $pdfName = 'Resumen-Payment-ExtraWork' .$biweeklyTitle .  '.pdf';
            return $pdf->stream($pdfName);
  }
  public function uncollectedCustomerPaymentsReport($biweeklyId)
  {       
           $biweeklys = HistoryPendingPayment::with('installationTeam')
            ->where('biweekly_id', $biweeklyId)
            ->where('type_history', 'INSTALLER')
            ->get();

            //dd($biweeklys);
            $uncollected = collect();

            foreach ($biweeklys as $uncollectBiweekly) {
              foreach ($uncollectBiweekly['data'] as $uncollect) {
                  $payments = collect($uncollect['installation_payments']);
                  $lastPayment = $payments->last();
      
                  if (!$lastPayment) {
                      continue; // No hay pagos, saltamos este item
                  }
      
                  if (
                      ($lastPayment['percentage_payment'] == '80' && $uncollect['partial_payment_installation'] == 0) ||
                      ($lastPayment['percentage_payment'] == '20' && $uncollect['final_payment_installation'] == 0) ||
                      ($lastPayment['percentage_payment'] == '100' && $uncollect['final_payment_installation'] == 0)
                  ) {
                      $uncollected->push($uncollect);
                  }
              }
          }
             
               //dd($uncollected);
            

            //$installerName =$biweekly[0]['data'][0]['installer'] ?? '';
            //$companyName = $biweekly[0]['data'][0]['company_name'] ?? '';
            $biweekly = Biweekly::find($biweeklyId);
            $biweeklyTitle = Carbon::parse($biweekly->start_biweekly_period)->locale('en')->isoFormat('MMMM D') . ' to ' . Carbon::parse($biweekly->end_biweekly_period)->locale('en')->isoFormat('MMMM D');
            $pdf = Pdf::loadView('pdf.uncollected-payments-report', ['biweeklys' => $uncollected,'biweeklyTitle' => $biweeklyTitle])->setPaper('A2', 'landscape');
            $pdfName = 'Uncollected-Payments-Report' .$biweeklyTitle .  '.pdf';
            return $pdf->stream($pdfName);
  }



}
