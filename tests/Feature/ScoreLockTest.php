<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AssessmentGroup;
use App\Models\ClassArm;
use App\Models\ClassGroup;
use App\Models\ClassTermSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\Schools\AcademicSessionScoreLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScoreLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_without_lock_value_defaults_to_unlocked(): void
    {
        $session = AcademicSession::factory()->create();
        $attributes = collect($session->getAttributes())->except('score_entries_locked')->all();
        $session->setRawAttributes($attributes, true);

        $this->assertFalse(app(AcademicSessionScoreLock::class)->isLocked($session));
    }

    public function test_scheduled_lock_and_unlock_times_control_effective_status(): void
    {
        $session = AcademicSession::factory()->create([
            'score_entries_locked' => false,
            'score_lock_at' => now()->subMinute(),
            'score_unlock_at' => now()->addMinute(),
        ]);

        $this->assertTrue(app(AcademicSessionScoreLock::class)->isLocked($session));

        $session->update(['score_unlock_at' => now()->subSecond()]);

        $this->assertFalse(app(AcademicSessionScoreLock::class)->isLocked($session->refresh()));
    }

    public function test_teacher_cannot_save_scores_when_session_is_locked(): void
    {
        [$teacher, $school, $group, $item, $student] = $this->createLockedScoreSheet();

        $this->actingAs($teacher)->post(route('school.scores.store'), [
            'assessment_group_id' => $group->id,
            'scores' => [[
                'student_profile_id' => $student->id,
                'items' => [['assessment_item_id' => $item->id, 'score' => 75]],
            ]],
        ])->assertSessionHasErrors('scores');

        $this->assertDatabaseMissing('student_assessment_scores', [
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'assessment_item_id' => $item->id,
        ]);
    }

    public function test_teacher_can_save_multiple_subject_score_sheets_in_one_request(): void
    {
        [$teacher, $school, $firstGroup, $firstItem, $student] = $this->createLockedScoreSheet();
        $firstGroup->classTermSubject->academicSession->update(['score_entries_locked' => false]);

        $secondSubject = Subject::factory()->create(['name' => 'English Language', 'status' => 'active']);
        $secondSchoolSubject = $school->schoolSubjects()->create(['subject_id' => $secondSubject->id, 'is_active' => true]);
        $secondClassTermSubject = ClassTermSubject::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $firstGroup->classTermSubject->academic_session_id,
            'term_id' => $firstGroup->classTermSubject->term_id,
            'class_arm_id' => $firstGroup->classTermSubject->class_arm_id,
            'school_subject_id' => $secondSchoolSubject->id,
        ]);
        $secondGroup = AssessmentGroup::factory()->create([
            'school_id' => $school->id,
            'class_term_subject_id' => $secondClassTermSubject->id,
            'name' => 'Default',
            'total_score' => 100,
        ]);
        $secondItem = $secondGroup->items()->create([
            'school_id' => $school->id,
            'name' => 'Exam',
            'max_score' => 100,
            'sort_order' => 1,
        ]);

        $this->actingAs($teacher)->post(route('school.scores.bulk-store'), [
            'score_sheets' => [
                [
                    'assessment_group_id' => $firstGroup->id,
                    'scores' => [[
                        'student_profile_id' => $student->id,
                        'items' => [['assessment_item_id' => $firstItem->id, 'score' => 72]],
                    ]],
                ],
                [
                    'assessment_group_id' => $secondGroup->id,
                    'scores' => [[
                        'student_profile_id' => $student->id,
                        'items' => [['assessment_item_id' => $secondItem->id, 'score' => 84]],
                    ]],
                ],
            ],
        ])->assertRedirect()->assertSessionHas('status', '2 subject score sheets saved.');

        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_group_id' => $firstGroup->id,
            'assessment_item_id' => $firstItem->id,
            'student_profile_id' => $student->id,
            'score' => 72,
        ]);
        $this->assertDatabaseHas('student_assessment_scores', [
            'assessment_group_id' => $secondGroup->id,
            'assessment_item_id' => $secondItem->id,
            'student_profile_id' => $student->id,
            'score' => 84,
        ]);
    }

    public function test_teacher_can_download_and_import_a_filtered_score_template(): void
    {
        [$teacher, $school, $group, $item, $student] = $this->createLockedScoreSheet();
        $group->classTermSubject->academicSession->update(['score_entries_locked' => false]);

        $download = $this->actingAs($teacher)->get(route('school.scores.bulk-template', [
            'assessment_group_ids' => [$group->id],
            'student_profile_ids' => [$student->id],
        ]));

        $download->assertOk();
        $download->assertHeader('content-disposition', 'attachment; filename=filtered-score-entry-template.csv');
        $this->assertStringContainsString('assessment_group_id,student_profile_id,admission_no', $download->streamedContent());
        $this->assertStringContainsString('Exam / 100 [item:'.$item->id.']', $download->streamedContent());

        $csv = implode(',', [
            'assessment_group_id',
            'student_profile_id',
            'admission_no',
            'student_name',
            'class_arm',
            'subject',
            'academic_session',
            'term',
            'Exam / 100 [item:'.$item->id.']',
        ])."\n".implode(',', [
            $group->id,
            $student->id,
            $student->admission_no,
            'Ayo Mensah',
            'Primary 1A',
            'Mathematics',
            '2026/2027',
            'First Term',
            '91',
        ])."\n";

        $this->actingAs($teacher)->post(route('school.scores.bulk-import'), [
            'assessment_group_ids' => [$group->id],
            'score_file' => UploadedFile::fake()->createWithContent('filtered-scores.csv', $csv),
        ])->assertRedirect()->assertSessionHas('status', '1 subject score sheets imported.');

        $this->assertDatabaseHas('student_assessment_scores', [
            'school_id' => $school->id,
            'assessment_group_id' => $group->id,
            'student_profile_id' => $student->id,
            'assessment_item_id' => $item->id,
            'score' => 91,
        ]);
    }

    private function createLockedScoreSheet(): array
    {
        $teacher = User::factory()->create();
        $school = School::factory()->approved()->create();
        $role = $school->roles()->create(['name' => 'Teacher', 'slug' => 'teacher', 'is_system' => true]);
        $role->permissions()->create(['module' => 'score_entry', 'access_level' => 'readwrite']);
        $school->users()->create(['user_id' => $teacher->id, 'role' => 'teacher', 'school_role_id' => $role->id, 'status' => 'active']);
        $teacher->update(['current_school_id' => $school->id]);

        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);
        $class = SchoolClass::factory()->create(['school_id' => $school->id, 'class_group_id' => $classGroup->id]);
        $arm = ClassArm::factory()->create(['school_id' => $school->id, 'school_class_id' => $class->id]);
        $session = AcademicSession::factory()->create(['school_id' => $school->id, 'score_entries_locked' => true]);
        $term = Term::factory()->create(['school_id' => $school->id, 'academic_session_id' => $session->id]);
        $subject = Subject::factory()->create(['name' => 'Mathematics', 'status' => 'active']);
        $schoolSubject = $school->schoolSubjects()->create(['subject_id' => $subject->id, 'is_active' => true]);
        $classTermSubject = ClassTermSubject::factory()->create(['school_id' => $school->id, 'academic_session_id' => $session->id, 'term_id' => $term->id, 'class_arm_id' => $arm->id, 'school_subject_id' => $schoolSubject->id]);
        $group = AssessmentGroup::factory()->create(['school_id' => $school->id, 'class_term_subject_id' => $classTermSubject->id, 'name' => 'Default', 'total_score' => 100]);
        $item = $group->items()->create(['school_id' => $school->id, 'name' => 'Exam', 'max_score' => 100, 'sort_order' => 1]);
        $student = StudentProfile::factory()->create(['school_id' => $school->id, 'admission_no' => 'ADM-001', 'first_name' => 'Ayo', 'last_name' => 'Mensah', 'status' => 'active']);
        $school->studentEnrollments()->create(['student_profile_id' => $student->id, 'academic_session_id' => $session->id, 'term_id' => $term->id, 'class_arm_id' => $arm->id, 'status' => 'active']);

        return [$teacher, $school, $group, $item, $student];
    }
}
