<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderCompanyContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'company_contact_id',
        'client_id',
        'source_id',
        'is_selected',
        'selected_at',
    ];

    protected function casts(): array
    {
        return [
            'is_selected' => 'boolean',
            'selected_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function companyContact(): BelongsTo
    {
        return $this->belongsTo(CompanyContact::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
