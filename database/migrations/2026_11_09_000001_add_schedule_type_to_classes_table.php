<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // How the course meets (as rqmana's days of study): daily, on chosen weekdays (`days`),
            // or on dates picked by hand (`manual_dates`, a list of Y-m-d).
            $table->string('schedule_type', 10)->default('weekly')->after('ends_on');
            $table->json('manual_dates')->nullable()->after('days');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['schedule_type', 'manual_dates']);
        });
    }
};
