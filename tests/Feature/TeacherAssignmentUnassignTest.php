<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassGroup;
use App\Models\ClassTermSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeacherClassArm;
use App\Models\TeacherClassTermSubject;
use App\Models\TeacherProfile;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAssignmentUnassignTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_super_admin_can_unassign_class_arm_and_subject(): void
    {
        [$admin, $school] = $this->createSchoolSuperAdmin();
        [$classArmAssignment, $subjectAssignment] = $this->createAssignments($school);

        $this->actingAs($admin)
            ->delete(route('school.teachers.class-arms.destroy', $classArmAssignment))
            ->assertRedirect()
            ->assertSessionHas('status', 'Teacher unassigned from class arm.');

        $this->actingAs($admin)
            ->delete(route('school.teachers.subjects.destroy', $subjectAssignment))
            ->assertRedirect()
            ->assertSessionHas('status', 'Teacher unassigned from subject.');

        $this->assertModelMissing($classArmAssignment);
        $this->assertModelMissing($subjectAssignment);
    }

    public function test_school_super_admin_cannot_unassign_another_schools_records(): void
    {
        [$admin] = $this->createSchoolSuperAdmin();
        [, $otherSchool] = $this->createSchoolSuperAdmin();
        [$classArmAssignment, $subjectAssignment] = $this->createAssignments($otherSchool);

        $this->actingAs($admin)
            ->delete(route('school.teachers.class-arms.destroy', $classArmAssignment))
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('school.teachers.subjects.destroy', $subjectAssignment))
            ->assertNotFound();

        $this->assertModelExists($classArmAssignment);
        $this->assertModelExists($subjectAssignment);
    }

    /** @return array{TeacherClassArm, TeacherClassTermSubject} */
    private function createAssignments(School $school): array
    {
        $teacherUser = User::factory()->create();
        $teacher = TeacherProfile::query()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'staff_no' => 'T-'.$school->id,
        ]);
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'class_group_id' => $classGroup->id]);
        $classArm = ClassArm::factory()->create(['school_id' => $school->id, 'school_class_id' => $schoolClass->id]);
        $session = AcademicSession::factory()->create(['school_id' => $school->id]);
        $term = Term::factory()->create(['school_id' => $school->id, 'academic_session_id' => $session->id]);
        $subject = Subject::factory()->create(['name' => 'Mathematics '.$school->id, 'status' => 'active']);
        $schoolSubject = $school->schoolSubjects()->create(['subject_id' => $subject->id, 'is_active' => true]);
        $classTermSubject = ClassTermSubject::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'class_arm_id' => $classArm->id,
            'school_subject_id' => $schoolSubject->id,
        ]);

        $classArmAssignment = $school->teacherClassArms()->create([
            'teacher_profile_id' => $teacher->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'class_arm_id' => $classArm->id,
        ]);
        $subjectAssignment = $school->teacherClassTermSubjects()->create([
            'teacher_profile_id' => $teacher->id,
            'class_term_subject_id' => $classTermSubject->id,
        ]);

        return [$classArmAssignment, $subjectAssignment];
    }

    /** @return array{User, School} */
    private function createSchoolSuperAdmin(): array
    {
        $user = User::factory()->create();
        $school = School::factory()->approved()->create(['owner_user_id' => $user->id]);
        $school->users()->create(['user_id' => $user->id, 'role' => 'school_super_admin', 'status' => 'active']);
        $user->update(['current_school_id' => $school->id]);

        return [$user, $school];
    }
}
