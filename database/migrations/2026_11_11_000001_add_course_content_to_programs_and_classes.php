<?php

use App\Support\CourseContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A program and a class share one content shape: a description (plain text) and three
        // bullet lists (what the student will learn, requirements, who it is for), plus an
        // optional promotional video (a YouTube, Vimeo or Dailymotion link).
        foreach (['programs', 'classes'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('learn')->nullable();
                $table->json('requirements')->nullable();
                $table->json('audience')->nullable();
                $table->string('promo_video_url', 500)->nullable();
            });
        }

        Schema::table('classes', function (Blueprint $table) {
            // «تعليق البدء تلقائيًا»: when the class reaches its start date with fewer accepted
            // students than min_seats it becomes «suspended» until a new start date is chosen.
            $table->boolean('auto_suspend')->default(false);
            $table->unsignedInteger('min_seats')->nullable();
            $table->timestamp('suspended_at')->nullable();
        });

        // Descriptions used to be rich text; they are plain text now.
        foreach (['programs', 'classes'] as $table) {
            DB::table($table)->select(['id', 'description'])->orderBy('id')->each(function ($row) use ($table) {
                DB::table($table)->where('id', $row->id)->update([
                    'description' => CourseContent::htmlToText((string) $row->description),
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['auto_suspend', 'min_seats', 'suspended_at']);
        });

        foreach (['programs', 'classes'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['learn', 'requirements', 'audience', 'promo_video_url']);
            });
        }
    }
};
