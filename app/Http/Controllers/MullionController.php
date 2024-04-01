<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Models\Order;
use App\Actions\CreateMullion;
use App\Models\Product;
use App\Actions\UpdateMullion;
use App\Enum\ExternalProductEnum;
use App\Http\Requests\StoreMullionRequest;
use App\Http\Requests\UpdateMullionRequest;
use App\Models\ExternalProductConfiguration;
use App\Traits\ExternalProductTrait;

class MullionController extends Controller
{

    use ExternalProductTrait;
  /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

  public function create($id)
  {
      $externalProducts = ExternalProductConfiguration::where('external_product', ExternalProductEnum::$MULLION)->get();
      return Inertia::render('Mullion/Create', [
        'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
        'external_products' => $this->getExtraMullionFields($externalProducts),
        'estimate' => Order::with(['client'])->withCount(['products'])->findOrFail($id),
      ]);
  }

  /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMullionRequest $storeMullionRequest, CreateMullion $createMullion)
    {
        $createMullion->handle($storeMullionRequest);
        return redirect()->route('estimate.show', ['estimate' => $storeMullionRequest->order_id])
          ->with('success', 'Mullion created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
      $product->loadMissing('order');
      $externalProducts = ExternalProductConfiguration::where('external_product', ExternalProductEnum::$MULLION)->get();
      return Inertia::render('Mullion/Edit', [
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'external_products' => $this->getExtraMullionFields($externalProducts),
          'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMullionRequest $updateMullionRequest, UpdateMullion $updateMullion, Product $product)
    {
        $updateMullion->handle($updateMullionRequest, $product);
        return redirect()->route('estimate.show', ['estimate' => $product->order_id])
          ->with('success', 'Mullion updated successfully.');
    }
}
