<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_configs', function (Blueprint $table) {
            // Add the four per-group chat IDs
            $table->string('daily_chat_id')->nullable()->after('name');
            $table->string('weekly_chat_id')->nullable()->after('daily_chat_id');
            $table->string('monthly_chat_id')->nullable()->after('weekly_chat_id');
            $table->string('document_chat_id')->nullable()->after('monthly_chat_id');
        });

        // Migrate existing single chat_id into document_chat_id
        if (Schema::hasColumn('telegram_configs', 'chat_id')) {
            \DB::statement('UPDATE telegram_configs SET document_chat_id = chat_id WHERE chat_id IS NOT NULL AND chat_id != \'\'');
        }

        Schema::table('telegram_configs', function (Blueprint $table) {
            // Remove the old single-purpose columns
            if (Schema::hasColumn('telegram_configs', 'bot_token')) {
                $table->dropColumn('bot_token');
            }
            if (Schema::hasColumn('telegram_configs', 'chat_id')) {
                $table->dropColumn('chat_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_configs', function (Blueprint $table) {
            $table->string('bot_token')->nullable()->after('name');
            $table->string('chat_id')->nullable()->after('bot_token');
        });

        // Restore chat_id from document_chat_id
        \DB::statement('UPDATE telegram_configs SET chat_id = document_chat_id WHERE document_chat_id IS NOT NULL AND document_chat_id != \'\'');

        Schema::table('telegram_configs', function (Blueprint $table) {
            $table->dropColumn(['daily_chat_id', 'weekly_chat_id', 'monthly_chat_id', 'document_chat_id']);
        });
    }
};
