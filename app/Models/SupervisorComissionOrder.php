<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupervisorComissionOrder extends Model
{
    use HasFactory , SoftDeletes;
    protected $fillable = [
      'percentage',
      'amount',
      'order_id',
      'tier',
      'tier_base_amount',
  ];

  public function orderComision(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }
}
