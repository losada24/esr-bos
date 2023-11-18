<?php

namespace App\Http\Controllers;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateEstimate;
use App\Actions\UpdateEstimate;
use App\Http\Requests\StoreEstimateRequest;
use App\Http\Requests\UpdateEstimateRequest;
use App\Http\Requests\StoreEstimateToOrderRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Http\Resources\OrderCollection;
use App\Models\Client;
use App\Models\Order;
use App\Enum\OrderStatusEnum;

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
            Order::where('status', OrderStatusEnum::$ESTIMATE)->filter($request->only(['text']))
              ->orderBy('id', 'desc')
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
        return redirect()->route('estimate.show', ['estimate' => $estimate->id]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $estimate)
    {
        $estimate->load(['client']);
        return Inertia::render('Estimate/Edit', [
          'estimate' => $estimate,
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
          'clients' => Client::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEstimateRequest $updateEstimateRequest, UpdateEstimate $updateEstimate, Order $estimate)
    {
        $updateEstimate->handle($updateEstimateRequest, $estimate);
        return redirect()->route('estimate.index')
          ->with('success', 'Estimate updated successfully.');
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

     /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Inertia::render('Estimate/Show', [
          'estimate' => Order::with(['client', 'products'])->findOrFail($id)
        ]);
    }

    public function orderStore(StoreEstimateToOrderRequest $request, CreateEstimateOrder $createEstimateOrder, Order $estimate)
    {
        $createEstimateOrder->handle($request, $estimate);
        $estimate = Order::findOrFail($request->id);
        $estimate->status = OrderStatusEnum::$ACCOUNTING;
        $estimate->save();
        return redirect()->route('estimate.index')
          ->with('success', 'Order created successfully.');
    }
}
