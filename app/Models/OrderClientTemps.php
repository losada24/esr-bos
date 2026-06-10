<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderClientTemps extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'order_id',
      'client_id',
    ];

    public function order()
    {
      return $this->belongsTo(Order::class);
    }

    public function client()
    {
      return $this->belongsTo(Client::class);
    }

}
