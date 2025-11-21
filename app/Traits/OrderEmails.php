<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\DeliveryConfirmed;
use App\Mail\EmailAccounting;
use App\Mail\EstimateAppointmentScheduleSaleForm;
use App\Mail\EstimateDeliveryInstallationDate;
use App\Mail\EstimateMaterialArrivalDate;
use App\Mail\InstallationDateConfirmation;
use App\Mail\InstallationDateConfirmationClient;
use App\Mail\PendingAssigment;
use App\Models\Order;
use App\Models\User;

trait OrderEmails {

  public function sendEmail(Order $order) {
    if ($order->status === OrderStatusEnum::PLANNED->value) {
      $users = [];
      foreach ($order->owners as $owner) {
        $users[] = $owner->email;
      }
      if($order->do_not_send_email != 1){
        $users[] = $order->client->email;
      }
      $accountings = User::role([RoleEnum::ACCOUNTING->value])->get();
     
      //$accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
      //$users = array_merge($users, $accountManager->pluck('email')->toArray());

      if ($order->service === ServiceEnum::INSTALLATION->value || $order->service === ServiceEnum::SERVICE->value) {
        foreach ($users as $user) {
          // Mail::to($user)->send(new EstimateDeliveryInstallationDate($order));
          $estimateDeliveryInstallationDate = new EstimateDeliveryInstallationDate($order);
          SendGmailEmail::dispatch($user, $estimateDeliveryInstallationDate)->onQueue('emails');
        }
       
       /* foreach ($accountings->pluck('email')->toArray() as $user) {
          // Mail::to($user)->send(new EmailAccounting($order));
          $emailAccounting = new EmailAccounting($order);
          SendGmailEmail::dispatch($user, $emailAccounting)->onQueue('emails');
        }*/
      } else if ($order->service === ServiceEnum::DELIVERY->value || $order->service === ServiceEnum::PICKUP->value) {
        foreach ($users as $user) {
          // Mail::to($user)->send(new EstimateMaterialArrivalDate($order));
          $estimateMaterialArrivalDate = new EstimateMaterialArrivalDate($order);
          SendGmailEmail::dispatch($user, $estimateMaterialArrivalDate)->onQueue('emails');
        }
      }
    } else if ($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value) {
      if ($order->saleForm) {

            $ownerEmails = [];
          foreach ($order->owners as $owner) {
            $ownerEmails[] = $owner->email;
          }

        foreach ($ownerEmails as $email) {
          $mailable = new EstimateAppointmentScheduleSaleForm($order);
          SendGmailEmail::dispatch($email, $mailable)->onQueue('emails');
        }
      }
    } else if ($order->status === OrderStatusEnum::PENDING_ASSIGNMENT->value || $order->status === OrderStatusEnum::COMMERCIAL_ASSIGNMENT->value) {
      if ($order->saleForm) {
        $ownerAdminEmails = User::role([RoleEnum::OWNER_ADMIN->value])->pluck('email')->toArray();

        foreach ($ownerAdminEmails as $email) {
          $mailable = new PendingAssigment($order);
          SendGmailEmail::dispatch($email, $mailable)->onQueue('emails');
        }
      }
    }
    else if ($order->status === OrderStatusEnum::DELIVERY_CONFIRMED->value) {
      $users = [];
      if($order->do_not_send_email != 1){
        $users[] = $order->client->email;
      }
     
      $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
      $users = array_merge($users, $accountManager->pluck('email')->toArray());
      $usersByRoleManager = User::role([RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::SERVICE_MANAGER->value])->get();
      $users = array_merge($users, $usersByRoleManager->pluck('email')->toArray());
      foreach ($users as $user) {
        // Mail::to($user)->send(new DeliveryConfirmed($order));
        $deliveryConfirmed = new DeliveryConfirmed($order);
        SendGmailEmail::dispatch($user, $deliveryConfirmed)->onQueue('emails');
      }
    } else if ($order->status === OrderStatusEnum::CONFIRMED->value || $order->status === OrderStatusEnum::RESCHEDULE->value) {
      $users = [];
      if ($order->service === ServiceEnum::INSTALLATION->value || $order->service === ServiceEnum::SERVICE->value) {
        $owners = $order->owners->pluck('email')->toArray();
        foreach ($owners as $owner){
          // Mail::to($owner)->send(new InstallationDateConfirmationClient($order));
          $installationDateConfirmation = new InstallationDateConfirmationClient($order);
          SendGmailEmail::dispatch($owner, $installationDateConfirmation)->onQueue('emails');
        }

        if($order->do_not_send_email != 1){
          $users[] = $order->client->email;
        }
        
        foreach ($users as $user) {
          // Mail::to($user)->send(new InstallationDateConfirmationClient($order, true));
          $installationDateConfirmation = new InstallationDateConfirmationClient($order, true);
          SendGmailEmail::dispatch($user, $installationDateConfirmation)->onQueue('emails');
        }

        $users = [];
        $users[] = $order->supervisor->email;
        $serviceManager = User::role([RoleEnum::SERVICE_MANAGER->value])->get();
        $users = array_merge($users, $serviceManager->pluck('email')->toArray());
        foreach ($users as $user) {
          // Mail::to($user)->send(new InstallationDateConfirmation($order, true, true, false,true));
          $installationDateConfirmation = new InstallationDateConfirmation($order, true, true, false, true);
          SendGmailEmail::dispatch($user, $installationDateConfirmation)->onQueue('emails');
        }

        $users = [];
       
        //$installers = $order->loadMissing('installationTeams.user');
        //dd($order->installationTeams);
        foreach ($order->installationTeams as $installationTeam) {
          $users[] = $installationTeam->user->email;
        }
        //$users = $order->installationTeams->pluck('email')->toArray();
        $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
        $users = array_merge($users, $accountManager->pluck('email')->toArray());
        foreach ($users as $user) {
          // Mail::to($user)->send(new InstallationDateConfirmation($order, true, true, true));
          $installationDateConfirmation = new InstallationDateConfirmation($order, true, true, true);
          SendGmailEmail::dispatch($user, $installationDateConfirmation)->onQueue('emails');
        }
      } else if ($order->service === ServiceEnum::DELIVERY->value || $order->service === ServiceEnum::PICKUP->value) {
        $users = [];
        if($order->do_not_send_email != 1){
          $users[] = $order->client->email;
        }
        
        $users = array_merge($users,$order->owners->pluck('email')->toArray());
        $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
        $users = array_merge($users, $accountManager->pluck('email')->toArray());
        $usersByRoleManager = User::role([RoleEnum::WAREHOUSE_MANAGER->value, RoleEnum::SERVICE_MANAGER->value])->get();
        $users = array_merge($users, $usersByRoleManager->pluck('email')->toArray());
        foreach ($users as $user) {
          // Mail::to($user)->send(new DeliveryConfirmed($order));
          $deliveryConfirmed = new DeliveryConfirmed($order);
          SendGmailEmail::dispatch($user, $deliveryConfirmed)->onQueue('emails');
          //SendGmailEmail::dispatch('katiuska28@gmail.com', $deliveryConfirmed)->onQueue('emails');
        }
      }
    }
  }
}
