<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enum\FrameColorEnum;
use App\Enum\GlassColorEnum;
use App\Models\Order;
use App\Http\Requests\StoreSingleHuntRequest;
use App\Http\Requests\UpdateFixedWindowsRequest;
use App\Actions\CreateFixedWindows;
use App\Models\Product;
use App\Actions\UpdateFixedWindows;

class SingleHuntController extends Controller
{
  /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

  public function create($id)
  {
      return Inertia::render('SingleHunt/Create', [
        'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
        'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
        'estimate' => Order::with(['client'])->withCount(['products'])->findOrFail($id),
      ]);
  }

  /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSingleHuntRequest $storeSingleHuntRequest, CreateSingleHunt $createSingleHunt)
    {
        $createSingleHunt->handle($storeSingleHuntRequest);
        return redirect()->route('estimate.show', ['estimate' => $storeSingleHuntRequest->order_id]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
      return Inertia::render('SingleHunt/Edit', [
          'frame_colors' => array_values(FrameColorEnum::$FRAME_COLOR),
          'glass_colors' => array_values(GlassColorEnum::$GLASS_COLOR),
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
    public function update(UpdateFixedWindowsRequest $updateFixedWindowsRequest, UpdateFixedWindows $updateFixedWindows, Product $product)
    {
        $updateFixedWindows->handle($updateFixedWindowsRequest, $product);
        return redirect()->route('estimate.show', ['estimate' => $product->order_id])
          ->with('success', 'Fixed Windows updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()
          ->back()
          ->with('success', 'Fixed Windows deleted successfully.');
    }
}
