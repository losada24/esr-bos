<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'filename',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function orderDocuments(): MorphToMany 
    {
        return $this->morphedByMany(Order::class, 'attachable');
    }

    public function installationTeamDocuments(): MorphToMany 
    {
        return $this->morphedByMany(InstallationTeam::class, 'attachable');
    }
}
