<?php

namespace App\Actions;

use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\PlaningDateSupervisorEnum;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Models\Order;
use App\Models\OrderClientTemps;
use App\Models\OrderProduct;
use App\Models\SupervisorComissionOrder;
use App\Traits\ComissionSupervisor;
use App\Traits\OrderEmails;
use App\Traits\OrderStatus;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateQualifiedOrder
{

  use OrderEmails, OrderStatus, ComissionSupervisor;
 
  public function handle(Request $request)
  {
    DB::transaction(function () use ($request) {
      /*$client = Client::create([
        'name' => $request->client_name,
        'phone' => $request->phone,
        'source' => $request->source,
        'user_id' => auth()->user()->id,
      ]);*/
      $status = OrderStatusEnum::QUALIFIED->value;
        if ($request->order_type === OrderTypeEnum::RESIDENTIAL->value || $request->order_type === OrderTypeEnum::SUPPLY->value) {
          $status = OrderStatusEnum::PENDING_ASSIGNMENT->value;
        } 
        if ($request->order_type === OrderTypeEnum::COMMERCIAL->value) {
          $status = OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value;
        }
  
      $order = Order::create([
        'client_id' => $request->client_id,
        'user_id' => auth()->user()->id,
        'order_type' => $request->order_type,
        'name' => $request->name,
        'job_address' => $request->job_address,
        'city' => $request->city,
        'job_state' => $request->job_state,
        'job_zip' => $request->job_zip,
        'description' => $request->description,
        'status' => $status,
        'source' => $request->source ? $request->source : '',
        'bid_due_date' => $request->bid_due_date ? $request->bid_due_date : null,
        'is_supply' => $request->is_supply ? $request->is_supply : false,
        'schedule_appointment' => $request->schedule_appointment ? $request->schedule_appointment : null,
        
      ]);
      
      $hasAnySaleFormData =
          $request->boolean('sale') ||
          $request->boolean('installation') ||
          $request->boolean('permit') ||
          $request->boolean('replacement') ||
          $request->boolean('new_construction') ||
          $request->boolean('financing') ||
          $request->boolean('screen') ||
          $request->boolean('design') ||              // OJO con "door_design", ver nota abajo
          $request->boolean('mountin') ||
          $request->boolean('bar') ||
          $request->boolean('shutter_hole') ||
          $request->boolean('floor_cutting') ||
          $request->boolean('interior_finish') ||
          $request->boolean('hoa') ||
          $request->filled('floor') ||
          $request->filled('frame_color') ||
          $request->filled('glass_color') ||
          $request->filled('glass_type') ||
          $request->filled('glass_coating') ||
          $request->filled('language') ||
          ((int)$request->input('door_quantity', 0) > 0) ||
          ((int)$request->input('window_quantity', 0) > 0);

          if ($hasAnySaleFormData) {
          $payload = [
              'sale'             => $request->boolean('sale'),
              'installation'     => $request->boolean('installation'),
              'permit'           => $request->boolean('permit'),
              'replacement'      => $request->boolean('replacement'),
              'new_construction' => $request->boolean('new_construction'),
              'financing'        => $request->boolean('financing'),
              'screen'           => $request->boolean('screen'),
              // Si tu checkbox en la vista se llama "door_design", mapea así:
              'design'           => $request->boolean('door_design') ?: $request->boolean('design'),
              'mountin'          => $request->boolean('mountin'),
              'bar'              => $request->boolean('bar'),
              'shutter_hole'     => $request->boolean('shutter_hole'),
              'floor_cutting'    => $request->boolean('floor_cutting'),
              'interior_finish'  => $request->boolean('interior_finish'),
              'hoa'              => $request->boolean('hoa'),

              'floor'            => $request->input('floor', ''),
              'frame_color'      => $request->input('frame_color', ''),
              'glass_color'      => $request->input('glass_color', ''),
              'glass_type'       => $request->input('glass_type', ''),
              'glass_coating'    => $request->input('glass_coating', ''),
              'language'         => $request->input('language', ''),

              'door_quantity'    => (int)$request->input('door_quantity', 0),
              'window_quantity'  => (int)$request->input('window_quantity', 0),
          ];
          $order->saleForm()->create($payload); 
        }

          // Crea la relación 1:1
          



       $order->orderStatus()->create([
        'status' => OrderStatusEnum::QUALIFIED->value,
        'user_id' => auth()->user()->id,
        'notes' => "$status created by " . auth()->user()->name,
      ]);

      $order->orderStatus()->create([
        'status' => $status,
        'user_id' => auth()->user()->id,
        'notes' => "$status created by " . auth()->user()->name,
      ]);

       $order->load('saleForm', 'client');

      $this->sendEmail($order);
      if (!$order) {
        throw new \Exception('Order not created');
      }

       if ($request->order_type === OrderTypeEnum::COMMERCIAL->value) {
            // Mapea cliente ⇢ compañía a actualizar
            /* $pairs = [
                ['client' => (int) $request->client_id,               'company' => (int) $request->company_contact_id],
                ['client' => (int) $request->associate_client_id_1,   'company' => (int) $request->associate_company_contact_id_1],
                ['client' => (int) $request->associate_client_id_2,   'company' => (int) $request->associate_company_contact_id_2],
            ];

            //dd($pairs);

            foreach ($pairs as $p) {
                $clientId  = $p['client'];
                $companyId = $p['company'];

                // Saltar si viene 0/null
                if ($clientId > 0 && $companyId > 0) {
                    Client::whereKey($clientId)->update(['company_contact_id' => $companyId]);
                }
            }
                      
              // Asociado 1
              if ((int) $request->associate_client_id_1 > 0) {
                  OrderClientTemps::create([
                      'order_id'=> $order->id,
                      'client_id'=> (int) $request->associate_client_id_1,
                  ]);
              }

              // Asociado 2
              if ((int) $request->associate_client_id_2 > 0) {
                  OrderClientTemps::create([
                      'order_id'=> $order->id,
                      'client_id'=> (int) $request->associate_client_id_2,
                  ]);
              }*/
                  // Helper para imponer: un cliente solo puede tener UNA compañía
            $applyCompanyToClient = function (?int $clientId, ?int $companyId, string $fieldForError) {
                if (!$clientId || !$companyId) return; // 0/null → ignorar
                $client = Client::find($clientId);
                if (!$client) return;

                // Si no tenía compañía, se fija ahora
                if (empty($client->company_contact_id)) {
                    $client->update(['company_contact_id' => $companyId]);
                    return;
                }

                // Si ya tenía y es otra, error
                if ((int)$client->company_contact_id !== (int)$companyId) {
                    throw ValidationException::withMessages([
                        $fieldForError => 'Este cliente ya está asociado a otra compañía y no puede cambiarse.',
                    ]);
                }
            };

            // Aplica reglas para: principal y asociados
            $applyCompanyToClient(
                (int)$request->client_id,
                (int)$request->company_contact_id,
                'company_contact_id'
            );
            $applyCompanyToClient(
                (int)$request->associate_client_id_1,
                (int)$request->associate_company_contact_id_1,
                'associate_company_contact_id_1'
            );
            $applyCompanyToClient(
                (int)$request->associate_client_id_2,
                (int)$request->associate_company_contact_id_2,
                'associate_company_contact_id_2'
            );

            // Guarda SOLO los asociados en order_clients_temps (como ya haces)
            if ((int)$request->associate_client_id_1 > 0) {
                OrderClientTemps::create([
                    'order_id'  => $order->id,
                    'client_id' => (int)$request->associate_client_id_1,
                ]);
            }
            if ((int)$request->associate_client_id_2 > 0) {
                OrderClientTemps::create([
                    'order_id'  => $order->id,
                    'client_id' => (int)$request->associate_client_id_2,
                ]);
            }
        }
    });
  }
}
