<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Source extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
      'name',
      'description',

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
  

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['text'] ?? null, function ($query, $search) {
          $query->where(DB::raw("CONCAT(name, ' ', email, ' ', phone)"), 'like', '%'.$search.'%');
        });
    }


}
