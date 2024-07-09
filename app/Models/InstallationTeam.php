<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class InstallationTeam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number_of_member',
        'worker_compensation_expiration_date',
        'liability_expiration_date',
        'notes',
        'user_id',
    ];

    public function attachments(): MorphToMany {
        return $this->morphToMany(Attachment::class, 'attachable');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
      }
      public function installationTeamTypeHousing(): BelongsToMany
    {
        return $this->belongsToMany(TypeOfHousing::class, 'installation_teams_types_of_housing');
    }

    public function orders(): BelongsToMany
    {
      return $this->belongsToMany(Order::class, 'installation_teams_orders');
    }
}
