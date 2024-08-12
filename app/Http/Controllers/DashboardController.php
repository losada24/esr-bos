<?php

namespace App\Http\Controllers;

use App\Enum\Service;
use App\Models\Order;
use App\Traits\OrderStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
  
  use OrderStatus;

  public function index(Request $request): Response
  {
      return Inertia::render('Dashboard/Index', []);
  }

  public function getEvents($year, $month) {
    $orders = Order::calendarFilter()
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
      })
      ->get();
    $events = [];
    foreach ($orders as $order) {
      if ($order->service === Service::DELIVERY->value || $order->service === Service::PICKUP->value) {
        $startDate = $order->delivery_date;
        $endDate = $order->delivery_date;
        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name . ' (' . $order->service . ')',
          $order->job_address,
          $startDate,
          $endDate,
          $this->getColorByStatus($order->status),
          $order->service
        );

        $events[] = $event;
        
      } else if ($order->service === Service::INSTALLATION->value) {
        $startDeliveryDate = $order->delivery_date;
        $endDeliveryDate = $order->delivery_date;
        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name . ' (' . $order->service . ')',
          $order->job_address,
          $startDeliveryDate,
          $endDeliveryDate,
          $this->getColorByStatus($order->status),
          Service::DELIVERY->value
        );

        $events[] = $event;
        $startInstallationDate = $order->installation_date;
        $endInstallationDate = $order->installation_end_date;
        $event = $this->createEvent(
          $order->id,
          '#' . $order->order_number . ' - ' . $order->name . ' (' . $order->service . ')',
          $order->job_address,
          $startInstallationDate,
          $endInstallationDate,
          $this->getColorByStatus($order->status),
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
    if ($request->type_of_event === Service::INSTALLATION->value) {
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
}
