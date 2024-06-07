<?php

namespace App\Http\Controllers;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateEstimate;
use App\Actions\CreateEstimateOrder;
use App\Actions\ProductCustomizationUpdate;
use App\Actions\UpdateEstimate;
use App\Http\Requests\StoreEstimateRequest;
use App\Http\Requests\UpdateEstimateRequest;
use App\Http\Requests\StoreEstimateToOrderRequest;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Enum\GlassTypeEnum;
use App\Http\Resources\OrderCollection;
use App\Models\Client;
use App\Models\Order;
use App\Enum\OrderStatusEnum;
use App\Enum\States;
use App\Http\Requests\UpdateProductCustomizationRequest;
use App\Models\Product;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

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
            Order::estimates()
              ->with(['products'])
              ->withCount('products')
              ->filter($request->only(['text']))
              ->orderBy('updated_at', 'desc')
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
          'glass_types' => array_values(GlassTypeEnum::$GLASS_TYPE),
          'clients' => Client::all(),
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
          'glass_types' => array_values(GlassTypeEnum::$GLASS_TYPE),
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
          'estimate' => Order::with(['client', 'products' => function (Builder $builder) {
            $builder->orderBy('product_sort', 'asc');
          }, 'user.company'])->findOrFail($id)
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function order($id)
    {
        $estimate = Order::with(['client', 'products'  => function (Builder $builder) {
          $builder->orderBy('product_sort', 'asc');
        }, 'user.company'])->findOrFail($id); //TODO: not allow pay two times same order

        if ($estimate->status != OrderStatusEnum::$ESTIMATE) {
          return redirect()
            ->route('estimate.index')
            ->with('error', 'This estimate is not available for payment.');
        }

        return Inertia::render('Estimate/Payment', [
          'estimate' => $estimate,
          'states' => array_values(States::$USA_STATES),
        ]);
    }

    public function orderStore(StoreEstimateToOrderRequest $request, CreateEstimateOrder $createEstimateOrder)
    {
        $estimate = Order::findOrFail($request->order_id); //TODO: not allow pay two times same order
        if (empty($estimate->external_purchase_id)) {
          return redirect()
            ->back()
            ->with('error', 'To create an order, you must first fill in the External Purchase Id.');
        }

        $createEstimateOrder->handle($request, $estimate);
        return redirect()->route('estimate.index')
          ->with('success', 'Order created successfully.');
    }

    public function duplicate($id)
    {
        $message = 'Estimate duplicated successfully.';
        $estimate = Order::findOrFail($id);
        $newEstimate = $estimate->replicate();
        if ($newEstimate->status != OrderStatusEnum::$ESTIMATE && $newEstimate->status != OrderStatusEnum::$SUB_DEALER_ESTIMATE) {
          $newEstimate->status = OrderStatusEnum::$ESTIMATE;
          $message = 'The order was duplicated successfully as estimate.';
        }
        $newEstimate->name = $newEstimate->name . ' (copy)';
        $newEstimate->user_id = auth()->user()->id;
        $newEstimate->push();

        foreach ($estimate->products as $product) {
          $newProduct = $product->replicate();
          $newEstimate->products()->save($newProduct);
        }

        return redirect()
          ->route('estimate.index')
          ->with('success', $message);
    }

    public function customizationUpdate(ProductCustomizationUpdate $productCustomizationUpdate, UpdateProductCustomizationRequest $updateProductCustomizationRequest, Product $product) 
    {
      $productCustomizationUpdate->handle($updateProductCustomizationRequest, $product);
      return redirect()
          ->back()
          ->with('success', 'Customization request updated successfully.');
    }

    public function attachmentDelete(Product $product)
    {
      if (Storage::disk('public')->exists($product->attachment)) {
        Storage::disk('public')->delete($product->attachment);
      }

      $product->attachment = null;
      $product->save();

      return redirect()
          ->back()
          ->with('success', 'Attachment deleted successfully.');
    }
}
