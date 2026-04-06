<?php

use App\Models\TelegramSchedule;
use App\Services\TelegramService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Load report schedules dynamically from the database.
// Each row in telegram_schedules can be toggled and timed by the admin via the UI.
try {
    TelegramSchedule::where('is_enabled', true)->get()->each(function ($s) {
        $job = match ($s->report_type) {
            'daily'   => Schedule::call(fn () => app(TelegramService::class)->sendDailyReport())
                            ->dailyAt($s->time),
            'weekly'  => Schedule::call(fn () => app(TelegramService::class)->sendWeeklyReport())
                            ->weeklyOn($s->day_of_week ?? 1, $s->time),
            'monthly' => Schedule::call(fn () => app(TelegramService::class)->sendMonthlyReport())
                            ->monthlyOn($s->day_of_month ?? 1, $s->time),
            default   => null,
        };
        $job?->name("telegram:{$s->report_type}-report")->withoutOverlapping();
    });
} catch (\Throwable) {
    // Database may not be available (e.g. during fresh install / migrations).
}
