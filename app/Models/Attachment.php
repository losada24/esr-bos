<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'user_id',

    ];

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
}
