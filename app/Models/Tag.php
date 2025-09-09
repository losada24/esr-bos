<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'type',
        'user_id',

    ];

    public function clientTags(): MorphTo
    {
        return $this->morphTo(Client::class, 'taggable');
    }

    public function orderTags(): MorphTo
    {
        return $this->morphTo(Order::class, 'taggable');
    }

    public function user(): BelongsTo {
      return $this->belongsTo(User::class);
    }
}
