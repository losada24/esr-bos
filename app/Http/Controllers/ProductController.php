<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Product;

class ProductController extends Controller
{
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
          ->with('success', 'Product deleted successfully.');
    }
}
