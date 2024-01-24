<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'order_id',
      'system',
      'width',
      'height',
      'line_item_name',
      'frame_color',
      'qty',
      'markup',
      'glass_type',
      'glass_color',
      'low_e',
      'privacy',
      'extras',
      'user_id',
      'unit_price',
      'total_price',
      'dealer_unit_price',
      'dealer_total_price',
      'sub_dealer_unit_price',
      'sub_dealer_total_price',
      'customer_unit_price',
      'customer_total_price',
      'dealer_promotion_discount',
      'dealer_promotion_total_discount',
    ];

    protected $dispatchesEvents = [
      'created' => \App\Events\ProductCreated::class,
      'updated' => \App\Events\ProductCreated::class,
    ];

    
    protected $casts = [
      'extras' => 'array'
    ];

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where("name", $search);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
