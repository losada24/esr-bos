<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmEventOccurrenceEmail extends Model
{
    protected $fillable = [
        'crm_event_id',
        'occurrence_starts_at',
        'occurrence_ends_at',
        'sent_at',
    ];

    protected $casts = [
        'occurrence_starts_at' => 'datetime',
        'occurrence_ends_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CrmEvent::class, 'crm_event_id');
    }
}
