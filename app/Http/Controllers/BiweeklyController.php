<?php

namespace App\Http\Controllers;

use App\Actions\CreateBiweekly;
use App\Actions\UpdateBiweekly;
use App\Models\Biweekly;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BiweeklyController extends Controller
{

  public function index()
  {
    return Inertia::render('Biweekly/Index', [
      'biweeklies' => Biweekly::paginate()->withQueryString()
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
      ->with('success', 'Order updated successfully.');
  }
}
