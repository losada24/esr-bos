<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\Service;
use App\Mail\DeliveryConfirmed;
use App\Mail\EstimateDeliveryInstallationDate;
use App\Mail\EstimateMaterialArrivalDate;
use App\Mail\InstallationDateConfirmation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

trait OrderEmails {

  public function sendEmail(Order $order) {
    if ($order->status === OrderStatusEnum::PLANNED->value) {
      $users = [];
      foreach ($order->owners as $owner) {
        $users[] = $owner->email;
      }

      $users[] = $order->client->email;
      $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
      $users = array_merge($users, $accountManager->pluck('email')->toArray());

      if ($order->service === Service::INSTALLATION->value) {
        foreach ($users as $user) {
          Mail::to($user)->send(new EstimateDeliveryInstallationDate($order));
        }
      } else if ($order->service === Service::DELIVERY->value || $order->service === Service::PICKUP->value) {
        foreach ($users as $user) {
          Mail::to($user)->send(new EstimateMaterialArrivalDate($order));
        }
      }
    } else if ($order->status === OrderStatusEnum::DELIVERY_CONFIRMED->value) {
      $users = [];
      $users[] = $order->client->email;
      $users[] = $order->supervisor->email;
      $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
      $users = array_merge($users, $accountManager->pluck('email')->toArray());
      $usersByRoleManager = User::role([RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::SERVICE_MANAGER->value])->get();
      $users = array_merge($users, $usersByRoleManager->pluck('email')->toArray());
      foreach ($users as $user) {
        Mail::to($user)->send(new DeliveryConfirmed($order));
      }
    } else if ($order->status === OrderStatusEnum::CONFIRMED->value) {
      $users = $order->owners->pluck('email')->toArray();
      $users[] = $order->client->email;
      foreach ($users as $user) {
        // Mail::to($user)->send(new InstallationDateConfirmation($order));
      }

      $users = [];
      $users[] = $order->supervisor->email;
      $serviceManager = User::role([RoleEnum::SERVICE_MANAGER->value])->get();
      $users = array_merge($users, $serviceManager->pluck('email')->toArray());
      // dd($users);
      foreach ($users as $user) {
        //Mail::to($user)->send(new InstallationDateConfirmation($order, true, true));
      }

      $users = [];
      $users = $order->installationTeams->pluck('user.email')->toArray();
      $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
      $users = array_merge($users, $accountManager->pluck('email')->toArray());
      foreach ($users as $user) {
        Mail::to($user)->send(new InstallationDateConfirmation($order, true, true, true));
      }
    }
  }
}
