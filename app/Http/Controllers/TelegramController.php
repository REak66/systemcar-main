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
            'configs'   => TelegramConfig::all(),
            'schedules' => TelegramSchedule::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'bot_token' => 'required|string',
            'chat_id'   => 'required|string',
            'is_active' => 'boolean',
        ]);
        $c = TelegramConfig::create($data);
        $this->audit->log('create', 'TelegramConfig', $c->id, [], $c->toArray());
        return back()->with('success', 'Telegram config saved.');
    }

    public function update(Request $request, TelegramConfig $telegram)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'bot_token' => 'required|string',
            'chat_id'   => 'required|string',
            'is_active' => 'boolean',
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
        match ($schedule->report_type) {
            'daily'   => $this->telegram->sendDailyReport(),
            'weekly'  => $this->telegram->sendWeeklyReport(),
            'monthly' => $this->telegram->sendMonthlyReport(),
        };
        $this->audit->log('send', 'TelegramSchedule', $schedule->id, [], ['type' => $schedule->report_type]);
        return back()->with('success', ucfirst($schedule->report_type) . ' report sent.');
    }
}
