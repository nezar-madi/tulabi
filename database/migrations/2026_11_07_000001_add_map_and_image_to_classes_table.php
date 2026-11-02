<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // A Google Maps link, required for in-person classes (the written place is optional).
            $table->string('map_url', 500)->nullable()->after('location');
            // The promotional image, saved again by App\Support\ClassImage.
            $table->string('image_path')->nullable()->after('meeting_url');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['map_url', 'image_path']);
        });
    }
};
