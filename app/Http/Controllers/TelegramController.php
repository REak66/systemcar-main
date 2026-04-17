<?php

namespace App\Http\Controllers;

use App\Models\TelegramConfig;
use App\Models\TelegramSchedule;
use App\Services\AuditLogService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TelegramController extends Controller
{
    public function __construct(
        private AuditLogService $audit,
        private TelegramService $telegram,
    ) {}

    public function index()
    {
        return Inertia::render('Telegram/Index', [
            'configs' => TelegramConfig::with([
                'schedules' => fn ($q) => $q->orderByRaw("FIELD(report_type,'daily','weekly','monthly')"),
            ])->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'daily_chat_id'    => 'nullable|string|max:255',
            'weekly_chat_id'   => 'nullable|string|max:255',
            'monthly_chat_id'  => 'nullable|string|max:255',
            'document_chat_id' => 'nullable|string|max:255',
            'is_active'        => 'boolean',
        ]);
        $c = TelegramConfig::create($data);

        // Auto-create the 3 default schedules for this new branch
        foreach ([
            ['report_type' => 'daily',   'time' => '18:00', 'day_of_week' => null, 'day_of_month' => null],
            ['report_type' => 'weekly',  'time' => '08:00', 'day_of_week' => 1,    'day_of_month' => null],
            ['report_type' => 'monthly', 'time' => '08:00', 'day_of_week' => null, 'day_of_month' => 1],
        ] as $sched) {
            $c->schedules()->create(array_merge($sched, ['is_enabled' => false]));
        }

        $this->audit->log('create', 'TelegramConfig', $c->id, [], $c->toArray());
        return back()->with('success', 'Branch created.');
    }

    public function update(Request $request, TelegramConfig $telegram)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'daily_chat_id'    => 'nullable|string|max:255',
            'weekly_chat_id'   => 'nullable|string|max:255',
            'monthly_chat_id'  => 'nullable|string|max:255',
            'document_chat_id' => 'nullable|string|max:255',
            'is_active'        => 'boolean',
        ]);
        $old = $telegram->toArray();
        $telegram->update($data);
        $this->audit->log('update', 'TelegramConfig', $telegram->id, $old, $telegram->fresh()->toArray());
        return back()->with('success', 'Telegram config updated.');
    }

    public function destroy(TelegramConfig $telegram)
    {
        $this->audit->log('delete', 'TelegramConfig', $telegram->id, $telegram->toArray(), []);
        $telegram->delete();
        return back()->with('success', 'Telegram config deleted.');
    }

    public function updateSchedule(Request $request, TelegramSchedule $schedule)
    {
        $data = $request->validate([
            'is_enabled'   => 'boolean',
            'time'         => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'day_of_week'  => 'nullable|integer|between:0,6',
            'day_of_month' => 'nullable|integer|between:1,31',
        ]);
        $old = $schedule->toArray();
        $schedule->update($data);
        $this->audit->log('update', 'TelegramSchedule', $schedule->id, $old, $schedule->fresh()->toArray());
        return back()->with('success', 'Schedule updated.');
    }

    public function sendNow(TelegramSchedule $schedule)
    {
        $config = $schedule->telegramConfig;
        if (!$config) {
            return back()->withErrors(['error' => 'No branch config found for this schedule.']);
        }

        match ($schedule->report_type) {
            'daily'   => $this->telegram->sendDailyReport($config),
            'weekly'  => $this->telegram->sendWeeklyReport($config),
            'monthly' => $this->telegram->sendMonthlyReport($config),
        };
        $this->audit->log('send', 'TelegramSchedule', $schedule->id, [], [
            'type'   => $schedule->report_type,
            'branch' => $config->name,
        ]);
        return back()->with('success', ucfirst($schedule->report_type) . ' report sent for ' . $config->name . '.');
    }
}
