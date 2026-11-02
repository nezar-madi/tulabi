<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // «البرامج التدريبية» (as rqmana's): the instructor's catalogue. A course may belong to one program.
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            // HTML, always through App\Support\SafeHtml::description() before saving.
            $table->longText('description');
            // The poster, saved again by App\Support\ClassImage.
            $table->string('image_path')->nullable();
            // A stopped program takes no new courses; a published one is shown on the instructor's page.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->unique(['instructor_id', 'name']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('instructor_id')->constrained('programs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
        });

        Schema::dropIfExists('programs');
    }
};
