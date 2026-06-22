<?php

namespace App\Traits;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Jobs\SendGmailEmail;
use App\Mail\DeliveryConfirmed;
use App\Mail\EmailAccounting;
use App\Mail\EstimateAppointmentScheduledClient;
use App\Mail\EstimateAppointmentScheduleSaleForm;
use App\Mail\EstimateDeliveryInstallationDate;
use App\Mail\EstimateMaterialArrivalDate;
use App\Mail\InstallationDateConfirmation;
use App\Mail\InstallationDateConfirmationClient;
use App\Mail\PendingAssigment;
use App\Mail\RequestReSchedule;
use App\Mail\RequestStandBy;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderClientEmailManager;

trait OrderEmails {

  public function sendEmail(Order $order, ?string $requestRescheduleNote = null) {
    $clientEmailManager = app(OrderClientEmailManager::class);

    if ($order->status === OrderStatusEnum::PLANNED->value) {
      $ownerEmails = $order->owners->pluck('email')->toArray();
      $clientEmail = $clientEmailManager->resolveRecipient($order);
      $accountings = User::role([RoleEnum::ACCOUNTING->value])->get();
     
      //$accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
      //$ownerEmails = array_merge($ownerEmails, $accountManager->pluck('email')->toArray());

      if ($order->service === ServiceEnum::INSTALLATION->value || $order->service === ServiceEnum::SERVICE->value) {
        foreach ($ownerEmails as $user) {
          // Mail::to($user)->send(new EstimateDeliveryInstallationDate($order));
          $estimateDeliveryInstallationDate = new EstimateDeliveryInstallationDate($order);
          SendGmailEmail::dispatch($user, $estimateDeliveryInstallationDate)->onQueue('emails');
        }
        if (!empty($clientEmail)) {
          $this->dispatchClientEmail($clientEmail, new EstimateDeliveryInstallationDate($order));
        }
       
       /* foreach ($accountings->pluck('email')->toArray() as $user) {
          // Mail::to($user)->send(new EmailAccounting($order));
          $emailAccounting = new EmailAccounting($order);
          SendGmailEmail::dispatch($user, $emailAccounting)->onQueue('emails');
        }*/
      } else if ($order->service === ServiceEnum::DELIVERY->value || $order->service === ServiceEnum::PICKUP->value) {
        foreach ($ownerEmails as $user) {
          // Mail::to($user)->send(new EstimateMaterialArrivalDate($order));
          $estimateMaterialArrivalDate = new EstimateMaterialArrivalDate($order);
          SendGmailEmail::dispatch($user, $estimateMaterialArrivalDate)->onQueue('emails');
        }
        if (!empty($clientEmail)) {
          $this->dispatchClientEmail($clientEmail, new EstimateMaterialArrivalDate($order));
        }
      }
    } else if ($order->status === OrderStatusEnum::ESTIMATE_APPT_SCHEDULE->value) {
      $ownerEmails = $order->owners
        ->pluck('email')
        ->filter(fn ($email) => is_string($email) && trim($email) !== '')
        ->map(fn ($email) => trim((string) $email))
        ->unique(fn ($email) => mb_strtolower($email))
        ->values();

      foreach ($ownerEmails as $email) {
        $mailable = new EstimateAppointmentScheduleSaleForm($order);
        SendGmailEmail::dispatch($email, $mailable)->onQueue('emails');
      }

      $clientEmail = trim((string) ($clientEmailManager->resolveRecipient($order) ?? ''));
      if ($clientEmail !== '' && !empty($order->schedule_appointment)) {
        $mailable = new EstimateAppointmentScheduledClient($order);
        $this->dispatchClientEmail($clientEmail, $mailable);
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
    else if ($order->status === OrderStatusEnum::NEW_CUSTOMER_REQUEST_STAND_BY->value) {
      
        $frontdeskAdminEmails = User::role([RoleEnum::FRONTDESK_ADMIN->value])->pluck('email')->toArray();

        foreach ($frontdeskAdminEmails as $email) {
          $mailable = new RequestStandBy($order);
          SendGmailEmail::dispatch($email, $mailable)->onQueue('emails');
        }
      
    }
    else if ($order->status === OrderStatusEnum::REQUEST_RE_SCHEDULE->value) {

        $recipientEmails = collect(array_merge(
          $order->owners->pluck('email')->toArray(),
          User::role([RoleEnum::OWNER_ADMIN->value])->pluck('email')->toArray(),
          User::role([RoleEnum::FRONTDESK_ADMIN->value])->pluck('email')->toArray()
        ))
          ->filter(fn ($email) => is_string($email) && trim($email) !== '')
          ->map(fn ($email) => trim((string) $email))
          ->unique(fn ($email) => mb_strtolower($email))
          ->values();

        foreach ($recipientEmails as $email) {
          $mailable = new RequestReSchedule($order, $requestRescheduleNote);
          SendGmailEmail::dispatch($email, $mailable)->onQueue('emails');
        }

    }
    else if ($order->status === OrderStatusEnum::DELIVERY_CONFIRMED->value) {
      $clientEmail = $clientEmailManager->resolveRecipient($order);
      if (!empty($clientEmail)) {
        $this->dispatchClientEmail($clientEmail, new DeliveryConfirmed($order));
      }
     
      $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->get();
      $users = $accountManager->pluck('email')->toArray();
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

        $clientEmail = $clientEmailManager->resolveRecipient($order);
        if (!empty($clientEmail)) {
          // Mail::to($user)->send(new InstallationDateConfirmationClient($order, true));
          $installationDateConfirmation = new InstallationDateConfirmationClient($order, true);
          $this->dispatchClientEmail($clientEmail, $installationDateConfirmation);
        }

        $supervisorAttachmentIds = $this->selectedAttachmentIdsForRole($order, 'supervisor');
        $supervisorEmail = optional($order->supervisor)->email;
        if (!empty($supervisorEmail)) {
          // Mail::to($user)->send(new InstallationDateConfirmation($order, true, true, false,true));
          $installationDateConfirmation = new InstallationDateConfirmation($order, true, true, false, true, $supervisorAttachmentIds);
          SendGmailEmail::dispatch($supervisorEmail, $installationDateConfirmation)->onQueue('emails');
        }

        $serviceManagerAttachmentIds = $this->selectedAttachmentIdsForRole($order, 'service_manager');
        $serviceManager = User::role([RoleEnum::SERVICE_MANAGER->value])->pluck('email')->toArray();
        $serviceManager = array_values(array_unique(array_filter($serviceManager)));
        foreach ($serviceManager as $user) {
          $installationDateConfirmation = new InstallationDateConfirmation($order, true, true, false, true, $serviceManagerAttachmentIds);
          SendGmailEmail::dispatch($user, $installationDateConfirmation)->onQueue('emails');
        }

        $installerAttachmentIds = $this->selectedAttachmentIdsForRole($order, 'installer');
        $users = [];
        foreach ($order->installationTeams as $installationTeam) {
          $email = optional($installationTeam->user)->email;
          if (!empty($email)) {
            $users[] = $email;
          }
        }
        $users = array_values(array_unique(array_filter($users)));
        foreach ($users as $user) {
          $installationDateConfirmation = new InstallationDateConfirmation($order, true, true, true, false, $installerAttachmentIds);
          SendGmailEmail::dispatch($user, $installationDateConfirmation)->onQueue('emails');
        }

        $accountManagerAttachmentIds = $this->selectedAttachmentIdsForRole($order, 'account_manager');
        $accountManager = User::role([RoleEnum::ACCOUNT_MANAGER->value])->pluck('email')->toArray();
        $accountManager = array_values(array_unique(array_filter($accountManager)));
        foreach ($accountManager as $user) {
          $installationDateConfirmation = new InstallationDateConfirmation($order, true, true, true, false, $accountManagerAttachmentIds);
          SendGmailEmail::dispatch($user, $installationDateConfirmation)->onQueue('emails');
        }
      } else if ($order->service === ServiceEnum::DELIVERY->value || $order->service === ServiceEnum::PICKUP->value) {
        $clientEmail = $clientEmailManager->resolveRecipient($order);
        if (!empty($clientEmail)) {
          $this->dispatchClientEmail($clientEmail, new DeliveryConfirmed($order));
        }
        
        $users = $order->owners->pluck('email')->toArray();
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

  private function dispatchClientEmail(string $email, $mailable): void
  {
    SendGmailEmail::dispatch(
      $email,
      $mailable,
      allowInactiveUserRecipient: true
    )->onQueue('emails');
  }

  private function selectedAttachmentIdsForRole(Order $order, string $role): array
  {
    $order->loadMissing('attachmentRoleTargets');

    return $order->attachmentRoleTargets
      ->where('role', $role)
      ->pluck('attachment_id')
      ->map(fn ($id) => (int) $id)
      ->unique()
      ->values()
      ->all();
  }
}
