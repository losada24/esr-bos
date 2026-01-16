<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderSnapshot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_snapshots';

    protected $fillable = [
        'user_id',
        'order_id',
        'status',
        'snapshot_data'
    ];

    protected $casts = [
      'snapshot_data' => 'array'
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
