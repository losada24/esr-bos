<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'host_id',
        'order_id',
        'client_id',
        'title',
        'starts_at',
        'ends_at',
        'status',
        'is_repeating',
        'reminder_enabled',
        'reminder_minutes_before',
        'location',
        'online_meeting',
        'meeting_link',
        'participants',
        'description',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_repeating' => 'boolean',
        'reminder_enabled' => 'boolean',
        'online_meeting' => 'boolean',
        'participants' => 'array',
    ];

    public function setParticipantsAttribute($value): void
    {
        $this->attributes['participants'] = is_array($value) ? json_encode($value) : $value;
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
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
