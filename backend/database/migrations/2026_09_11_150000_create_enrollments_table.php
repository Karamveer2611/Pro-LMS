<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('manual');
            $table->timestamp('enrolled_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Backstop for the batch-based case; the null-batch case (where
            // SQL NULLs aren't unique-enforced) is checked in
            // EnrollmentService before insert — see that class for why.
            $table->unique(['user_id', 'course_id', 'batch_id']);
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
