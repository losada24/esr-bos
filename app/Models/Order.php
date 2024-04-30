<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Enum\RoleEnum;
use App\Enum\OrderStatusEnum;
use Carbon\Carbon;

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
      'user_markup',
      'rg_other_price',
      'order_promotion',
      'subdealer_other',
      'external_products_markup'
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public function getUpdatedAtAttribute($value)
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
          $query->where(DB::raw("CONCAT(id, ' ', name, ' ', COALESCE('project_name', ''))"), 'like', '%'. $searchStr .'%');
        })->when($filters['status'] ?? null, function($query, $search) use ($filters) {
          if ((isset($filters['dates']) && count($filters['dates']) !== 2) || !isset($filters['dates'])) {
            $query->where('orders.status', $search);
          }
        })->when($filters['dates'] ?? null, function($query, $search) use ($filters) {
          if (count($filters['dates']) === 2) {
              $startDate = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $filters['dates'][0])->startOfDay();
              $endDate = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $filters['dates'][1])->endOfDay();
              $defaultStatus = OrderStatusEnum::$ACCOUNTING;
              if (isset($filters['status']) && $filters['status'] != '') {
                $defaultStatus = $filters['status'];
              }
              $query->join('order_status', function($join) {
                $join->on('orders.id', '=', 'order_status.order_id');
              })->whereBetween('order_status.created_at', [$startDate, $endDate])->where('order_status.status', $defaultStatus)->select('orders.*');
          }
        });
    }

    /**
     * Scope a query to only show available orders by roles.
     */
    public function scopeOrders(Builder $query): void
    {
        if (auth()->user()->hasRole(RoleEnum::$ACCOUNTING)) {
          $query->where('orders.status', OrderStatusEnum::$ACCOUNTING)
            ->orWhere('orders.status', OrderStatusEnum::$PRODUCTION_COMPLETED)
            ->orWhere('orders.status', OrderStatusEnum::$PRODUCTION)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED)
            ->orWhere('orders.status', OrderStatusEnum::$PRODUCTION_IN_PROGRESS)
            ->orWhere('orders.status', OrderStatusEnum::$SCHEDULED_PRODUCTION)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_DELIVERY)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY)
            ->orWhere('orders.status', OrderStatusEnum::$ORDER_COMPLETED)
            ->orWhere('orders.status', OrderStatusEnum::$PICKED_UP)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_PICKED_UP)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_DELIVERED)
            ->orWhere('orders.status', OrderStatusEnum::$DELIVERED);
        }
        else if (auth()->user()->hasRole(RoleEnum::$PRODUCTION)) {
          $query->where('orders.status', OrderStatusEnum::$PRODUCTION)
            ->orWhere('orders.status', OrderStatusEnum::$PRODUCTION_IN_PROGRESS)
            ->orWhere('orders.status', OrderStatusEnum::$SCHEDULED_PRODUCTION)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_DELIVERED)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_PICKED_UP)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_DELIVERY)
            ->orWhere('orders.status', OrderStatusEnum::$ORDER_COMPLETED)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY);
        }
        else if (auth()->user()->hasRole(RoleEnum::$SHIPPING)) {
          $query->where('orders.status', OrderStatusEnum::$READY_FOR_DELIVERY)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PICKUP)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP)
            ->orWhere('orders.status', OrderStatusEnum::$DELIVERED)
            ->orWhere('orders.status', OrderStatusEnum::$PICKED_UP)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_DELIVERED)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_PICKED_UP);
        }
        else if (auth()->user()->hasRole(RoleEnum::$PLANT_MANAGER)) {
          $query->where('orders.status', OrderStatusEnum::$PRODUCTION_IN_PROGRESS)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_PRODUCTION_COMPLETED)
            ->orWhere('orders.status', OrderStatusEnum::$PRODUCTION_COMPLETED)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PARTIAL_DELIVERY)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PICKUP)
            ->orWhere('orders.status', OrderStatusEnum::$READY_FOR_PARTIAL_PICKUP)
            ->orWhere('orders.status', OrderStatusEnum::$DELIVERED)
            ->orWhere('orders.status', OrderStatusEnum::$PICKED_UP)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_DELIVERED)
            ->orWhere('orders.status', OrderStatusEnum::$PARTIAL_PICKED_UP);
        }
        else if (auth()->user()->hasRole(RoleEnum::$SUB_DEALER)) {
          $query->where('user_id', auth()->user()->id)
            ->where(function (Builder $query) {
              $query->where('orders.status', '<>', OrderStatusEnum::$SUB_DEALER_ESTIMATE)
                ->where('orders.status', '<>', OrderStatusEnum::$ESTIMATE);
            });
        }
        else if (auth()->user()->hasRole(RoleEnum::$DEALER)) {
          $query->where('company_id', auth()->user()->company_id)
          ->where(function (Builder $query) {
              $query->where('orders.status', '<>', OrderStatusEnum::$SUB_DEALER_ESTIMATE)
              ->where('orders.status', '<>', OrderStatusEnum::$ESTIMATE);
          });
        }
        else if (auth()->user()->hasRole(RoleEnum::$ADMIN)) {
          $query->where('orders.status', '<>', OrderStatusEnum::$ESTIMATE)
            ->where('orders.status', '<>', OrderStatusEnum::$SUB_DEALER_ESTIMATE);
        }
    }

    /**
     * Scope a query to only show available orders by roles.
     */
    public function scopeEstimates(Builder $query): void
    {
        if (auth()->user()->hasRole(RoleEnum::$DEALER)) {
          if (!request()->filled('text')) {
            $query->where('company_id', auth()->user()->company_id)
              ->where('status', OrderStatusEnum::$ESTIMATE);
          } else {
            $query->where('company_id', auth()->user()->company_id)
              ->where(function(Builder $query) {
                $query->where('status', OrderStatusEnum::$ESTIMATE)
                  ->orWhere('status', OrderStatusEnum::$SUB_DEALER_ESTIMATE);
              });
          }
        } else if (auth()->user()->hasRole(RoleEnum::$SUB_DEALER)) {
          $query->where(function(Builder $query) {
            $query->where('status', OrderStatusEnum::$SUB_DEALER_ESTIMATE)
              ->orWhere('status', OrderStatusEnum::$ESTIMATE);
          })->where('user_id', auth()->user()->id);
        } else {
          $query->where('status', OrderStatusEnum::$ESTIMATE)
            ->orWhere('status', OrderStatusEnum::$SUB_DEALER_ESTIMATE);
        }
    }

    public function scopeReports(Builder $query): void
    {
        if (auth()->user()->hasRole(RoleEnum::$DEALER)) {
          $query->where('company_id', auth()->user()->company_id);
        } else if (auth()->user()->hasRole(RoleEnum::$SUB_DEALER)) {
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
