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
        Schema::create('student_assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_term_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('score', 5, 2);
            $table->timestamps();

            $table->unique(['student_profile_id', 'assessment_item_id'], 'student_assessment_score_unique');
            $table->index(['school_id', 'class_term_subject_id', 'assessment_group_id'], 'student_assessment_score_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_assessment_scores');
    }
};
