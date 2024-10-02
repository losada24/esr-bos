<?php

namespace App\Http\Controllers;

use App\Enum\OrderStatusEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusColorEnum;
use App\Models\Order;
use App\Traits\OrderStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
  
  use OrderStatus;

  public function index(Request $request): Response
  {
      return Inertia::render('Dashboard/Index', [
        'services' => [
          ServiceEnum::INSTALLATION->value,
          ServiceEnum::DELIVERY->value,
          ServiceEnum::PICKUP->value
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
        ]
      ]);
  }

  public function getEvents($year, $month, $service, $status) {
    $orders = Order::calendarFilter(['service' => $service, 'status' => $status])
      ->where(function ($query) use ($year, $month) {
        $query->where(function($query) use ($year, $month) {
          $query->whereYear('delivery_date', $year)
            ->whereMonth('delivery_date', $month);
        })->orWhere(function($query) use ($year, $month) {
            $query->whereYear('installation_date', $year)
              ->whereMonth('installation_date', $month)
              ->orWhereYear('installation_end_date', $year)
              ->whereMonth('installation_end_date', $month);
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
        $startDeliveryDate = $order->delivery_date;
        $endDeliveryDate = $order->delivery_date;
        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name . ' (' . $order->service . ')',
          $this->getEventPopover($order->status, $order->service),
          $startDeliveryDate,
          $endDeliveryDate,
          $this->getColorByStatus($order->status, $order->service),
          ServiceEnum::DELIVERY->value
        );

        $events[] = $event;
        $startInstallationDate = $order->installation_date;
        $endInstallationDate = $order->installation_end_date;
        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name . ' (' . $order->service . ')',
          $this->getEventPopover($order->status, $order->service, true),
          $startInstallationDate,
          $endInstallationDate,
          $this->getColorByStatus($order->status, $order->service, true),
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
}
