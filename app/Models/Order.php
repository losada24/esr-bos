<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Enum\RoleEnum;
use App\Enum\OrderStatusEnum;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
      'name',
      'status',
      'notes',
      'project_name',
      'frame_color',
      'glass_color',
      'markup',
      'user_id',
      'client_id',
      'tax_amount',
      'tax_rate',
      'installation',
      'permit',
      'other',
      'company_id',
      'external_purchase_id',
      'glass_type',
      'company_markup',
      'company_promotion',
    ];

    protected $dispatchesEvents = [
      'created' => \App\Events\OrderCreated::class,
      'updated' => \App\Events\OrderCreated::class,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public function getCreatedAtAttribute($value)
    {
        return date('m/d/Y', strtotime($value));
    }

    public function getQuoteNumberAttribute()
    {
        return str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $searchStr = ltrim($search, "0");
          $query->where(DB::raw("CONCAT(id, ' ', name, ' ', project_name)"), 'like', '%'. $searchStr .'%');
        });
    }

    /**
     * Scope a query to only show available orders by roles.
     */
    public function scopeOrders(Builder $query): void
    {
        if (auth()->user()->hasRole(RoleEnum::$CLIENT_ADMIN)) {
          $query->where('company_id', auth()->user()->company_id)
            ->where('status', '<>', OrderStatusEnum::$ESTIMATE);
        }
        else if (auth()->user()->hasRole(RoleEnum::$ACCOUNTING)) {
          $query->where('status', OrderStatusEnum::$ACCOUNTING)
            ->orWhere('status', OrderStatusEnum::$PRODUCTION_COMPLETED)
            ->orWhere('status', OrderStatusEnum::$PRODUCTION)
            ->orWhere('status', OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED)
            ->orWhere('status', OrderStatusEnum::$PARTIAL_DELIVERED);
        }
        else if (auth()->user()->hasRole(RoleEnum::$PRODUCTION)) {
          $query->where('status', OrderStatusEnum::$PRODUCTION)
            ->orWhere('status', OrderStatusEnum::$PRODUCTION_IN_PROGRESS)
            ->orWhere('status', OrderStatusEnum::$SCHEDULED_PRODUCTION)
            ->orWhere('status', OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED)
            ->orWhere('status', OrderStatusEnum::$PARTIAL_DELIVERED);
        }
        else if (auth()->user()->hasRole(RoleEnum::$SHIPPING)) {
          $query->where('status', OrderStatusEnum::$READY_FOR_DELIVERY);
        }
        else if (auth()->user()->hasRole(RoleEnum::$ADMIN)) {
          $query->where('status', '<>', OrderStatusEnum::$ESTIMATE);
        }
    }

    /**
     * Scope a query to only show available orders by roles.
     */
    public function scopeEstimates(Builder $query): void
    {
        $query->where('status', OrderStatusEnum::$ESTIMATE);

        if (auth()->user()->hasRole(RoleEnum::$CLIENT_ADMIN)) {
          $query->where('company_id', auth()->user()->company_id);
        }
        else if (auth()->user()->hasRole(RoleEnum::$CLIENT)) {
          $query->where('user_id', auth()->user()->id);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function snapshots()
    {
        return $this->hasMany(OrderSnapshots::class);
    }

    public function orderStatus()
    {
        return $this->hasMany(OrderStatus::class);
    }
}
