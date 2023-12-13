<?php

namespace App\Http\Controllers;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateRawMaterial;
use App\Actions\UpdateRawMaterial;
use App\Http\Requests\StoreRawMaterialRequest;
use App\Http\Requests\UpdateRawMaterialRequest;
use App\Enum\UnitOfMeasurement;
use App\Http\Resources\RawMaterialCollection;
use App\Http\Resources\RawMaterialResource;

class RawMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('RawMaterial/Index', [
          'rawMaterials' => new RawMaterialCollection(
            RawMaterial::filter($request->only(['text']))
              ->orderBy('name')
              ->paginate()
              ->withQueryString()
            )
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('RawMaterial/Create', [
          'unit_of_measurement' => array_values(UnitOfMeasurement::$UNIT_OF_MEASUREMENT),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRawMaterialRequest $storeRawMaterialRequest, CreateRawMaterial $createRawMaterial)
    {
        $createRawMaterial->handle($storeRawMaterialRequest);
        return redirect()->route('raw-material.index')
          ->with('success', 'Raw Material created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(RawMaterial $rawMaterial)
    {
        RawMaterialResource::withoutWrapping();
        return Inertia::render('RawMaterial/Edit', [
          'rawMaterial' => new RawMaterialResource($rawMaterial),
          'unit_of_measurement' => array_values(UnitOfMeasurement::$UNIT_OF_MEASUREMENT),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRawMaterialRequest $updateRawMaterialRequest, UpdateRawMaterial $updateRawmaterial, RawMaterial $rawMaterial)
    {
        $updateRawmaterial->handle($updateRawMaterialRequest, $rawMaterial);
        return redirect()->route('raw-material.index')
          ->with('success', 'Client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(RawMaterial $rawMaterial)
    {
        $rawMaterial->delete();
        return redirect()
          ->back()
          ->with('success', 'Raw Material deleted successfully.');
    }
}
