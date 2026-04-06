<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('report_type', ['daily', 'weekly', 'monthly'])->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('time', 5)->default('08:00');
            $table->tinyInteger('day_of_week')->nullable();   // 0=Sun … 6=Sat (Laravel scheduler convention)
            $table->tinyInteger('day_of_month')->nullable();  // 1–31
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_schedules');
    }
};
