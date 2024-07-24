<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

class ConfigDateEstimation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_date_estimation';

    protected $fillable = [
        'travel_cost_id',
        'type_of_housing_id',
        'weeks',
        'week_day',
        'days_difference_between_services'
    ];

    public function travelCost(): BelongsTo {
      return $this->belongsTo(TravelCost::class);
    }

    public function typeOfHousing(): BelongsTo {
      return $this->belongsTo(TypeOfHousing::class);
    }
}
