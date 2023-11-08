<?php

namespace App\Http\Controllers;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateEstimate;
use App\Actions\UpdateRawMaterial;
use App\Http\Requests\StoreEstimateRequest;
use App\Http\Requests\UpdateRawMaterialRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\RawMaterialResource;
use App\Models\Client;
use App\Models\Order;

class EstimateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('Estimate/Index', [
          'estimates' => new OrderCollection(
            Order::filter($request->only(['text']))
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
        return Inertia::render('Estimate/Create', [
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
          'clients' => Client::all(), // TODO: ONLY SHOW CLIENTS THAT BELONG TO THE USER
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEstimateRequest $storeEstimateRequest, CreateEstimate $createEstimate)
    {
        $estimate = $createEstimate->handle($storeEstimateRequest);
        return redirect()->route('product.index', ['id' => $estimate->id]);
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
    public function destroy(Order $estimate)
    {
        $estimate->delete();
        return redirect()
          ->back()
          ->with('success', 'Estimate deleted successfully.');
    }
}
