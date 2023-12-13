<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderSnapshots extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_snapshots';

    protected $fillable = [
        'user_id',
        'order_id',
        'status',
        'order_details'
    ];

    protected $casts = [
      'order_details' => 'array'
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
