<?php

namespace App\Http\Controllers;

use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusColorEnum;
use App\Models\Order;
use App\Traits\OrderStatus;
use App\Traits\Twilio;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DashboardController extends Controller
{
  
  use OrderStatus, Twilio;

  public function index(Request $request): Response
  {
      return Inertia::render('Dashboard/Index', [
        'services' => [
          ServiceEnum::INSTALLATION->value,
          ServiceEnum::DELIVERY->value,
          ServiceEnum::PICKUP->value,
          ServiceEnum::INSTALLATION_ONLY->value,
        ],
        'status' => [
          OrderStatusEnum::PLANNED->value,
          OrderStatusEnum::CONFIRMED->value,
          OrderStatusEnum::DELIVERY_CONFIRMED->value,
        ],
        'legend' => [
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
        ]
      ]);
  }

  public function getEvents($year, $month, $service, $status) {
    $showOnlyInstallation = $service === ServiceEnum::INSTALLATION_ONLY->value;
    $service_filter = $service === ServiceEnum::INSTALLATION_ONLY->value ? ServiceEnum::INSTALLATION->value : $service;

    $currentPassingDate = Carbon::parse($year . '-' . $month . '-01');
    $previewMonth = $currentPassingDate->copy()->subMonth()->startOfMonth();
    $nextMonth = $currentPassingDate->copy()->addMonth()->endOfMonth();

    $orders = Order::with(['permit'])->calendarFilter(['service' => $service_filter, 'status' => $status])
      ->where(function ($query) use ($previewMonth, $nextMonth) {
        $query->where(function($query) use ($previewMonth, $nextMonth) {
            $query->whereBetween('delivery_date', [$previewMonth, $nextMonth]);
        })->orWhere(function($query) use ($previewMonth, $nextMonth) {
            $query->whereBetween('installation_date', [$previewMonth, $nextMonth])
              ->orWhereBetween('installation_end_date', [$previewMonth, $nextMonth]);
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
    foreach ($orders->get() as $order) {
      if ($order->service === ServiceEnum::DELIVERY->value || $order->service === ServiceEnum::PICKUP->value) {
        $startDate = $order->delivery_date;
        $endDate = $order->delivery_date;
        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name . ' (' . $order->service . ')',
          $this->getEventPopover($order->status, $order->service),
          $startDate,
          $endDate,
          $this->getColorByStatus($order->status, $order->service),
          $order->service
        );

        $events[] = $event;
        
      } else if ($order->service === ServiceEnum::INSTALLATION->value) {

        if (!$showOnlyInstallation) {
          $startDeliveryDate = $order->delivery_date;
          $endDeliveryDate = $order->delivery_date;
          $event = $this->createEvent(
            $order->id,
            '#' . $order->order_number . ' - ' . $order->name . ' (DELIVERY)',
            $this->getEventPopover($order->status, $order->service),
            $startDeliveryDate,
            $endDeliveryDate,
            $this->getColorByStatus($order->status, $order->service),
            ServiceEnum::DELIVERY->value
          );
          $events[] = $event;
        }

        $startInstallationDate = $order->installation_date;
        $endInstallationDate = $order->installation_end_date;
        $startInstallationDateCarbon = Carbon::parse($startInstallationDate);
        $actualDate = Carbon::now();
        $color = $this->getColorByStatus($order->status, $order->service, true);
        if ($actualDate->diffInDays($startInstallationDateCarbon) <= 7 && $order->permit != null && $order->permit->pick_up_permit == '') {
          $color = StatusColorEnum::DELAY_PERMITS->value;
        }

        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name . ' (INSTALLATION)',
          $this->getEventPopover($order->status, $order->service, true),
          $startInstallationDate,
          $endInstallationDate,
          $color,
          $order->service
        );
        $events[] = $event;
      }
      
      
    }
    return response()
      ->json($events);
  }

  public function updateEvent(Request $request, $id) {
    $order = Order::find($id);
    if ($request->type_of_event === ServiceEnum::INSTALLATION->value) {
      $order->installation_date = $request->start;
      $order->installation_end_date = $request->end;
    } else {
      $order->delivery_date = $request->start;
    }

    $order->save();
    return response()
      ->json($order);
  }

  public function getEvent(Order $order) {
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
    ]);
  
    return response()
      ->json($order);
  }

  public function getPaymentList(Order $order) {
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
    ]);
  
    $pdf = Pdf::loadView('pdf.payment-list', ['order' => $order]);
    $pdfName = 'payment-list-' . $order->order_number . '.pdf';
    return $pdf->download($pdfName);
  }

  public function whatsapp() {
    $this->sendWhatsAppMessage('+12397632059', 'Primer mensaje para Katy');
    echo 'whatsapp message';
  }
}
