<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceControlHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_control_id',
        'user_id',
        'event_type',
        'summary',
        'old_values',
        'new_values',
        'comment',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function serviceControl(): BelongsTo
    {
        return $this->belongsTo(ServiceControl::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
