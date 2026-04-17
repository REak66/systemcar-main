<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1 – add nullable FK column
        Schema::table('telegram_schedules', function (Blueprint $table) {
            $table->foreignId('telegram_config_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('telegram_configs')
                  ->cascadeOnDelete();
        });

        // Step 2 – assign orphan rows to the first existing config (if any)
        $firstConfig = DB::table('telegram_configs')->orderBy('id')->first();
        if ($firstConfig) {
            DB::table('telegram_schedules')
               ->whereNull('telegram_config_id')
               ->update(['telegram_config_id' => $firstConfig->id]);
        } else {
            // No configs yet – delete orphan global schedules
            DB::table('telegram_schedules')->whereNull('telegram_config_id')->delete();
        }

        // Step 3 – drop the unique constraint so each branch can have daily/weekly/monthly
        Schema::table('telegram_schedules', function (Blueprint $table) {
            try {
                $table->dropUnique('telegram_schedules_report_type_unique');
            } catch (\Throwable) {
                // Index may already be absent (SQLite, etc.)
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_schedules', function (Blueprint $table) {
            $table->dropForeign(['telegram_config_id']);
            $table->dropColumn('telegram_config_id');
        });

        // Restore uniqueness best-effort (may fail if duplicates exist)
        try {
            Schema::table('telegram_schedules', function (Blueprint $table) {
                $table->unique('report_type');
            });
        } catch (\Throwable) {}
    }
};
