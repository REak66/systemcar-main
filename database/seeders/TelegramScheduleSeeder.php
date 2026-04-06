<?php

namespace Database\Seeders;

use App\Models\TelegramSchedule;
use Illuminate\Database\Seeder;

class TelegramScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['report_type' => 'daily',   'is_enabled' => false, 'time' => '18:00', 'day_of_week' => null, 'day_of_month' => null],
            ['report_type' => 'weekly',  'is_enabled' => false, 'time' => '08:00', 'day_of_week' => 1,    'day_of_month' => null],
            ['report_type' => 'monthly', 'is_enabled' => false, 'time' => '08:00', 'day_of_week' => null, 'day_of_month' => 1],
        ];

        foreach ($defaults as $default) {
            TelegramSchedule::firstOrCreate(
                ['report_type' => $default['report_type']],
                $default
            );
        }
    }
}
