<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverdueReportEmailSchedule extends Model
{
  use HasFactory;

  protected $fillable = [
    'enabled',
    'send_time',
    'timezone',
    'days_of_week',
    'user_recipient_ids',
    'manual_recipients',
    'last_sent_at',
  ];

  protected $casts = [
    'enabled' => 'boolean',
    'days_of_week' => 'array',
    'user_recipient_ids' => 'array',
    'manual_recipients' => 'array',
    'last_sent_at' => 'datetime',
  ];

  public static function current(): self
  {
    return static::query()->firstOrCreate([], [
      'enabled' => false,
      'send_time' => '08:00:00',
      'timezone' => 'America/New_York',
      'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
      'user_recipient_ids' => [],
      'manual_recipients' => [],
    ]);
  }
}
