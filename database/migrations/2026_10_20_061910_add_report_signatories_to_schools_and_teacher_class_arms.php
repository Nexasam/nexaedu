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
        Schema::table('schools', function (Blueprint $table) {
            $table->foreignId('principal_staff_profile_id')->nullable()->after('owner_user_id')->constrained('staff_profiles')->nullOnDelete();
        });

        Schema::table('teacher_class_arms', function (Blueprint $table) {
            $table->boolean('is_class_teacher')->default(false)->after('class_arm_id');
            $table->index(['school_id', 'class_arm_id', 'academic_session_id', 'term_id', 'is_class_teacher'], 'teacher_arm_signatory_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_class_arms', function (Blueprint $table) {
            $table->dropIndex('teacher_arm_signatory_idx');
            $table->dropColumn('is_class_teacher');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropConstrainedForeignId('principal_staff_profile_id');
        });
    }
};
