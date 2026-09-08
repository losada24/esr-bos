<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmEventGoogleCalendarSync extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SYNCED = 'synced';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'crm_event_id',
        'user_email',
        'google_event_id',
        'status',
        'last_error',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CrmEvent::class, 'crm_event_id');
    }
}
