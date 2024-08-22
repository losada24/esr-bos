<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InstallationTeam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number_of_member',
        'worker_compensation_expiration_date',
        'liability_expiration_date',
        'notes',
        'user_id',
        'company_name',
        'phone',
        'work_area'
    ];

    protected $dates = [
      'worker_compensation_expiration_date',
      'liability_expiration_date',
    ];

    public function scopeFilter($query, array $filters)
    {
        /* $query->when($filters['status'] ?? null, function ($query, $search) {
          $query->where('status', $search);
        })->when($filters['client_id'] ?? null, function ($query, $search) {
          // $query->where('client_id', $search);
          $query->whereHas('client', function ($query) use ($search) {
            //$query->where('name', 'like', '%'.$search.'%');
            $query->where(DB::raw("CONCAT(name, ' ', email, ' ',phone)"), 'like', '%'.$search.'%');
          });
        })->when($filters['entry_date'] ?? null, function ($query, $search) {
          $query->where('entry_date', $search);
        })->when($filters['address'] ?? null, function ($query, $search) {
          $query->where('address', 'like', '%'.$search.'%');
        }); */
    }

    public function attachments(): MorphMany {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }
    public function typeHousing(): BelongsToMany {
      return $this->belongsToMany(TypeOfHousing::class, 'installation_teams_types_of_housing', 'installation_team_id', 'type_of_housing_id', 'id', 'id');
    }

    public function travelCost(): BelongsToMany {
      return $this->belongsToMany(TravelCost::class, 'installation_teams_travel_costs', 'installation_team_id', 'travel_cost_id', 'id', 'id');
    }

    public function orders(): BelongsToMany
    {
      return $this->belongsToMany(Order::class, 'installation_teams_orders');
    }
}
