<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallationPayment extends Model
{
  use HasFactory, SoftDeletes;


          protected $fillable = [
            'installer_payment',
            'order_id',
            'percentage_payment',
            'payment_date',
            'installation_team_id',
            'extra_work',
            'extra_discount',
            'other_cost_installer',
            'biweekly_id',
            'payment_status',
        ];

        protected $dispatchesEvents = [
          'created' => \App\Events\PaymentCreated::class,
          'updated' => \App\Events\PaymentCreated::class,
        ];

        protected $dates = [
            'payment_date',
        
      ];


        public function order()
        {
            return $this->belongsTo(Order::class);
        }

        public function installationTeam()
        {
            return $this->belongsTo(InstallationTeam::class);
        }

        public function biweekly()
        {
            return $this->belongsTo(Biweekly::class);
        }

      


}
