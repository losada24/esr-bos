<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentExtraField extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'responsible_extra_work',
      'order_id',
      'notes',
      'installer_payment_status',
      'installation_team_id',
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
