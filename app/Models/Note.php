<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content',
        'type',
        'user_id',

    ];

    protected static function booted(): void
    {
        $touchRelatedOrder = function (Note $note) {
            $parent = $note->noteable;
            if ($parent instanceof Order) {
                $parent->touch();
            }
        };

        static::created($touchRelatedOrder);
        static::updated($touchRelatedOrder);
        static::deleted($touchRelatedOrder);
        static::restored($touchRelatedOrder);
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function clientNotes(): MorphTo
    {
        return $this->morphTo(Client::class, 'noteable');
    }

    public function orderNotes(): MorphTo
    {
        return $this->morphTo(Order::class, 'noteable');
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
