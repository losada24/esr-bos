<?php

namespace App\Models;

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Enum\SupervisorPaymentStatusEnum;
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
use Spatie\Permission\Traits\HasRoles;

class Order extends Model
{
  use HasFactory, SoftDeletes, HasRoles;

  protected $dispatchesEvents = [
    'created' => \App\Events\OrderCreated::class,
    'updated' => \App\Events\OrderCreated::class,
  ];

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
    'job_state',
    'job_zip',
    'supervisor_payment_status',
    'execution_planing_date',
    'supervisor_commissions',
    'supervisor_payment_percentage',
    'hide_on_weekends',
    'inspection_date',
    'finish_date',
    'final_inspection_date',
    'complete_date',
    'do_not_send_email',
    'service_date',
    'pending_collect',
    'pre_inspection',
    'inspection',
    'walk_trough',
    'partial_payment_installation',
    'final_payment_installation',
    'is_send_email',
    'is_new_travel_cost',
    'new_travel_cost',
    'material_received_date',
    'loss_reason_frontdesk',
    'description',
    'order_type',
    'bid_due_date',
  ];

  protected $dates = [
    'entry_date',
    'installation_date',
    'contract_signing_date',
    'payment_factory_date',
    'delivery_date',
    'installation_date',
    'eta_date',
    'installation_end_date',
    'supervisor_payment_date',
    'inspection_date',
    'finish_date',
    'final_inspection_date',
    'complete_date',
    'service_date',
    'pending_collect',
    'material_received_date',
    'bid_due_date',
    
  ];

  protected function casts(): array
  {
    return [
      'city_permits' => 'boolean',
      'payment_definition' => 'boolean',
      'hide_on_weekends' => 'boolean',
      'do_not_send_email' => 'boolean',
      'pre_inspection' => 'boolean',
      'inspection' => 'boolean',
      'walk_trough' => 'boolean',
      'partial_payment_installation' => 'boolean',
      'final_payment_installation' => 'boolean',
      'is_send_email' => 'boolean',
      'is_new_travel_cost' => 'boolean',
    ];
  }

  public function scopeFilter($query, array $filters)
  {
    $query->when($filters['status'] ?? null, function ($query, $search) {
      $query->where('status', $search);
    })->when($filters['text'] ?? null, function ($query, $search) {
      $query->where(DB::raw("CONCAT(name, ' ', order_number, ' ', job_address)"), 'like', '%' . $search . '%');
    });
  }
  public function scopeSupervisorFilter($query, array $filters)
  {
    $query->when($filters['status'] ?? null, function ($query, $search) {
      if ($search != '') {
        if($search == 'null'){
          $query->whereIn('supervisor_payment_status', [SupervisorPaymentStatusEnum::PENDING->value, SupervisorPaymentStatusEnum::OPEN->value]);
        }
        else{
          $query->where('supervisor_payment_status', $search);
        }
      }
    
      
    });
  }

  public function scopeCalendarFilter($query, array $filters)
  {
    /*i f (isset($filters['status']) && $filters['status'] != 'all') {
        $query->where('status', $filters['status']);
      } */

    if (isset($filters['status']) && $filters['status'] != 'all') {
      if ($filters['status'] === OrderStatusEnum::CONFIRMED_FINISH->value) {
        // Mostrar órdenes con estatus CONFIRMED y FINISH
        $query->whereIn('status', [
          OrderStatusEnum::CONFIRMED,
          OrderStatusEnum::FINISH,
        ]);
      } else {
        // Aplicar filtro de estatus específico
        $query->where('status', $filters['status']);
      }
    }

    /*if (isset($filters['service']) && $filters['service'] != 'all') {
        $query->where('service', $filters['service']);
        if ($filters['service'] === ServiceEnum::DELIVERY->value) {
          $query->orWhere('service', ServiceEnum::INSTALLATION->value);
        }
      }*/
    if (isset($filters['service']) && $filters['service'] != 'all') {
      $query->where(function ($query) use ($filters) {
        if ($filters['service'] === ServiceEnum::DELIVERY->value) {
          $query->where('service', ServiceEnum::DELIVERY->value)
            ->orWhere('service', ServiceEnum::INSTALLATION->value);
        } else {
          $query->where('service', $filters['service']);
        }
      });
    }


    /* if (isset($filters['name']) && $filters['name'] !== 'all' && !empty($filters['name'])) {
        $query->where('name', 'like', '%' . $filters['name'] . '%');
    }*/

    if (isset($filters['name']) && $filters['name'] !== 'all' && !empty($filters['name'])) {
      $query->where(function ($query) use ($filters) {
        $query->where('name', 'like', '%' . $filters['name'] . '%') // Filtro por nombre principal
          ->orWhereHas('installationTeams.user', function ($query) use ($filters) {
            $query->where('name', 'like', '%' . $filters['name'] . '%'); // Filtro por nombre del instalador
          })
          ->orWhereHas('supervisor', function ($query) use ($filters) {
            $query->where('name', 'like', '%' . $filters['name'] . '%'); // Filtro por nombre del supervisor
          });
      });
    }


    if (!(auth()->user()->hasRole(RoleEnum::ACCOUNT_MANAGER->value)) && !(auth()->user()->hasRole(RoleEnum::ADMIN->value))&& !(auth()->user()->hasRole(RoleEnum::OWNER_ADMIN->value))&& !(auth()->user()->hasRole(RoleEnum::FRONTDESK->value))) {
      if (auth()->user()->hasRole(RoleEnum::INSTALLER->value)) {
        $installationTeams = InstallationTeam::where('user_id', auth()->user()->id)->first();
        $query->whereHas('installationTeams', function ($q) use ($installationTeams) {
          $q->where('installation_teams.id', $installationTeams->id);
        });
      }
      /*if (auth()->user()->hasRole(RoleEnum::SUPERVISOR->value)) {
        $supervisor = User::where ('user_id', auth()->user()->id)->first();
        $query->whereHas('supervisor', function ($q) use ($supervisor)  {
          $q->where('supervisor_id', $supervisor->user->id);
        });*/

      if (auth()->user()->hasRole(RoleEnum::SUPERVISOR->value)) {
        $query->where('supervisor_id', auth()->user()->id)
          ->whereIn('status', [
            OrderStatusEnum::PLANNED,        // Solo órdenes en "PLANNED"
            OrderStatusEnum::RESCHEDULE,   // Solo órdenes en "EXECUTION"
            OrderStatusEnum::CONFIRMED,   // Solo órdenes en "EXECUTION"
            OrderStatusEnum::EXECUTION,
            OrderStatusEnum::SUPERVISION,
            OrderStatusEnum::INSPECTION,
            OrderStatusEnum::FINISH,
            OrderStatusEnum::SERVICE,
            OrderStatusEnum::ON_HOLD,
            OrderStatusEnum::FINAL_INSPECTION,
            OrderStatusEnum::FINAL_COLLECT,
            OrderStatusEnum::COMPLETE,
            OrderStatusEnum::MATERIALS_RECEIVED,
          ]);
      }

      if (auth()->user()->hasRole(RoleEnum::OWNER->value)) {
        $query->whereHas('owners', function ($ownerQuery) {
          $ownerQuery->where('user_id', auth()->user()->id);
        })
        ->whereIn('status', [
          OrderStatusEnum::PLANNED,
          OrderStatusEnum::RESCHEDULE,
          OrderStatusEnum::CONFIRMED,
          OrderStatusEnum::EXECUTION,
          OrderStatusEnum::SUPERVISION,
          OrderStatusEnum::INSPECTION,
          OrderStatusEnum::FINISH,
          OrderStatusEnum::SERVICE,
          OrderStatusEnum::ON_HOLD,
          OrderStatusEnum::FINAL_INSPECTION,
          OrderStatusEnum::FINAL_COLLECT,
          OrderStatusEnum::COMPLETE,
        ]);
      }

      if (auth()->user()->hasRole(RoleEnum::SERVICE_MANAGER->value) || auth()->user()->hasRole(RoleEnum::PAYMENT_COORDINATOR->value)) {
        $query->whereIn('status', [
          OrderStatusEnum::RESCHEDULE,   // Solo órdenes en "EXECUTION"
          OrderStatusEnum::CONFIRMED,   // Solo órdenes en "EXECUTION"
          OrderStatusEnum::EXECUTION,
          OrderStatusEnum::SUPERVISION,
          OrderStatusEnum::INSPECTION,
          OrderStatusEnum::FINISH,
          OrderStatusEnum::SERVICE,
          OrderStatusEnum::FINAL_INSPECTION,
          OrderStatusEnum::FINAL_COLLECT,
          OrderStatusEnum::COMPLETE,
        ]);
      }
    }
  }

  public static function booted()
  {
      static::updated(function ($order) {
          \Log::info('Order updated: ' . $order->id);
      });
  }
  public function client(): BelongsTo
  {
    return $this->belongsTo(Client::class);
  }

  public function typeOfWork(): BelongsTo
  {
    return $this->belongsTo(TypeOfWork::class);
  }

  public function typeOfHousing(): BelongsTo
  {
    return $this->belongsTo(TypeOfHousing::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function supervisor(): BelongsTo
  {
    return $this->belongsTo(User::class, 'supervisor_id', 'id');
  }

  public function travelCost(): BelongsTo
  {
    return $this->belongsTo(TravelCost::class);
  }

  public function comissions(): HasMany
  {
    return $this->hasMany(SupervisorComissionOrder::class);
  }

  public function durationOfWork(): BelongsTo
  {
    return $this->belongsTo(DurationOfWork::class);
  }

  public function attachments(): MorphMany
  {
    return $this->morphMany(Attachment::class, 'attachable');
  }

  public function owners(): BelongsToMany
  {
    return $this->belongsToMany(User::class, 'owner_user');
  }

  public function orderProducts(): HasMany
  {
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

  public function installationPayments()
  {
    return $this->hasMany(InstallationPayment::class);
  }

  /*public function paymentExtraFields()
    {
        return $this->hasMany(PaymentExtraField::class);
    }*/

  public function paymentExtraFields()
  {
    return $this->hasOne(PaymentExtraField::class, 'order_id', 'id');
  }

  public function permit(): HasOne
  {
    return $this->hasOne(Permit::class);
  }

  public function orderColors(): HasMany
  {
    return $this->hasMany(OrderColors::class, 'order_id', 'id');
  }

  public function orderClientTemps()
  {
    return $this->hasMany(OrderClientTemps::class);
  }

  public function getGrandTotalPrice()
  {
    $pricesWithExtraWorks = $this->orderProducts->sum('total_price') + $this->orderProducts->sum('extra_work_price');
    $travelCost = 0;
    if (isset($this->travel_cost_id) && $this->is_new_travel_cost == 0) {
      $travelCost = $this->travelCost->price;
    }
    if ($this->is_new_travel_cost == 1) {
      $travelCost = $this->new_travel_cost;
    }
    return $pricesWithExtraWorks + $this->additional_travel_costs + $travelCost;
  }

  public function syncFrameColors(array $colors): void
  {
      $this->orderColors()->delete();

      $this->orderColors()->createMany(
          collect($colors)->map(fn($color) => ['name' => $color])->toArray()
      );
  }

}
