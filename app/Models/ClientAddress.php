<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ClientAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'client_address';
    protected $fillable = [
      'appointment_date',
      'notes',
      'address',
      'client_id'
    ];

    public function client(): BelongsTo {
      return $this->belongsTo(Client::class);
    }

}
