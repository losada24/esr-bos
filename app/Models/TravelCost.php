<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TravelCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name',
      'price',
      'notes',
    ];

    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }

    public function configDateEstimates(): HasMany {
      return $this->hasMany(ConfigDateEstimation::class);
    }
    public function installationTeamTravelCost(): BelongsToMany
    {
        return $this->belongsToMany(InstallationTeam::class, 'installation_teams_travel_costs', 'travel_cost_id', 'installation_team_id', 'id', 'id');
    }
}
