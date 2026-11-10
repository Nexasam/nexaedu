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
        Schema::create('teacher_class_arms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('class_arm_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_profile_id', 'academic_session_id', 'term_id', 'class_arm_id'], 'teacher_class_arm_unique');
            $table->index(['school_id', 'teacher_profile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_class_arms');
    }
};
