<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoryPendingPayment extends Model
{
    use HasFactory, SoftDeletes;
    
    //protected $table = 'permits_snapshots';
    /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'order_id',
    'installation_team_id',
    'biweekly_id',
    'data',
  ];

  protected $casts = [
    'data' => 'array'
  ];

  
  /**
   * Get the client that owns the order.
   */
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
