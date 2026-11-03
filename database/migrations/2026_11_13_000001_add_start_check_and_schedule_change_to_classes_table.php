<?php

use App\Support\TulabiClock;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // «تعليق البدء تلقائيًا» is checked once, when the class reaches its start date.
            $table->timestamp('start_checked_at')->nullable()->after('suspended_at');
            // When the dates, hours or place last changed (accepted students see it for a few days).
            $table->timestamp('schedule_changed_at')->nullable()->after('meeting_url_changed_at');
        });

        // Classes that have already started were checked (or never needed it): they are never suspended now.
        DB::table('classes')
            ->where('starts_on', '<', TulabiClock::today())
            ->update(['start_checked_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['start_checked_at', 'schedule_changed_at']);
        });
    }
};
