<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallationPayment extends Model
{
  use HasFactory, SoftDeletes;
          protected $fillable = [
            'installer_paymemt',
            'order_id',
            'percentage_payment',
            'payment_date',
            'installation_team_id',
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

}
