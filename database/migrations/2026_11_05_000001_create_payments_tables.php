<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per instructor: the last receipt number issued on the last day used.
        // It only moves forward, so a voided receipt's number is never issued again.
        Schema::create('receipt_counters', function (Blueprint $table) {
            $table->foreignId('instructor_id')->primary()->constrained()->cascadeOnDelete();
            $table->char('day', 8)->nullable(); // Ymd in Libya
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });

        // Payments are voided, never deleted (as in rqmana).
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 3);
            $table->string('currency_code', 3);
            // The UTC moment of local midnight of the date the instructor typed (TulabiDateTime).
            $table->timestamp('paid_at');
            $table->string('method', 20); // cash, transfer, other; later dpay, mypay
            // For electronic payments later (DPay / MyPay): empty for now.
            $table->string('provider', 20)->nullable();
            $table->string('provider_reference', 100)->nullable();
            $table->string('receipt_number', 20); // TL-YYYYMMDD-NNNN, per instructor
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['instructor_id', 'receipt_number']);
            $table->index(['instructor_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('receipt_counters');
    }
};
