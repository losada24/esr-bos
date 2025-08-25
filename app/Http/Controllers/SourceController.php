<?php

namespace App\Http\Controllers;

use App\Actions\CreateBiweekly;
use App\Actions\CreateSource;
use App\Actions\UpdateBiweekly;
use App\Actions\UpdateSource;
use App\Exports\PaymentBiweeklyExport;
use App\Models\Biweekly;
use App\Models\HistoryPendingPayment;
use App\Models\InstallationPayment;
use App\Models\Source;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SourceController extends Controller
{

  public function index()
  {
    return Inertia::render('Source/Index', [
      'sources' => Source::orderBy('id', 'desc')->paginate()->withQueryString(),
    ]);
  }
  public function create()
  {
    // Retornar la vista con los datos
    return Inertia::render('Source/Create', []);
  }

  public function store(Request $request, CreateSource $createSource)
  {
    $createSource->handle($request);
    return redirect()->route('source.index')
      ->with('success', 'Source created successfully.');
  }

  public function edit(Source $source)
  {

      return Inertia::render('Source/Edit', [
          'source' => $source
      ]);
  }


 public function update(Request $request, UpdateSource $updateSource)
  {

    $source = Source::findOrFail((int)$request->input('id'));

    $updateSource->handle($request, $source);
    return redirect()->route('source.index')
      ->with('success', 'Source updated successfully.');
    }
}
