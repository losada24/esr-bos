<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class SaleForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'sale',
      'installation',
      'permit',
      'replacement',
      'new_construction',
      'financing',
      'screen',
      'design',
      'mountin',
      'bar',
      'shutter_hole',
      'floor_cutting',
      'interior_finish',
      'floor',
      'frame_color',
      'glass_color',
      'glass_type',
      'glass_coating',
      'hoa',
      'language',
      'door_quantity',
      'window_quantity',
      'order_id' // Foreign key to Referral model

    ];

    protected function casts(): array
    {
        return [
            'sale' => 'boolean',
            'installation' => 'boolean',
            'permit' => 'boolean',
            'replacement' => 'boolean',
            'new_construction' => 'boolean',
            'financing' => 'boolean',
            'screen' => 'boolean',
            'design' => 'boolean',
            'mountin' => 'boolean',
            'bar' => 'boolean',
            'shutter_hole' => 'boolean',
            'floor_cutting' => 'boolean',
            'interior_finish' => 'boolean',
            'hoa' => 'boolean',
        ];
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public function getUpdatedAtAttribute($value)
    {
        return date('m/d/Y', strtotime($value));
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', email, ' ', phone)"), 'like', '%'.$search.'%');
        });
    }

    
      public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

   

}
