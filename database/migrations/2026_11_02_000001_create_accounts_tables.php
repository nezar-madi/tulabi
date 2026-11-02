<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin accounts are separate from users and always use a TOTP second factor.
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->unsignedBigInteger('two_factor_last_step')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // A user becomes an instructor when the admin accepts their application.
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            // Chosen by the instructor in phase 2 (their page is /{slug}).
            $table->string('slug', 40)->nullable()->unique();
            $table->string('display_name');
            $table->string('specialty', 120);
            $table->string('city', 80);
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->json('links')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('instructor_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('specialty', 120);
            $table->string('city', 80);
            $table->text('bio')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // One-time links to set a new password: issued by the admin, an instructor (for their
        // students) or sent by email. Only the hash of the token is kept.
        Schema::create('password_reset_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('issued_by_type', 20); // admin, instructor, email
            $table->unsignedBigInteger('issued_by_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('password_reset_links');
        Schema::dropIfExists('instructor_applications');
        Schema::dropIfExists('instructors');
        Schema::dropIfExists('admins');
    }
};
