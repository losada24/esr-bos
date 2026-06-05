<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmNotification extends Model
{
    use HasFactory;

    public const TYPE_FEED = 'feed';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_SYSTEM = 'system';

    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'title',
        'body',
        'data',
        'notifiable_type',
        'notifiable_id',
        'due_at',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'due_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
