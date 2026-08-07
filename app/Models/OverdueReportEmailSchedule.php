<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverdueReportEmailSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'weekdays',
        'send_time',
        'timezone',
        'recipient_user_ids',
        'manual_emails',
        'last_sent_at',
        'last_sent_date',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'weekdays' => 'array',
        'recipient_user_ids' => 'array',
        'manual_emails' => 'array',
        'last_sent_at' => 'datetime',
        'last_sent_date' => 'date',
    ];

    public static function defaultWeekdays(): array
    {
        return ['tuesday', 'thursday'];
    }

    public static function defaultTimezone(): string
    {
        return 'America/New_York';
    }
}
