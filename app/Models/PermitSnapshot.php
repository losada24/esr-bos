<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermitSnapshot extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'permits_snapshots';
    /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'user_id',
    'permit_id',
    'data',
  ];

  protected $casts = [
    'data' => 'array'
  ];

  
  /**
   * Get the client that owns the order.
   */
  public function permit()
  {
      return $this->belongsTo(Permit::class);
  }

  /**
   * Get the product that owns the order.
   */
  public function user()
  {
      return $this->belongsTo(User::class);
  }
}
