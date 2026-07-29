<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceControl extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'client_id',
        'service_name',
        'service_id',
        'external_order_id',
        'is_bm',
        'service_source',
        'creation_source',
        'request_origin',
        'service_type',
        'description',
        'requires_part',
        'requested_parts',
        'parts_available',
        'service_status',
        'priority',
        'cost',
        'area',
        'requester_type',
        'requester_id',
        'requester_role',
        'assignee_type',
        'assignee_id',
        'assignee_role',
        'target_date',
        'service_created_date',
        'service_id_requested_date',
        'eta_date',
        'parts_received_date',
        'production_output_overdue_days',
        'production_output_overdue_resolved_at',
        'part_delivered_date',
        'scheduled_date',
        'executed_date',
        'opened_at',
        'closed_at',
        'closure_result',
        'observations',
        'bm_quantity',
        'bm_requested_date',
        'bm_picked_up_by',
        'bm_pickup_date',
        'bm_invoice_number',
        'bm_invoice_status',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'open_days',
    ];

    protected $casts = [
        'requires_part' => 'boolean',
        'requested_parts' => 'boolean',
        'parts_available' => 'boolean',
        'is_bm' => 'boolean',
        'service_type' => 'array',
        'cost' => 'decimal:2',
        'production_output_overdue_days' => 'integer',
        'target_date' => 'date:Y-m-d',
        'service_created_date' => 'date:Y-m-d',
        'service_id_requested_date' => 'date:Y-m-d',
        'eta_date' => 'date:Y-m-d',
        'parts_received_date' => 'date:Y-m-d',
        'production_output_overdue_resolved_at' => 'datetime',
        'part_delivered_date' => 'date:Y-m-d',
        'scheduled_date' => 'date:Y-m-d',
        'executed_date' => 'date:Y-m-d',
        'opened_at' => 'date:Y-m-d',
        'closed_at' => 'date:Y-m-d',
        'bm_requested_date' => 'date:Y-m-d',
        'bm_pickup_date' => 'date:Y-m-d',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
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
