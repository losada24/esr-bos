<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'name',
        'job_address',
        'city_permits',
        'association_permits',
        'equipment_rental',
        'equipment_rental_price',
        'additional_travel_costs',
        'entry_date',
        'notes',
        'client_id',
        'type_of_work_id',
        'type_of_housing_id',
        'supervisor_id',
        'travel_cost_id',
        'duration_of_work_id',
        'user_id',
        'method_of_payment',
        'service',
        'contract_signing_date',
        'payment_factory_date',
        'delivery_date',
        'installation_date',
        'status',
        'eta_date',
        'installation_end_date',
        'frame_color',
        'cost_delivery',
        'cost_city_fee',
        'project_amount',
        'city',
        'type_of_financing',
        'payment_definition',
        'initial_payment_percentage',
        'work_team_notes',
    ];

    protected $dates = [
        'entry_date',
        'installation_date',
        'contract_signing_date',
        'payment_factory_date',
        'delivery_date',
        'installation_date',
        'eta_date',
        'installation_end_date'
    ];

    protected function casts(): array
    {
        return [
            'city_permits' => 'boolean',
            'payment_definition' => 'boolean',
            
        ];
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['status'] ?? null, function ($query, $search) {
          $query->where('status', $search);
        })->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', order_number, ' ', job_address)"), 'like', '%'.$search.'%');
        });
    }

    public function scopeCalendarFilter($query, array $filters)
    {
      if (isset($filters['status']) && $filters['status'] != 'all') {
        $query->where('status', $filters['status']);
      }

      if (isset($filters['service']) && $filters['service'] != 'all') {
        $query->where('service', $filters['service']);
      }
    }

    public function client(): BelongsTo {
      return $this->belongsTo(Client::class);
    }

    public function typeOfWork(): BelongsTo {
      return $this->belongsTo(TypeOfWork::class);
    }

    public function typeOfHousing(): BelongsTo {
      return $this->belongsTo(TypeOfHousing::class);
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }

    public function supervisor(): BelongsTo {
      return $this->belongsTo(User::class, 'supervisor_id', 'id');
    }

    public function travelCost(): BelongsTo {
      return $this->belongsTo(TravelCost::class);
    }

    public function durationOfWork(): BelongsTo {
      return $this->belongsTo(DurationOfWork::class);
    }

    public function attachments(): MorphMany {
      return $this->morphMany(Attachment::class, 'attachable');
    }

    public function owners(): BelongsToMany
    {
      return $this->belongsToMany(User::class, 'owner_user');
    }

    public function orderProducts(): HasMany {
      return $this->hasMany(OrderProduct::class, 'order_id', 'id');
    }

    public function installationTeams(): BelongsToMany
    {
      return $this->belongsToMany(InstallationTeam::class, 'installation_teams_orders');
    }

    public function orderStatus()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function permit(): HasOne
    {
      return $this->hasOne(Permit::class);
    }

    public function getGrandTotalPrice() {
      $pricesWithExtraWorks = $this->orderProducts->sum('total_price') + $this->orderProducts->sum('extra_work_price');
      $travelCost = 0;
      if (isset($this->travel_cost_id)) {
        $travelCost = $this->travelCost->price;
      }
      return $pricesWithExtraWorks + $this->additional_travel_costs + $travelCost;
    }
}
