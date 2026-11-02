<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            // HTML, always through App\Support\SafeHtml::description() before saving.
            $table->text('description')->nullable();
            $table->string('type', 20); // in_person, online
            $table->string('location', 255)->nullable();
            // Shown only to accepted students (phase 3), never on public pages.
            $table->string('meeting_url', 500)->nullable();
            // Calendar dates in Libya (DATE columns: no time, so no time zone shift).
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            // Weekdays as Carbon numbers (0 = Sunday ... 6 = Saturday).
            $table->json('days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('price', 12, 3)->default(0);
            $table->string('currency_code', 3)->default('LYD');
            // A warning only: the instructor may accept more students on purpose.
            $table->unsignedInteger('seats')->nullable();
            $table->string('status', 20)->default('draft')->index(); // draft, published, closed, canceled
            $table->timestamp('published_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['instructor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
