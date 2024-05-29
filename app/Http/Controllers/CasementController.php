<?php

namespace App\Http\Controllers;

use App\Actions\CreateCasement;
use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Models\Order;
use App\Actions\UpdateCasement;
use App\Models\Product;
use App\Enum\ExternalProductEnum;
use App\Enum\GlassColorEnum;
use App\Enum\MuntinPatternEnum;
use App\Enum\MuntinStyleEnum;
use App\Http\Requests\StoreCasementRequest;
use App\Http\Requests\UpdateCasementRequest;
use App\Models\ExternalProductConfiguration;
use App\Traits\ExternalProductTrait;

class CasementController extends Controller
{

    use ExternalProductTrait;
  /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

  public function create($id)
  {
      $externalProducts = ExternalProductConfiguration::where('external_product', ExternalProductEnum::$CASEMENT)->get();
      return Inertia::render('Casement/Create', [
        'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
        'opening' => array_values($this->getCasementOpeningOptions()),
        'glass_colors' => array_values(GlassColorEnum::getExternalGlassColor()),
        'muntin_patterns' => array_values(MuntinPatternEnum::$MUNTIN_PATTERN),
        'muntin_styles' => array_values(MuntinStyleEnum::$MUNTIN_STYLE),
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
    public function store(StoreCasementRequest $storeCasementRequest, CreateCasement $createCasement)
    {
        $createCasement->handle($storeCasementRequest);
        return redirect()->route('estimate.show', ['estimate' => $storeCasementRequest->order_id])
          ->with('success', 'Casement created successfully.');
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
      $externalProducts = ExternalProductConfiguration::where('external_product', ExternalProductEnum::$CASEMENT)->get();
      return Inertia::render('Casement/Edit', [
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'opening' => array_values($this->getCasementOpeningOptions()),
          'glass_colors' => array_values(GlassColorEnum::getExternalGlassColor()),
          'muntin_patterns' => array_values(MuntinPatternEnum::$MUNTIN_PATTERN),
          'muntin_styles' => array_values(MuntinStyleEnum::$MUNTIN_STYLE),
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
    public function update(UpdateCasementRequest $updateCasementRequest, UpdateCasement $updateCasement, Product $product)
    {
        $updateCasement->handle($updateCasementRequest, $product);
        return redirect()->route('estimate.show', ['estimate' => $product->order_id])
          ->with('success', 'Casement updated successfully.');
    }
}
