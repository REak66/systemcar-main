<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramSchedule extends Model
{
    protected $fillable = [
        'telegram_config_id',
        'report_type',
        'is_enabled',
        'time',
        'day_of_week',
        'day_of_month',
    ];

    protected $casts = [
        'is_enabled'    => 'boolean',
        'day_of_week'   => 'integer',
        'day_of_month'  => 'integer',
    ];

    public function telegramConfig()
    {
        return $this->belongsTo(TelegramConfig::class);
    }
}
