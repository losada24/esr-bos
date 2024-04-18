<?php

namespace Database\Seeders;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Models\Email;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailsOnOrderStatusChange extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Monica y Lezcano reciban el correo cuando la orden está en Producción completada y Lista para pick up o delivery
        $monica_email = 'monica@reylosglass.com';
        $lezcano_email = 'lezcano@reylosglass.com';
        $merged_emails = ',' . $monica_email . ',' . $lezcano_email;

        $adminUsers = User::whereHas('roles', function($q) {
          $q->where('name', RoleEnum::$ADMIN);
        })->get();

        $accountManager = User::whereHas('roles', function($q) {
          $q->where('name', RoleEnum::$ACCOUNT_MANAGER);
        })->get();
        
        $productionUsers = User::whereHas('roles', function($q) {
          $q->where('name', RoleEnum::$PRODUCTION);
        })->get();

        $accountingUsers = User::whereHas('roles', function($q) {
          $q->where('name', RoleEnum::$ACCOUNTING);
        })->get();

        $shippingUsers = User::whereHas('roles', function($q) {
          $q->where('name', RoleEnum::$SHIPPING);
        })->get();

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$productionUsers,
            ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$PRODUCTION
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$productionUsers,
          ]),
          'status' => OrderStatusEnum::$SCHEDULED_PRODUCTION
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$productionUsers,
          ]),
          'status' => OrderStatusEnum::$PRODUCTION_IN_PROGRESS
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$productionUsers,
            ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$PRODUCTION_COMPLETED
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$productionUsers,
            ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$shippingUsers,
            ...$accountingUsers,
          ]) . $merged_emails,
          'status' => OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$shippingUsers,
            ...$accountingUsers,
          ]) . $merged_emails,
          'status' => OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$shippingUsers,
            ...$accountingUsers,
          ]) . $merged_emails,
          'status' => OrderStatusEnum::$READY_FOR_DELIVERY
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$shippingUsers,
            ...$accountingUsers,
          ]) . $merged_emails,
          'status' => OrderStatusEnum::$READY_FOR_PICKUP
        ]);

        Email::create([
          'recipients' => $this->getEmails([
              ...$adminUsers,
              ...$accountManager,
              ...$shippingUsers,
              ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$PARTIAL_DELIVERED
        ]);

        Email::create([
          'recipients' => $this->getEmails([
              ...$adminUsers,
              ...$accountManager,
              ...$shippingUsers,
              ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$PARTIAL_PICKED_UP
        ]);

        Email::create([
          'recipients' => $this->getEmails([
              ...$adminUsers,
              ...$accountManager,
              ...$shippingUsers,
              ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$DELIVERED
        ]);

        Email::create([
          'recipients' => $this->getEmails([
              ...$adminUsers,
              ...$accountManager,
              ...$shippingUsers,
              ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$PICKED_UP
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$ORDER_COMPLETED
        ]);

        Email::create([
          'recipients' => $this->getEmails([
            ...$adminUsers,
            ...$accountManager,
            ...$accountingUsers,
          ]),
          'status' => OrderStatusEnum::$ACCOUNTING
        ]);
    }

    public function getEmails($emails) {
      $result = [];
      foreach ($emails as $email) {
        $result[] = $email->email;
      }

      return implode(',', $result);
    }
}
