<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRolloverTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_promote_and_graduate_students_during_rollover(): void
    {
        $admin = User::factory()->create();
        $school = School::factory()->approved()->create(['owner_user_id' => $admin->id]);
        $school->users()->create(['user_id' => $admin->id, 'role' => 'school_super_admin', 'status' => 'active']);
        $admin->update(['current_school_id' => $school->id]);
        $group = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Primary']);
        $firstClass = SchoolClass::factory()->create(['school_id' => $school->id, 'class_group_id' => $group->id, 'name' => 'Primary 1']);
        $secondClass = SchoolClass::factory()->create(['school_id' => $school->id, 'class_group_id' => $group->id, 'name' => 'Primary 2']);
        $sourceArm = ClassArm::factory()->create(['school_id' => $school->id, 'school_class_id' => $firstClass->id, 'name' => 'Primary 1A']);
        $targetArm = ClassArm::factory()->create(['school_id' => $school->id, 'school_class_id' => $secondClass->id, 'name' => 'Primary 2A']);
        $sourceSession = AcademicSession::factory()->create(['school_id' => $school->id, 'name' => '2026/2027']);
        $targetSession = AcademicSession::factory()->create(['school_id' => $school->id, 'name' => '2027/2028']);
        $targetTerm = Term::factory()->create(['school_id' => $school->id, 'academic_session_id' => $targetSession->id, 'name' => 'First Term']);
        $promoted = StudentProfile::factory()->create([
            'school_id' => $school->id, 'admission_no' => 'ADM-001',
            'first_name' => 'Ayo', 'last_name' => 'Mensah', 'status' => 'active',
        ]);
        $graduated = StudentProfile::factory()->create([
            'school_id' => $school->id, 'admission_no' => 'ADM-002',
            'first_name' => 'Sade', 'last_name' => 'Lawal', 'status' => 'active',
        ]);

        foreach ([$promoted, $graduated] as $student) {
            $school->studentEnrollments()->create([
                'student_profile_id' => $student->id, 'academic_session_id' => $sourceSession->id,
                'class_arm_id' => $sourceArm->id, 'status' => 'active',
            ]);
        }

        $this->actingAs($admin)->post(route('school.students.rollover.store'), [
            'source_session_id' => $sourceSession->id,
            'target_session_id' => $targetSession->id,
            'target_term_id' => $targetTerm->id,
            'students' => [
                ['student_profile_id' => $promoted->id, 'decision' => 'promoted', 'target_class_arm_id' => $targetArm->id],
                ['student_profile_id' => $graduated->id, 'decision' => 'graduated', 'target_class_arm_id' => null],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_enrollments', [
            'student_profile_id' => $promoted->id, 'academic_session_id' => $targetSession->id,
            'term_id' => $targetTerm->id, 'class_arm_id' => $targetArm->id,
        ]);
        $this->assertDatabaseHas('student_profiles', ['id' => $graduated->id, 'status' => 'graduated']);
        $this->assertDatabaseMissing('student_enrollments', ['student_profile_id' => $graduated->id, 'academic_session_id' => $targetSession->id]);
    }
}
