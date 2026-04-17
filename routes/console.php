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
// Each row is now tied to a specific branch (telegram_config_id).
try {
    TelegramSchedule::where('is_enabled', true)
        ->with('telegramConfig')
        ->get()
        ->each(function ($s) {
            $config = $s->telegramConfig;
            if (!$config || !$config->is_active) return;

            $configId = $s->telegram_config_id;
            $job = match ($s->report_type) {
                'daily'   => Schedule::call(fn () => app(TelegramService::class)->sendDailyReport($config))
                                ->dailyAt($s->time),
                'weekly'  => Schedule::call(fn () => app(TelegramService::class)->sendWeeklyReport($config))
                                ->weeklyOn($s->day_of_week ?? 1, $s->time),
                'monthly' => Schedule::call(fn () => app(TelegramService::class)->sendMonthlyReport($config))
                                ->monthlyOn($s->day_of_month ?? 1, $s->time),
                default   => null,
            };
            $job?->name("telegram:{$s->report_type}-report-config{$configId}")->withoutOverlapping();
        });
} catch (\Throwable) {
    // Database may not be available (e.g. during fresh install / migrations).
}
