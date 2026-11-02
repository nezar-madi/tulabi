<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per student and class: asking again after a rejection or withdrawal
        // reuses the row, so an accepted student's fee carries over (as in rqmana).
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            // Nullable: a deleted account leaves its enrollments (and later payments) without a person.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->index(); // requested, accepted, rejected, canceled, withdrawn
            // Copied from the class price when first accepted; never changed by a price edit.
            $table->decimal('fee', 12, 3)->nullable();
            $table->string('currency_code', 3)->default('LYD');
            $table->string('note', 500)->nullable(); // from the student, with the request
            $table->string('decision_reason', 500)->nullable(); // from the instructor, on reject or remove
            $table->timestamp('requested_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['class_id', 'user_id']);
            $table->index(['class_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
