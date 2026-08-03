<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPhase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'position',
        'name',
        'status',
        'delivery_date',
        'installation_date',
        'installation_end_date',
        'inspection_date',
        'finish_date',
        'service_date',
        'pending_collect',
        'final_inspection_date',
        'complete_date',
        'supervisor_id',
        'hide_on_weekends',
        'replanned_reasons',
        'notes',
        'last_email_sent_at',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'installation_date' => 'date',
        'installation_end_date' => 'date',
        'inspection_date' => 'date',
        'finish_date' => 'date',
        'service_date' => 'date',
        'pending_collect' => 'date',
        'final_inspection_date' => 'date',
        'complete_date' => 'date',
        'hide_on_weekends' => 'boolean',
        'replanned_reasons' => 'array',
        'last_email_sent_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function installationTeams(): BelongsToMany
    {
        return $this->belongsToMany(InstallationTeam::class, 'order_phase_installation_team')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function phaseProducts(): HasMany
    {
        return $this->hasMany(OrderPhaseProduct::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderPhaseLog::class)->latest();
    }
}
