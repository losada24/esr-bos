<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'filename',
        'file_path',
        'file_type',
        'mime_type',
        'size_bytes',
        'duration_seconds',
        'transcription_status',
        'transcription_text',
        'transcription_error',
        'user_id',

    ];

    protected static function booted(): void
    {
        $touchRelatedOrder = function (Attachment $attachment) {
            $parent = $attachment->attachable;
            if ($parent instanceof Order) {
                $parent->touch();
            }
        };

        static::created($touchRelatedOrder);
        static::updated($touchRelatedOrder);
        static::deleted($touchRelatedOrder);
        static::restored($touchRelatedOrder);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function orderDocuments(): MorphTo
    {
        return $this->morphTo(Order::class, 'attachable');
    }

    public function installationTeamDocuments(): MorphTo
    {
        return $this->morphTo(InstallationTeam::class, 'attachable');
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }

    public function roleTargets(): HasMany
    {
        return $this->hasMany(OrderAttachmentRoleTarget::class);
    }
}
