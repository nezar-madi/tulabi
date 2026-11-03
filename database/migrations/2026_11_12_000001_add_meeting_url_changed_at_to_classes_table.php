<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // When the instructor last changed the meeting link (students see «تم التحديث» for a few days).
            $table->timestamp('meeting_url_changed_at')->nullable()->after('meeting_url');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('meeting_url_changed_at');
        });
    }
};
