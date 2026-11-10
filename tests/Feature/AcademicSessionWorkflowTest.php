<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AssessmentGroup;
use App\Models\ClassArm;
use App\Models\ClassGroup;
use App\Models\ClassTermSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentAssessmentScore;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicSessionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_duplicate_session_structure_without_scores(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();
        [$session, $term, $classTermSubject, $assessmentGroup, $student] = $this->createSessionStructure($school);
        $item = $assessmentGroup->items()->create(['school_id' => $school->id, 'name' => 'Exam', 'max_score' => 100, 'sort_order' => 1]);
        StudentAssessmentScore::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'class_term_subject_id' => $classTermSubject->id,
            'assessment_group_id' => $assessmentGroup->id,
            'assessment_item_id' => $item->id,
            'recorded_by' => $user->id,
            'score' => 80,
        ]);

        $this->actingAs($user)->post(route('school.academic-sessions.duplicate', $session), [
            'name' => '2027/2028',
            'starts_on' => '2027-09-01',
            'ends_on' => '2028-07-31',
            'copy_enrollments' => true,
            'copy_teacher_assignments' => true,
            'copy_assessment_flows' => true,
        ])->assertRedirect();

        $newSession = AcademicSession::query()->where('school_id', $school->id)->where('name', '2027/2028')->firstOrFail();
        $newTerm = Term::query()->whereBelongsTo($newSession)->where('name', $term->name)->firstOrFail();
        $newSubject = ClassTermSubject::query()->whereBelongsTo($newSession)->whereBelongsTo($newTerm)->firstOrFail();
        $newGroup = AssessmentGroup::query()->where('class_term_subject_id', $newSubject->id)->firstOrFail();

        $this->assertDatabaseHas('student_enrollments', ['student_profile_id' => $student->id, 'academic_session_id' => $newSession->id, 'term_id' => $newTerm->id]);
        $this->assertDatabaseHas('assessment_items', ['assessment_group_id' => $newGroup->id, 'name' => 'Exam', 'max_score' => 100]);
        $this->assertDatabaseMissing('student_assessment_scores', ['assessment_group_id' => $newGroup->id]);
        $this->assertNull($newGroup->completed_at);
    }

    public function test_super_admin_cannot_duplicate_another_schools_session(): void
    {
        [$user] = $this->createSchoolSuperAdmin();
        [, $otherSchool] = $this->createSchoolSuperAdmin();
        $otherSession = AcademicSession::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($user)->post(route('school.academic-sessions.duplicate', $otherSession), [
            'name' => '2028/2029',
        ])->assertForbidden();
    }

    /** @return array{AcademicSession, Term, ClassTermSubject, AssessmentGroup, StudentProfile} */
    private function createSessionStructure(School $school): array
    {
        $group = ClassGroup::factory()->create(['school_id' => $school->id]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'class_group_id' => $group->id]);
        $arm = ClassArm::factory()->create(['school_id' => $school->id, 'school_class_id' => $class->id]);
        $session = AcademicSession::factory()->create(['school_id' => $school->id, 'name' => '2026/2027', 'starts_on' => '2026-09-01']);
        $term = Term::factory()->create(['school_id' => $school->id, 'academic_session_id' => $session->id, 'name' => 'First Term']);
        $subject = Subject::factory()->create(['name' => 'Mathematics', 'status' => 'active']);
        $schoolSubject = $school->schoolSubjects()->create(['subject_id' => $subject->id, 'is_active' => true]);
        $classTermSubject = ClassTermSubject::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'class_arm_id' => $arm->id,
            'school_subject_id' => $schoolSubject->id,
        ]);
        $assessmentGroup = AssessmentGroup::factory()->create(['school_id' => $school->id, 'class_term_subject_id' => $classTermSubject->id, 'name' => 'Default', 'total_score' => 100]);
        $student = StudentProfile::factory()->create(['school_id' => $school->id, 'admission_no' => 'ADM-001', 'first_name' => 'Ayo', 'last_name' => 'Mensah', 'status' => 'active']);
        $school->studentEnrollments()->create([
            'student_profile_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'class_arm_id' => $arm->id,
            'status' => 'active',
        ]);

        return [$session, $term, $classTermSubject, $assessmentGroup, $student];
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
