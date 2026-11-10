<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_card_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('school_days_opened')->nullable();
            $table->unsignedSmallInteger('days_present')->nullable();
            $table->unsignedSmallInteger('days_absent')->nullable();
            $table->unsignedSmallInteger('times_late')->nullable();
            $table->text('class_teacher_comment')->nullable();
            $table->text('principal_comment')->nullable();
            $table->string('promotion_decision')->default('pending');
            $table->date('next_term_starts_on')->nullable();
            $table->json('calculated_snapshot')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['report_card_run_id', 'student_profile_id'], 'src_run_student_unique');
            $table->index(['school_id', 'student_profile_id', 'status'], 'src_school_student_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_report_cards');
    }
};
