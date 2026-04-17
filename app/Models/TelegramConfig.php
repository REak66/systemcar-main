<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TelegramConfig extends Model {
    protected $fillable = [
        'name',
        'daily_chat_id',
        'weekly_chat_id',
        'monthly_chat_id',
        'document_chat_id',
        'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function schedules()
    {
        return $this->hasMany(TelegramSchedule::class)->orderByRaw("FIELD(report_type,'daily','weekly','monthly')");
    }
}
