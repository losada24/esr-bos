<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Biweekly extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'biweeklys';

          protected $fillable = [
            'start_biweekly_period',
            'end_biweekly_period',
            'payment_method',
            'installation_team_id',
        ];

        protected $dates = [
            'start_biweekly_period',
            'end_biweekly_period',
        
      ];

        public function installationTeam()
        {
            return $this->belongsTo(InstallationTeam::class);
        }
        public function installationPayments()
        {
            return $this->hasMany(InstallationPayment::class);
        }
}
