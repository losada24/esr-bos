<?php
namespace App\Actions;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Traits\OrderEmails;
use App\Traits\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateOrder {

  use OrderEmails, OrderStatus;

  public function handle(Request $request, Order $order) {

    DB::transaction(function() use ($request, $order) {

      if ($request->client_id == 0) {
        $client = Client::create([
          'name' => $request->client_name,
          'last_name' => $request->last_name,
          'phone' => $request->phone,
          'email' => $request->email,
        ]);
      } else {
        $client = Client::find($request->client_id);
      }

      $status = $this->getStatus($request);
      $sendEmail = $status != $order->status;
      $orderData = [
        'client_id' => $client->id,
        'user_id' => auth()->user()->id,
        'name' => $request->name,
        'job_address' => $request->job_address,
        'order_number' => $request->order_number,
        'type_of_work_id' => $request->type_of_work_id, 
        'type_of_housing_id' => $request->type_of_housing_id,
        'supervisor_id' => $request->supervisor_id,
        'travel_cost_id' => $request->travel_cost_id,
        'duration_of_work_id' => $request->duration_of_work_id,
        'duration_of_work_id' => $request->duration_of_work_id,
        'method_of_payment' => $request->method_of_payment,
        'service' => $request->service,
        'contract_signing_date' => $request->contract_signing_date,
        'payment_factory_date' => $request->payment_factory_date,
        'entry_date' => $request->entry_date,
        'eta_date' => $request->eta_date,
        'installation_date' => $request->installation_date,
        'installation_end_date' => $request->installation_end_date,
        'additional_travel_costs' => $request->additional_travel_costs,
        'city_permits' => $request->city_permits,
        'association_permits' => $request->association_permits,
        'equipment_rental' => $request->equipment_rental,
        'notes' => $request->notes,
        'delivery_date' => $request->delivery_date,
        'status' => $status,
        'frame_color' => $request->frame_color,
        'cost_delivery' => $request->cost_delivery
      ];

      $order->update($orderData);
      if ($request->hasFile('attachments')) {
        $files = $request->file('attachments');
        foreach ($files as $file) {
          $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
          $filePath = $file->storeAs('order_files', $fileName, 'public');
          $order->attachments()->create([
            'filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => 'order_files'
          ]);
        }
      }

      $order->installationTeams()->sync($request->installation_teams);
      $order->owners()->sync($request->owners);

      $order->orderProducts()->delete();
      foreach ($request->orderProducts as $product) {
        $orderProduct = OrderProduct::create([
          'order_id' => $order->id,
          'product_config_id' => $product['product_config_id'],
          'type_of_work_id' => $product['type_of_work_id'],
          'height' => $product['height'],
          'width' => $product['width'],
          'qty' => $product['qty'],
          'unit_price' => $product['unit_price'],
          'total_price' => $product['total_price'],
          'unit_price_with_extraworks' => $product['unit_price_with_extraworks'],
          'total_price_with_extraworks' => $product['total_price_with_extraworks'],
          'extra_work_price' => $product['extra_work_price'],
          'notes' => $product['notes'],
          'storefront_area' => $product['storefront_area'],
          'installation_other_level' => $product['installation_other_level'],
          'product_category_id' => $product['product_category_id'],
          'type_of_product_id' => $product['type_of_product_id'],
        ]);
        
        $extraWorks = [];
        $product_extra_works = $product['extra_works'] ?? [];
        
        for ($i = 0; $i < count($product_extra_works); $i++) {
          $extraWorks[$product_extra_works[$i]['extra_work_id']] = [
            'price' => $product_extra_works[$i]['price'],
            'amount' => $product_extra_works[$i]['amount'],
          ];
        }

        $orderProduct->orderProductExtraWorks()->attach($extraWorks);
      }
      
      if ($sendEmail) {
        $this->sendEmail($order);
      }
      
      if( !$order )
      {
          throw new \Exception('Not not updated');
      }

    });
  }
}
