<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmCall extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'order_id',
        'client_id',
        'to_from',
        'call_start_time',
        'call_duration_minutes',
        'reminder_enabled',
        'reminder_minutes_before',
        'call_type',
        'outgoing_call_status',
        'call_purpose',
        'call_agenda',
    ];

    protected $casts = [
        'call_start_time' => 'datetime',
        'reminder_enabled' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }
}
