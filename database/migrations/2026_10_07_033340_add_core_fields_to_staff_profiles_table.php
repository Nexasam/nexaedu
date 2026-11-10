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
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('middle_name')->nullable()->after('last_name');
            $table->string('qualification')->nullable()->after('gender');
            $table->string('specialization')->nullable()->after('qualification');
            $table->string('department')->nullable()->after('specialization');
            $table->string('job_title')->nullable()->after('department');
            $table->text('address')->nullable()->after('job_title');
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('employment_type')->nullable()->after('employment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'middle_name',
                'qualification',
                'specialization',
                'department',
                'job_title',
                'address',
                'emergency_contact_name',
                'emergency_contact_phone',
                'employment_type',
            ]);
        });
    }
};
