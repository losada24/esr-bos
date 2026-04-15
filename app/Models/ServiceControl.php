<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceControl extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'service_name',
        'service_id',
        'service_type',
        'description',
        'requires_part',
        'requested_parts',
        'parts_available',
        'service_status',
        'priority',
        'target_date',
        'scheduled_date',
        'executed_date',
        'opened_at',
        'closed_at',
        'closure_result',
        'observations',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'open_days',
    ];

    protected function casts(): array
    {
        return [
            'requires_part' => 'boolean',
            'requested_parts' => 'boolean',
            'parts_available' => 'boolean',
            'target_date' => 'date:Y-m-d',
            'scheduled_date' => 'date:Y-m-d',
            'executed_date' => 'date:Y-m-d',
            'opened_at' => 'date:Y-m-d',
            'closed_at' => 'date:Y-m-d',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ServiceControlHistory::class)->latest();
    }

    public function getOpenDaysAttribute(): int
    {
        $openedAt = $this->opened_at instanceof Carbon
            ? $this->opened_at->copy()->startOfDay()
            : ($this->opened_at ? Carbon::parse($this->opened_at)->startOfDay() : null);

        if (!$openedAt) {
            return 0;
        }

        $endDate = $this->closed_at instanceof Carbon
            ? $this->closed_at->copy()->startOfDay()
            : ($this->closed_at ? Carbon::parse($this->closed_at)->startOfDay() : now()->startOfDay());

        return max(0, $openedAt->diffInDays($endDate));
    }
}
