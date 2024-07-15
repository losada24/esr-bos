<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TypeOfHousing extends Model
{
    use HasFactory , SoftDeletes;
    protected $table = 'types_of_housing';

    protected $fillable = [
      'name',
      'notes',
    ];

    public function orders(): HasMany {
      return $this->hasMany(Order::class);
    }
    public function installationTeamTypeHousings(): BelongsToMany
    {
        return $this->belongsToMany(InstallationTeam::class, 'installation_teams_types_of_housing', 'type_of_housing_id', 'installation_team_id', 'id', 'id');
    }
}
