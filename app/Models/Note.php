<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
