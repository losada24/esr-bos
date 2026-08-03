<?php

namespace App\Http\Controllers;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusColorEnum;
use App\Mail\DeliveryConfirmed;
use App\Models\InstallationTeam;
use App\Models\Order;
use App\Models\OrderPhase;
use App\Models\OrderStatus as ModelsOrderStatus;
use App\Models\User;
use App\Support\Orders\OrderPhaseManager;
use App\Traits\OrderStatus;
use App\Traits\Twilio;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DashboardController extends Controller
{

  use OrderStatus, Twilio;

  public function index(Request $request): Response|RedirectResponse
  {

    $user = auth()->user();

    if ($user->hasRole(RoleEnum::SERVICE->value) && !$user->hasAnyRole([
      RoleEnum::ADMIN->value,
      RoleEnum::ACCOUNT_MANAGER->value,
      RoleEnum::ACCOUNTING->value,
      RoleEnum::INSTALLER->value,
      RoleEnum::SUPERVISOR->value,
      RoleEnum::OWNER->value,
      RoleEnum::OWNER_ADMIN->value,
      RoleEnum::FRONTDESK->value,
      RoleEnum::FRONTDESK_ADMIN->value,
      RoleEnum::FRONTDESK_ESR->value,
      RoleEnum::SERVICE_MANAGER->value,
      RoleEnum::PAYMENT_COORDINATOR->value,
    ])) {
      return redirect()->route('service-control.calendar');
    }

    $status = [];
    $legend = [];
    $statusmodal = [];
    

    $services = [ 
      ServiceEnum::INSTALLATION->value,
      ServiceEnum::DELIVERY->value,
      ServiceEnum::PICKUP->value,
      ServiceEnum::INSTALLATION_ONLY->value,
      ServiceEnum::SERVICE->value
    ];

    if ($user->hasRole(RoleEnum::ACCOUNT_MANAGER->value) || $user->hasRole(RoleEnum::ADMIN->value) || $user->hasRole(RoleEnum::OWNER->value) || $user->hasRole(RoleEnum::OWNER_ADMIN->value) || $user->hasRole(RoleEnum::FRONTDESK_ADMIN->value) || $user->hasRole('FRONTDESK_ADMIN') ) {
      $status = [
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::REPLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::DELIVERY_CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::RESCHEDULE->value,
        OrderStatusEnum::CONFIRMED_FINISH->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
        OrderStatusEnum::CANCELED->value,
        
        
      ];
      $statusmodal = [  
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::REPLANNED->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::DELIVERY_CONFIRMED->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::RESCHEDULE->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
        OrderStatusEnum::CANCELED->value,  
      ];

      $legend = [
        [
          'color' => StatusColorEnum::PLANNED->value,
          'label' => 'PICKUP PLANNED'
        ],
        [
          'color' => StatusColorEnum::CONFIRMED->value,
          'label' => 'CONFIRMED DELIVERY'
        ],
        [
          'color' => StatusColorEnum::PLANNED_INSTALLATION->value,
          'label' => 'PLANNED DELIVERY DATE'
        ],
        [
          'color' => StatusColorEnum::PLANNED_INSTALLATION_EVENT->value,
          'label' => 'PLANNED INSTALLATION DATE'
        ],
        [
          'color' => StatusColorEnum::CONFIRMED_INSTALLATION->value,
          'label' => 'INSTALLATION CONFIRMED'
        ],
        [
          'color' => StatusColorEnum::CONFIRMED_DELIVERY->value,
          'label' => 'CONFIRMED DELIVERY'
        ],
        [
          'color' => StatusColorEnum::DELAY_PERMITS->value,
          'label' => 'DELAYED PERMIT'
        ],
        [
          'color' => StatusColorEnum::COMPLETE->value,
          'label' => 'COMPLETE'
        ],
        [
          'color' => StatusColorEnum::ON_HOLD->value,
          'label' => 'ON HOLD'
        ],
        [
          'color' => StatusColorEnum::RESCHEDULE->value,
          'label' => 'RESCHEDULE'
        ],
        [
          'color' => StatusColorEnum::FINISH->value,
          'label' => 'FINISH'
        ],
         [
          'color' => StatusColorEnum::CANCELED->value,
          'label' => 'CANCELED'
        ],
      ];
    } else if ($user->hasRole(RoleEnum::SUPERVISOR->value) || $user->hasRole(RoleEnum::SERVICE_MANAGER->value) || $user->hasRole(RoleEnum::INSTALLER->value) || $user->hasRole(RoleEnum::PAYMENT_COORDINATOR->value)) {
      $status = [
        //OrderStatusEnum::RESCHEDULE->value,
        OrderStatusEnum::PLANNED->value,
        OrderStatusEnum::REPLANNED->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::ON_HOLD->value,
        OrderStatusEnum::EXECUTION->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
        OrderStatusEnum::CANCELED->value,
      ];
    $statusmodal = [  
        OrderStatusEnum::REPLANNED->value,
        OrderStatusEnum::SUPERVISION->value,
        OrderStatusEnum::INSPECTION->value,
        OrderStatusEnum::FINISH->value,
        OrderStatusEnum::SERVICE->value,
        OrderStatusEnum::FINAL_INSPECTION->value,
        OrderStatusEnum::FINAL_COLLECT->value,
        OrderStatusEnum::COMPLETE->value,
        OrderStatusEnum::MATERIALS_RECEIVED->value,
        OrderStatusEnum::CANCELED->value,
      ];

      $legend = [
        [
          'color' => StatusColorEnum::RESCHEDULE->value,
          'label' => 'RESCHEDULE'
        ],
        [
          'color' => StatusColorEnum::CONFIRMED_INSTALLATION->value,
          'label' => 'CONFIRMED'
        ],
        [
          'color' => StatusColorEnum::EXECUTION->value,
          'label' => 'EXECUTION'
        ],
        [
          'color' => StatusColorEnum::SUPERVISION->value,
          'label' => 'SUPERVISION'
        ],
        [
          'color' => StatusColorEnum::INSPECTION->value,
          'label' => 'INSPECTION'
        ],
        [
          'color' => StatusColorEnum::FINISH->value,
          'label' => 'FINISH'
        ],
        [
          'color' => StatusColorEnum::FINAL_INSPECTION->value,
          'label' => 'FINAL INSPECTION'
        ],
        [
          'color' => StatusColorEnum::FINAL_COLLECT->value,
          'label' => 'PENDING COLLECT'
        ],
        [
          'color' => StatusColorEnum::COMPLETE->value,
          'label' => 'COMPLETE'
        ],
         [
          'color' => StatusColorEnum::CANCELED->value,
          'label' => 'CANCELED'
        ],
      ];

      $services = [
        ServiceEnum::INSTALLATION_ONLY->value,
        //ServiceEnum::INSTALLATION->value,
        ServiceEnum::SERVICE->value,
      ];
    }

    


    return Inertia::render('Dashboard/Index', [
      'services' => $services,
      'status' => $status,
      'legend' => $legend,
      'statusmodal' => $statusmodal,
      'installation_teams' => InstallationTeam::with(['user', 'typeHousing'])
        ->whereHas('user', function ($query) {
          $query->where('status', 'ACTIVE');
        })
        ->get(),
      'supervisors' => User::role(RoleEnum::SUPERVISOR->value)
        ->where('status', 'ACTIVE')
        ->get(),
    ]);
  }




  public function getEvents($year, $month, $service, $status, $name = null)
  {
    $user = auth()->user();
    $canHideOnWeekends = $user->hasRole(RoleEnum::ACCOUNT_MANAGER->value)
      || $user->hasRole(RoleEnum::ADMIN->value)
      || $user->hasRole(RoleEnum::INSTALLER->value);
    $showOnHoldByStatusDate = $user->hasRole(RoleEnum::SERVICE_MANAGER->value)
      && !$user->hasRole(RoleEnum::ACCOUNT_MANAGER->value)
      && !$user->hasRole(RoleEnum::ADMIN->value);

    if (empty($name) || $name === 'all') {
      $name = null; // Deja en null si no se quiere filtrar por cliente
    }
    $showOnlyInstallation = $service === ServiceEnum::INSTALLATION_ONLY->value;
    $showOnlyDeliveries = $service === ServiceEnum::DELIVERY->value;
    $service_filter = $showOnlyInstallation
      ? [ServiceEnum::INSTALLATION->value, ServiceEnum::SERVICE->value]
      : $service;

    $currentPassingDate = Carbon::parse($year . '-' . $month . '-01');
    $previewMonth = $currentPassingDate->copy()->subMonth()->startOfMonth();
    $nextMonth = $currentPassingDate->copy()->addMonth()->endOfMonth();
    //dd($previewMonth , $nextMonth);

    $orders = Order::with([
        'permit',
        'phases.installationTeams.user',
        'phases.phaseProducts.orderProduct.typeOfProduct',
        'phases.supervisor',
        'orderStatus' => fn ($query) => $query
          ->where('status', OrderStatusEnum::ON_HOLD->value)
          ->latest('created_at')
          ->latest('id'),
      ])->calendarFilter(['service' => $service_filter, 'status' => $status, 'name' => $name])
      ->where(function ($query) use ($previewMonth, $nextMonth, $showOnHoldByStatusDate) {
        $query->where(function ($query) use ($previewMonth, $nextMonth) {
          $query->whereBetween('delivery_date', [$previewMonth, $nextMonth]);
        })->orWhere(function ($query) use ($previewMonth, $nextMonth) {
          $query->whereBetween('installation_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('installation_end_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('inspection_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('finish_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('service_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('final_inspection_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('pending_collect', [$previewMonth, $nextMonth])
            ->orWhereBetween('complete_date', [$previewMonth, $nextMonth]);
        })->when($showOnHoldByStatusDate, function ($query) use ($previewMonth, $nextMonth) {
          $query->orWhere(function ($query) use ($previewMonth, $nextMonth) {
            $query->where('status', OrderStatusEnum::ON_HOLD->value)
              ->whereHas('orderStatus', function ($query) use ($previewMonth, $nextMonth) {
                $query->where('status', OrderStatusEnum::ON_HOLD->value)
                  ->whereBetween('created_at', [$previewMonth, $nextMonth]);
              });
            });
        })->orWhereHas('phases', function ($query) use ($previewMonth, $nextMonth) {
          $query->whereBetween('delivery_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('installation_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('installation_end_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('inspection_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('finish_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('service_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('pending_collect', [$previewMonth, $nextMonth])
            ->orWhereBetween('final_inspection_date', [$previewMonth, $nextMonth])
            ->orWhereBetween('complete_date', [$previewMonth, $nextMonth]);
        });
      });

    /*$sql = $orders->toSql();
    $bindings = $orders->getBindings();
    // Reemplazar los placeholders "?" con los valores de bindings
    foreach ($bindings as $binding) {
      // Escapa las comillas para valores de texto
      $binding = is_string($binding) ? "'{$binding}'" : $binding;
      $sql = preg_replace('/\?/', $binding, $sql, 1); // Reemplaza solo el primer "?"
    }
    dd($sql);*/

    $events = [];
    $events1 = [];
    $customAbbreviations = [
      'Door' => 'D', // ID 1 -> 'abc'
      'Window' => 'W', // ID 2 -> 'xyz'
      'Storefront' => 'St', // ID 3 -> 'lmn'
      'Sidelite' => 'Sd', // ID 3 -> 'lmn'
      'Mullion' => 'M', // ID 3 -> 'lmn'
      // Agrega más según sea necesario
    ];
    foreach ($orders->get() as $order) {
      if ($order->install_by_phases) {
        foreach ($order->phases as $phase) {
          if (!$this->phaseHasAnyCalendarDate($phase)) {
            continue;
          }

          if ($user->hasRole(RoleEnum::INSTALLER->value)) {
            $isAssignedToPhase = $phase->installationTeams
              ->contains(fn ($team) => (int) ($team->user_id ?? 0) === (int) $user->id);
            if (! $isAssignedToPhase) {
              continue;
            }
          }

          if ($user->hasSupervisorOnlyAccess()) {
            $effectiveSupervisorId = $phase->supervisor_id ?: $order->supervisor_id;
            if ((int) $effectiveSupervisorId !== (int) $user->id) {
              continue;
            }
          }

          $phaseProducts = $phase->phaseProducts->map(function ($phaseProduct) use ($customAbbreviations) {
            $orderProduct = $phaseProduct->orderProduct;
            $productName = $orderProduct?->typeOfProduct?->name ?? 'Product';
            $shortName = $customAbbreviations[$productName] ?? strtolower(substr($productName, 0, 1));
            return ((float) $phaseProduct->qty) . $shortName;
          })->filter()->join(', ');

          $phaseTitle = '#' . $order->order_number . ' - ' . $order->name . ' - ' . $phase->name . (!empty($order->city) ? ' - ' . $order->city : '');
          $phaseTooltip = $phaseProducts !== '' ? 'Products: ' . $phaseProducts : 'Products: Not assigned';
          $phaseExtra = [
            'order_phase_id' => $phase->id,
            'phase_name' => $phase->name,
            'phase_status' => $phase->status,
          ];

          if (!$showOnlyInstallation && $phase->delivery_date) {
            $events[] = $this->createEvent(
              $order->id,
              $phaseTitle,
              $phaseTooltip,
              $this->formatCalendarDate($phase->delivery_date),
              $this->formatCalendarDate($phase->delivery_date),
              $this->getColorByStatus($phase->status, ServiceEnum::DELIVERY->value),
              ServiceEnum::DELIVERY->value,
              false,
              $phaseExtra
            );
          }

          [$phaseStartDate, $phaseEndDate, $phaseColor] = $this->phaseInstallationCalendarRange($phase, $order, $user);
          if (!$showOnlyDeliveries && $phaseStartDate) {
            $events[] = $this->createEvent(
              $order->id,
              $phaseTitle,
              $phaseTooltip,
              $phaseStartDate,
              $phaseEndDate,
              $phaseColor,
              ServiceEnum::INSTALLATION->value,
              false,
              $phaseExtra
            );
          }

          if (
            !$showOnlyDeliveries
            && $phase->status === OrderStatusEnum::FINISH->value
            && $phase->finish_date
            && ($user->hasRole(RoleEnum::ACCOUNT_MANAGER->value) || $user->hasRole(RoleEnum::ADMIN->value))
          ) {
            $events[] = $this->createEvent(
              $order->id,
              $phaseTitle,
              $phaseTooltip,
              $this->formatCalendarDate($phase->finish_date),
              $this->formatCalendarDate($phase->finish_date),
              $this->getColorByStatus($phase->status, $order->service, true),
              ServiceEnum::INSTALLATION->value,
              false,
              $phaseExtra
            );
          }
        }

        continue;
      }

      $productCounts = $order->orderProducts()
        ->select('type_of_product_id', DB::raw('SUM(qty) as total'))
        ->groupBy('type_of_product_id')
        ->with('typeOfProduct') // Carga el tipo de producto para obtener el nombre
        ->get();

      // Formatear los datos de productos en el formato deseado
      $productDetails = $productCounts->map(function ($item) use ($customAbbreviations) {
        $productName = $item->typeOfProduct->name;
        $shortName = $customAbbreviations[$productName] ?? strtolower(substr($productName, 0, 1)); // Primera letra del tipo de producto
        return $item->total . $shortName;
      })->join(', ');

      $isVip = optional($order->client)->vip_clients ?? false;
      $serviceLabel = $isVip ? 'VIP' : '';
      $onHoldStatus = $showOnHoldByStatusDate && $order->status === OrderStatusEnum::ON_HOLD->value
        ? $order->orderStatus->first()
        : null;

      if ($onHoldStatus) {
        $onHoldDate = Carbon::parse($onHoldStatus->created_at)->format('Y-m-d');
        $events[] = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name .  ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
          'Products: ' . $productDetails,
          $onHoldDate,
          $onHoldDate,
          StatusColorEnum::ON_HOLD->value,
          $order->service
        );

        continue;
      }

      if ($order->service === ServiceEnum::DELIVERY->value || $order->service === ServiceEnum::PICKUP->value) {
        $startDate = $order->delivery_date;
        $endDate = $order->delivery_date;
        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name .  ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
          // $this->getEventPopover($order->status, $order->service),
          'Products: ' . $productDetails,
          $startDate,
          $endDate,
          $this->getColorByStatus($order->status, $order->service),
          $order->service
        );

        $events[] = $event;
      }

       

      if ($order->service === ServiceEnum::INSTALLATION->value || $order->service === ServiceEnum::SERVICE->value) {
        /*if($order->status === OrderStatusEnum::INSPECTION->value) {
          $color = StatusColorEnum::PLANNED->value;
          $startDate = $order->inspection_date;
          $endDate = $order->inspection_date;
          $event = $this->createEvent(
            $order->id,
            '#' . $order->order_number . ' - ' . $order->name .  ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
            // $this->getEventPopover($order->status, $order->service),
            'Products: ' . $productDetails,
            $startDate,
            $endDate,
            $color,
            $order->service
          );
          $events[] = $event;
        }*/

        if (!$showOnlyInstallation) {
           /* if($order->status === OrderStatusEnum::INSPECTION->value) {
              $startDeliveryDate = $order->inspection_date;
              $endDeliveryDate = $order->inspection_date;
              $color = StatusColorEnum::PLANNED->value;
            }else{*/
            $startDeliveryDate = $order->delivery_date;
            $endDeliveryDate = $order->delivery_date;
            $color= $this->getColorByStatus($order->status, $order->service);
            //}

          $event = $this->createEvent(
            $order->id,
            '#' . $order->order_number . ' - ' . $order->name . ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
            // $this->getEventPopover($order->status, $order->service),
            'Products: ' . $productDetails,
            $startDeliveryDate,
            $endDeliveryDate,
            $color,
            ServiceEnum::DELIVERY->value
          );
          $events[] = $event;
        }

        if (!$showOnlyDeliveries) {
           
          if(!( $user->hasRole(RoleEnum::ACCOUNT_MANAGER->value) || $user->hasRole(RoleEnum::ADMIN->value))){
            //dd ($order->status);


          if($order->status === OrderStatusEnum::INSPECTION->value) {
            $startInstallationDate = $order->inspection_date;
            $endInstallationDate = $order->inspection_date;
            $color = StatusColorEnum::INSPECTION->value;
          } else if ($order->status === OrderStatusEnum::FINISH->value){
            $startInstallationDate = $order->finish_date;
            $endInstallationDate = $order->finish_date;
            $color = StatusColorEnum::FINISH->value;
          } else if ($order->status === OrderStatusEnum::SERVICE->value){
            $startInstallationDate = $order->service_date;
            $endInstallationDate = $order->service_date;
            $color = StatusColorEnum::SERVICE->value;
          }
          else if($order->status === OrderStatusEnum::FINAL_COLLECT->value){
            $startInstallationDate = $order->pending_collect;
            $endInstallationDate =$order->pending_collect;
            $color = $this->getColorByStatus($order->status, $order->service, true);
          }
          else if ($order->status === OrderStatusEnum::FINAL_INSPECTION->value){
            $startInstallationDate = $order->final_inspection_date;
            $endInstallationDate = $order->final_inspection_date;
            $color = StatusColorEnum::FINAL_INSPECTION->value;
          }
          else if($order->status === OrderStatusEnum::COMPLETE->value){
              $startInstallationDate = $order->complete_date;
              $endInstallationDate =$order->complete_date;
              $color = $this->getColorByStatus($order->status, $order->service, true);
            }
            else{
              $startInstallationDate = $order->installation_date;
              $endInstallationDate = $order->installation_end_date;
              $color = $this->getColorByStatus($order->status, $order->service, true);
              }
          }

          else{
          $startInstallationDate = $order->installation_date;
          $endInstallationDate = $order->installation_end_date;
          $color = $this->getColorByStatus($order->status, $order->service, true);
          
        }
          $startInstallationDateCarbon = Carbon::parse($startInstallationDate);
          $endInstallationDateCarbon = Carbon::parse($endInstallationDate);
          $actualDate = Carbon::now();
          
          
          if ($actualDate->diffInDays($startInstallationDateCarbon) <= 7 && $order->permit != null && $order->permit->pick_up_permit == '') {
            $color = StatusColorEnum::DELAY_PERMITS->value;
          }

          if ($order->hide_on_weekends && $canHideOnWeekends) {
            $blockStart = null;
            while ($startInstallationDateCarbon <= $endInstallationDateCarbon) {
              $dayOfWeek = $startInstallationDateCarbon->format('N'); // 1 = Monday, 7 = Sunday
      
              if ($dayOfWeek < 6) { // If it's a workday
                  if ($blockStart === null) {
                      $blockStart = clone $startInstallationDateCarbon; // Start a new block
                  }
              } else { // If it's Saturday or Sunday, close the current block
                  if ($blockStart !== null) {
                      $event = $this->createEvent(
                          $order->id,
                          '#' . $order->order_number . ' - ' . $order->name . ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
                          'Products: ' . $productDetails,
                          $blockStart->format('Y-m-d'),
                         $startInstallationDateCarbon->modify('-1 day')->format('Y-m-d'),
                          $this->getColorByStatus($order->status, ServiceEnum::INSTALLATION->value, true),
                          ServiceEnum::INSTALLATION->value
                          
                      );
                      $events[] = $event;
                      $blockStart = null;
                  }
              }
           
              // If it's the last day and a workday, close the block
              if ($startInstallationDateCarbon == $endInstallationDateCarbon && $dayOfWeek < 6 && $blockStart !== null) {
                  $event = $this->createEvent(
                      $order->id,
                      '#' . $order->order_number . ' - ' . $order->name . ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
                      'Products: ' . $productDetails,
                      $blockStart->format('Y-m-d'),
                       $startInstallationDateCarbon->format('Y-m-d'),
                      $this->getColorByStatus($order->status, ServiceEnum::INSTALLATION->value, true),
                      ServiceEnum::INSTALLATION->value
                  );
                
                  $events[] = $event;
              }
      
              // Move to the next day
              $startInstallationDateCarbon->modify('+1 day');
            }
          } else {
            $event = $this->createEvent(
              $order->id,
              '#' . $order->order_number . ' - ' . $order->name . ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
              'Products: ' . $productDetails,
              $startInstallationDate,
              $endInstallationDate,
              $color,
              $order->service,
              
            ); 
            $events[] = $event;

            if($order->status === OrderStatusEnum::FINISH->value && ( $user->hasRole(RoleEnum::ACCOUNT_MANAGER->value) || $user->hasRole(RoleEnum::ADMIN->value))) {
              $startInstallationDate = $order->finish_date;
              $endInstallationDate = $order->finish_date;
              $event = $this->createEvent(
                $order->id,
                '#' . $order->order_number . ' - ' . $order->name . ($serviceLabel ? ' (' . $serviceLabel . ')' : '') . (!empty($order->city) ? ' - ' . $order->city : ''),
                'Products: ' . $productDetails,
                $startInstallationDate,
                $endInstallationDate,
                $this->getColorByStatus($order->status, $order->service, true),
                $order->service,
              );
              $events[] = $event;
            }
          }
        }
      }
    }

    return response()
      ->json($events);
  }

  public function updateEvent(Request $request, $id)
  {
    if ($request->filled('order_phase_id')) {
      $phase = OrderPhase::findOrFail((int) $request->order_phase_id);
      app(OrderPhaseManager::class)->updateCalendarPhase($phase, $request);

      return response()->json($phase->fresh(['order', 'installationTeams.user', 'supervisor']));
    }

    $order = Order::find($id);
    if (in_array($request->type_of_event, [
      ServiceEnum::INSTALLATION->value,
      ServiceEnum::SERVICE->value,
    ], true)) {
      $order->installation_date = $request->start;
      $order->installation_end_date = $request->end;
    } else {
      $order->delivery_date = $request->start;
    }

    $order->save();
    return response()
      ->json($order);
  }

  public function getEvent(Order $order)
  {
    if ($this->isRestrictedOwner(auth()->user()) && !$order->isAccessibleToOwner(auth()->user())) {
      abort(403, 'You are not authorized to access this order.');
    }

    $order->load([
      'client',
      'typeOfWork',
      'typeOfHousing',
      'user',
      'attachments',
      'attachmentRoleTargets',
      'owners',
      'orderProducts.orderProductExtraWorks',
      'installationTeams.user',
      'supervisor',
      'travelCost',
      'durationOfWork',
      'orderProducts.orderProductExtraWorks',
      'orderColors',
      'phases.installationTeams.user',
      'phases.phaseProducts.orderProduct.typeOfProduct',
      'phases.logs.user',
      'phases.supervisor',
    ]);

    $attachmentRoleTargetsByRole = $order->attachmentRoleTargets
      ->groupBy('role')
      ->map(function ($items) {
        return $items->pluck('attachment_id')
          ->map(fn ($id) => (int) $id)
          ->unique()
          ->values();
      })
      ->toArray();

    return response()
      ->json(array_merge($order->toArray(), [
        'attachment_role_targets_by_role' => $attachmentRoleTargetsByRole,
        'replanned_reasons' => $this->currentReplannedReasons($order),
      ]));
  }

  private function currentReplannedReasons(Order $order): array
  {
    if ($order->status !== OrderStatusEnum::REPLANNED->value) {
      return [];
    }

    $statusRecord = $order->orderStatus()
      ->where('status', OrderStatusEnum::REPLANNED->value)
      ->latest('id')
      ->first();

    return is_array($statusRecord?->replanned_reasons) ? $statusRecord->replanned_reasons : [];
  }

  private function phaseInstallationCalendarRange(OrderPhase $phase, Order $order, User $user): array
  {
    $startDate = $phase->installation_date;
    $endDate = $phase->installation_end_date ?: $phase->installation_date;
    $color = $this->getColorByStatus($phase->status, $order->service, true);

    if (!($user->hasRole(RoleEnum::ACCOUNT_MANAGER->value) || $user->hasRole(RoleEnum::ADMIN->value))) {
      if ($phase->status === OrderStatusEnum::INSPECTION->value && $phase->inspection_date) {
        $startDate = $phase->inspection_date;
        $endDate = $phase->inspection_date;
        $color = StatusColorEnum::INSPECTION->value;
      } else if ($phase->status === OrderStatusEnum::FINISH->value && $phase->finish_date) {
        $startDate = $phase->finish_date;
        $endDate = $phase->finish_date;
        $color = StatusColorEnum::FINISH->value;
      } else if ($phase->status === OrderStatusEnum::SERVICE->value && $phase->service_date) {
        $startDate = $phase->service_date;
        $endDate = $phase->service_date;
        $color = StatusColorEnum::SERVICE->value;
      } else if ($phase->status === OrderStatusEnum::FINAL_COLLECT->value && $phase->pending_collect) {
        $startDate = $phase->pending_collect;
        $endDate = $phase->pending_collect;
        $color = $this->getColorByStatus($phase->status, $order->service, true);
      } else if ($phase->status === OrderStatusEnum::FINAL_INSPECTION->value && $phase->final_inspection_date) {
        $startDate = $phase->final_inspection_date;
        $endDate = $phase->final_inspection_date;
        $color = StatusColorEnum::FINAL_INSPECTION->value;
      } else if ($phase->status === OrderStatusEnum::COMPLETE->value && $phase->complete_date) {
        $startDate = $phase->complete_date;
        $endDate = $phase->complete_date;
        $color = $this->getColorByStatus($phase->status, $order->service, true);
      }
    }

    return [
      $this->formatCalendarDate($startDate),
      $this->formatCalendarDate($endDate ?: $startDate),
      $color,
    ];
  }

  private function phaseHasAnyCalendarDate(OrderPhase $phase): bool
  {
    return (bool) (
      $phase->delivery_date
      || $phase->installation_date
      || $phase->inspection_date
      || $phase->finish_date
      || $phase->service_date
      || $phase->pending_collect
      || $phase->final_inspection_date
      || $phase->complete_date
    );
  }

  private function formatCalendarDate(mixed $date): ?string
  {
    return $date ? Carbon::parse($date)->format('Y-m-d') : null;
  }

  private function isRestrictedOwner(?User $user): bool
  {
    if (!$user) {
      return false;
    }

    return $user->hasRole(RoleEnum::OWNER->value) && !$user->hasAnyRole([
      RoleEnum::ADMIN->value,
      RoleEnum::ACCOUNT_MANAGER->value,
      RoleEnum::OWNER_ADMIN->value,
      RoleEnum::FRONTDESK_ADMIN->value,
      'FRONTDESK_ADMIN',
    ]);
  }

  public function getPaymentList(Order $order)
  {
    $order->load([
      'client',
      'typeOfWork',
      'typeOfHousing',
      'user',
      'attachments',
      'owners',
      'orderProducts.orderProductExtraWorks',
      'orderProducts.productConfig.productCosts',
      'orderProducts.productCategory',
      'orderProducts.typeOfWork',
      'installationTeams.user',
      'supervisor',
      'travelCost',
      'durationOfWork',
      'orderColors',
    ]);

    $pdf = Pdf::loadView('pdf.payment-list', ['order' => $order]);
    $pdfName = 'payment-list-' . $order->order_number . '.pdf';
    return $pdf->stream($pdfName);
  }

  public function getSupervisorList(Order $order)
  {
    $order->load([
      'client',
      'typeOfWork',
      'typeOfHousing',
      'user',
      'attachments',
      'owners',
      'orderProducts.orderProductExtraWorks',
      'installationTeams.user',
      'supervisor',
      'travelCost',
      'durationOfWork',
      'orderColors',
    ]);

    $pdf = Pdf::loadView('pdf.supervisor-list', ['order' => $order]);
    $pdfName = 'supervisor-list-' . $order->order_number . '.pdf';
    return $pdf->stream($pdfName);
  }

  public function whatsapp()
  {
    $parameters = [
      '1' => '123456',
    ];
    $this->sendWhatsAppMessage('+12397632059', $parameters);
    //$order = Order::find(48);
    //Mail::to('carlos@reylosglass.com')->send(new DeliveryConfirmed($order));
    echo 'whatsapp message';
  }
}
