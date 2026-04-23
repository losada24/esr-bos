<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionPeriod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'start_date',
        'end_date',
        'label',
        'status',
        'closed_at',
        'closed_by',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'closed_at',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(OrderCommissionPayment::class);
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(CommissionPeriodSnapshot::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
