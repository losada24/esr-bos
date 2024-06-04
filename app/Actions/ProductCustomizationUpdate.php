<?php
namespace App\Actions;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enum\RoleEnum;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ProductCustomizationUpdate {

  public function handle(Request $request, Product $product) {

    DB::transaction(function() use ($request, $product) {

      if( !$product )
      {
          throw new \Exception('Not not updated');
      }

      $attachmentPath = $product->attachment;
      if ($request->hasFile('attachment')) {
        $fileName = time() . '_' . $request->file('attachment')->getClientOriginalName();
        $tempOldImagePath = $attachmentPath;
        $attachmentPath = $request->file('attachment')->storeAs('products', $fileName, 'public');
        if ($attachmentPath && $tempOldImagePath) {
          Storage::disk('public')->delete($tempOldImagePath);
        }
      }
      
      $productData = [
        'attachment' => $attachmentPath,
        'comments' => $request->comments,
        'user_id' => auth()->user()->id,
      ];

      $product->update($productData);

    });
  }
}
