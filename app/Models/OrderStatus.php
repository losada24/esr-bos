<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderStatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_status';

    protected $fillable = [
        'status',
        'order_id',
        'notes',
        'user_id',
        'start_date',
        'end_date',
        'pickup_date',

    ];
    protected $dates = [
      'start_date',
      'end_date',
      'pickup_date',
  ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
