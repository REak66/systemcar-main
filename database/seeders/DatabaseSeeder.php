<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default admin — change the password immediately after first login
        \App\Models\User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name'       => 'Administrator',
                'email'      => 'admin@car.local',
                'password'   => \Illuminate\Support\Facades\Hash::make('Admin@123'),
                'role'       => 'admin',
                'is_active'  => true,
            ]
        );

        $this->call(TelegramScheduleSeeder::class);
    }
}
