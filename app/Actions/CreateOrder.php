<?php
namespace App\Actions;

use App\Enum\OrderStatusEnum;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\RoleEnum;
use App\Models\Order;
use App\Models\OrderProduct;
use Carbon\Carbon;

class CreateOrder {

  public function handle(Request $request) {
    
    DB::transaction(function() use ($request) {

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

      $estimate_delivery_date = $request->delivery_date;
      if ($request->delivery_date == null) {
        $delivery_date_object = Carbon::parse($request->payment_factory_date);
        $delivery_week = $delivery_date_object->addWeeks(7);
        $end_of_delivery_week = $delivery_week->endOfWeek();
        $estimate_delivery_date = $end_of_delivery_week->previous(Carbon::FRIDAY)->format('Y-m-d');
      }

      $estimate_installation_date = $request->installation_date;
      if ($request->installation_date == null && $request->service == 'INSTALLATION') {
        $installation_date_object = Carbon::parse($request->payment_factory_date);
        $installation_week = $installation_date_object->addWeeks(8);
        $end_of_installation_week = $installation_week->endOfWeek();
        $estimate_installation_date = $end_of_installation_week->previous(Carbon::FRIDAY)->format('Y-m-d');
      }

      $order = Order::create([
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
        'additional_travel_costs' => $request->additional_travel_costs,
        'city_permits' => $request->city_permits,
        'association_permits' => $request->association_permits,
        'equipment_rental' => $request->equipment_rental,
        'notes' => $request->notes,
        'delivery_date' => $estimate_delivery_date,
        'installation_date' => $estimate_installation_date,
        'status' => OrderStatusEnum::PLANNED->value,
      ]);

      if ($request->hasFile('attachments')) {
        $files = $request->file('attachments');
        foreach ($files as $file) {
          $fileName = time() . '_' . $file->getClientOriginalName();
          $filePath = $file->storeAs('order_files', $fileName, 'public');
          $order->attachments()->create([
            'filename' => $fileName,
            'file_path' => $filePath,
            'file_type' => 'order_files'
          ]);
        }
      }

      $order->installers()->attach($request->installation_teams);
      $order->owners()->attach($request->owners);

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
          'notes' => $product['notes'],
          'storefront_area' => $product['storefront_area'],
          'installation_other_level' => $product['installation_other_level'],
          'product_category_id' => $product['product_category_id'],
          'type_of_product_id' => $product['type_of_product_id'],
        ]);

        // dd($product['extra_works']);

        $extraWorks = [];
        $product_extra_works = $product['extra_works'] ?? [];
        
        //dd($product_extra_works); die;
        for ($i = 0; $i < count($product_extra_works); $i++) {
          $extraWorks[$product_extra_works[$i]['extra_work_id']] = [
            'price' => $product_extra_works[$i]['price'],
            'number_of_sides' => $product_extra_works[$i]['number_of_sides'],
          ];
        }
       /* foreach ($product_extra_works as $extraWork) {
          $extraWorks[$extraWork['extra_work_id']] = [
            'price' => $extraWork['price'],
            'number_of_sides' => $extraWork['number_of_sides'],
          ];
        }*/

        //$orderProduct->orderProductExtraWorks()->attach($extraWorks);
      }

      if( !$order )
      {
          throw new \Exception('Client not created');
      }
    });
  }
}
