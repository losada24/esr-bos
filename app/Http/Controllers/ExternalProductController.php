<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\CreateExternalProduct;
use App\Actions\UpdateExternalProduct;
use App\Enum\ExternalProductEnum;
use App\Http\Requests\StoreExternalProductsRequest;
use App\Http\Requests\UpdateExternalProductsRequest;
use App\Models\ExternalProductConfiguration;

class ExternalProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Inertia::render('ExternalProducts/Index', [
          'externalProducts' => ExternalProductConfiguration::orderBy('external_product', 'asc')
              ->orderBy('width', 'asc')
              ->orderBy('height', 'asc')
              ->paginate()
              ->withQueryString()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('ExternalProducts/Create', [
          'externalProducts' => [
            ExternalProductEnum::$MULLION,
            ExternalProductEnum::$CASEMENT,
          ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreExternalProductsRequest $storeExternalProductRequest, CreateExternalProduct $createExternalProduct)
    {
        $externalProduct = $createExternalProduct->handle($storeExternalProductRequest);
        return redirect()->route('external-products.index')
          ->with('success', 'External Product created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ExternalProductConfiguration $externalProduct)
    {
        return Inertia::render('ExternalProducts/Edit', [
          'externalProduct' => $externalProduct,
          'externalProducts' => [
            ExternalProductEnum::$MULLION,
            ExternalProductEnum::$CASEMENT,
          ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateExternalProductsRequest $updateProductsRequest, UpdateExternalProduct $updateExternalProduct, ExternalProductConfiguration $externalProduct)
    {
        $updateExternalProduct->handle($updateProductsRequest, $externalProduct);
        return redirect()->route('external-products.index')
          ->with('success', 'External Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExternalProductConfiguration $externalProduct)
    {
        $externalProduct->delete();
        return redirect()
          ->back()
          ->with('success', 'External product deleted successfully.');
    }

    public function duplicate(ExternalProductConfiguration $externalProduct)
    {
        $newProduct = $externalProduct->replicate();
        $newProduct->save();

        return redirect()
        ->back()
        ->with('success', 'Product duplicated successfully.');
    }
}
