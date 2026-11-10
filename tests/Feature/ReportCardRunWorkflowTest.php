<?php

namespace Tests\Feature;

use App\Actions\Schools\ApplyAssessmentFlowAction;
use App\Actions\Schools\GenerateReportCardRunAction;
use App\Actions\Schools\UpdateStudentReportCardAction;
use App\Models\AcademicSession;
use App\Models\AssessmentGroup;
use App\Models\ClassArm;
use App\Models\ClassGroup;
use App\Models\ClassTermSubject;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StaffProfile;
use App\Models\StudentAssessmentScore;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportCardRunWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_draft_cards_with_weighted_subject_results_for_active_students(): void
    {
        [$user, $school, $session, $term, $classArm, $template, $student] = $this->createReportContext();

        $run = app(GenerateReportCardRunAction::class)->handle(
            $school,
            $template,
            $session,
            $term,
            $classArm,
            $user,
        );

        $this->assertDatabaseHas('report_card_runs', [
            'id' => $run->id,
            'school_id' => $school->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('student_report_cards', [
            'report_card_run_id' => $run->id,
            'student_profile_id' => $student->id,
            'status' => 'draft',
        ]);

        $card = $run->cards()->firstOrFail();

        $this->assertSame(80, $card->calculated_snapshot['summary']['average']);
        $this->assertSame('A', $card->calculated_snapshot['subjects'][0]['grade']);
        $this->assertSame('Mathematics', $card->calculated_snapshot['subjects'][0]['name']);
        $this->assertSame('Exam', $card->calculated_snapshot['subjects'][0]['assessment_items'][0]['name']);
        $this->assertSame(80, $card->calculated_snapshot['subjects'][0]['class_statistics']['class_average']);
        $this->assertSame(1, $card->calculated_snapshot['subjects'][0]['position']);
    }

    public function test_prepares_a_student_card_and_records_the_change(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);
        $card = $run->cards()->firstOrFail();

        app(UpdateStudentReportCardAction::class)->handle($school, $run, $card, [
            'school_days_opened' => 65,
            'days_present' => 61,
            'days_absent' => 4,
            'times_late' => 2,
            'class_teacher_comment' => 'A focused and consistent student.',
            'principal_comment' => 'Keep up the good work.',
            'promotion_decision' => 'promoted',
            'next_term_starts_on' => '2027-01-11',
        ], $user);

        $this->assertDatabaseHas('student_report_cards', [
            'id' => $card->id,
            'status' => 'prepared',
            'school_days_opened' => 65,
            'promotion_decision' => 'promoted',
        ]);
        $this->assertDatabaseHas('report_card_events', [
            'report_card_run_id' => $run->id,
            'student_report_card_id' => $card->id,
            'event' => 'prepared',
        ]);
    }

    public function test_prepared_card_snapshots_configured_staff_signatures(): void
    {
        Storage::fake('local');
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $teacherStaff = StaffProfile::factory()->create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'first_name' => 'Ada',
            'last_name' => 'Okafor',
            'job_title' => 'Class Teacher',
            'role' => 'teacher',
            'status' => 'active',
        ]);
        $teacher = $school->teacherProfiles()->create(['staff_profile_id' => $teacherStaff->id, 'user_id' => $user->id, 'staff_no' => 'T-001']);

        $this->actingAs($user)->post(route('profile.signature'), [
            'signature' => UploadedFile::fake()->image('signature.png', 300, 100),
        ])->assertRedirect();

        $teacherStaff->refresh();
        $school->update(['principal_staff_profile_id' => $teacherStaff->id]);
        $school->teacherClassArms()->create([
            'teacher_profile_id' => $teacher->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'class_arm_id' => $classArm->id,
            'is_class_teacher' => true,
        ]);
        $run = app(GenerateReportCardRunAction::class)->handle($school->refresh(), $template, $session, $term, $classArm, $user);
        $card = $run->cards()->firstOrFail();

        $updatedCard = app(UpdateStudentReportCardAction::class)->handle($school->refresh(), $run, $card, [
            'school_days_opened' => 65,
            'days_present' => 61,
            'days_absent' => 4,
            'times_late' => 2,
            'promotion_decision' => 'promoted',
        ], $user);

        $this->assertSame('Ada Okafor', data_get($updatedCard->signature_snapshot, 'class_teacher.name'));
        $this->assertSame('Ada Okafor', data_get($updatedCard->signature_snapshot, 'principal.name'));
        $this->assertNotEmpty(data_get($updatedCard->signature_snapshot, 'class_teacher.sha256'));
        $this->actingAs($user)
            ->get(route('school.report-cards.signature', [$updatedCard, 'class_teacher']))
            ->assertOk();
    }

    public function test_keeps_raw_score_limits_separate_from_report_weights(): void
    {
        [, $school, $session, $term, $classArm] = $this->createReportContext();
        $classTermSubject = ClassTermSubject::query()->whereBelongsTo($school)->firstOrFail();

        app(ApplyAssessmentFlowAction::class)->handle($school, collect([$classTermSubject->id]), [
            'name' => 'Weighted assessment',
            'items' => [
                ['name' => 'CA', 'max_score' => 200, 'raw_max_score' => 200, 'weight' => 20],
                ['name' => 'Exam', 'max_score' => 100, 'raw_max_score' => 100, 'weight' => 80],
            ],
        ]);

        $item = AssessmentGroup::query()
            ->whereBelongsTo($school)
            ->where('class_term_subject_id', $classTermSubject->id)
            ->latest()
            ->firstOrFail()
            ->items()
            ->where('name', 'CA')
            ->firstOrFail();

        $this->assertSame(200, (int) $item->raw_max_score);
        $this->assertSame(200, (int) $item->max_score);
        $this->assertSame(20, (int) $item->weight);
    }

    public function test_saves_class_group_trait_ratings_on_a_student_card(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $trait = $school->reportCardTraitDefinitions()->create([
            'class_group_id' => $classArm->schoolClass->class_group_id,
            'name' => 'Character',
            'rating_scale' => 'options',
            'rating_options' => ['Excellent', 'Good'],
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);
        $card = $run->cards()->firstOrFail();

        app(UpdateStudentReportCardAction::class)->handle($school, $run, $card, [
            'promotion_decision' => 'pending',
            'ratings' => [['trait_definition_id' => $trait->id, 'rating' => 'Excellent']],
        ], $user);

        $this->assertDatabaseHas('student_report_card_ratings', [
            'student_report_card_id' => $card->id,
            'report_card_trait_definition_id' => $trait->id,
            'rating' => 'Excellent',
        ]);
    }

    public function test_opens_the_class_preview_while_cards_are_still_drafts(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);

        $this->actingAs($user)
            ->get(route('school.report-cards.preview', ['reportCardRun' => $run, 'mode' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SchoolAdmin/ReportCards/Preview')
                ->where('mode', 'all')
                ->where('allPrepared', false)
                ->has('cards', 1)
                ->where('cards.0.status', 'draft'));
    }

    public function test_uses_a_compatible_selected_template_for_preview(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);
        $alternativeTemplate = $school->reportCardTemplates()->create([
            'class_group_id' => $classArm->schoolClass->class_group_id,
            'grading_scheme_id' => $template->grading_scheme_id,
            'name' => 'Alternative report layout',
            'term_result_mode' => 'independent',
        ]);

        $this->actingAs($user)
            ->get(route('school.report-cards.preview', [
                'reportCardRun' => $run,
                'card' => $run->cards()->firstOrFail()->id,
                'template' => $alternativeTemplate->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SchoolAdmin/ReportCards/Preview')
                ->where('mode', 'student')
                ->where('selectedTemplate.id', $alternativeTemplate->id)
                ->has('cards', 1));
    }

    public function test_preview_derives_missing_class_statistics_for_an_existing_report_run(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);
        $card = $run->cards()->firstOrFail();
        $snapshot = $card->calculated_snapshot;
        unset($snapshot['summary']['class_average'], $snapshot['subjects'][0]['class_statistics'], $snapshot['subjects'][0]['position']);
        $card->update(['calculated_snapshot' => $snapshot]);

        $this->actingAs($user)
            ->get(route('school.report-cards.preview', [
                'reportCardRun' => $run,
                'card' => $card->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('cards.0.calculated_snapshot.summary.class_average', 80)
                ->where('cards.0.calculated_snapshot.subjects.0.class_statistics.class_average', 80)
                ->where('cards.0.calculated_snapshot.subjects.0.position', 1));
    }

    public function test_streams_a_student_report_card_as_a_pdf(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);
        $card = $run->cards()->firstOrFail();

        $response = $this->actingAs($user)->get(route('school.report-cards.pdf', [
            'reportCardRun' => $run,
            'card' => $card,
            'template' => $template,
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_school_admin_configures_cumulative_reporting_weights(): void
    {
        [$user, , , , , $template] = $this->createReportContext();

        $this->actingAs($user)->put(route('school.report-card-templates.policy.update', $template), [
            'term_result_mode' => 'cumulative',
            'term_weights' => [20, 30, 50],
        ])->assertRedirect();

        $this->assertSame('cumulative', $template->refresh()->term_result_mode);
        $this->assertSame([20, 30, 50], $template->term_weights);
    }

    public function test_cumulative_report_combines_prior_term_subject_percentages(): void
    {
        [$user, $school, $session, $firstTerm, $classArm, $template, $student] = $this->createReportContext();
        $template->update(['term_result_mode' => 'cumulative', 'term_weights' => [1, 1, 1]]);
        app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $firstTerm, $classArm, $user);
        $firstSubject = ClassTermSubject::query()->whereBelongsTo($firstTerm)->firstOrFail();
        $secondTerm = Term::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'name' => 'Second Term',
            'starts_on' => $firstTerm->starts_on->addMonths(4),
        ]);
        $secondSubject = ClassTermSubject::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'term_id' => $secondTerm->id,
            'class_arm_id' => $classArm->id,
            'school_subject_id' => $firstSubject->school_subject_id,
        ]);
        $group = AssessmentGroup::factory()->create([
            'school_id' => $school->id,
            'class_term_subject_id' => $secondSubject->id,
            'name' => 'Second term assessment',
            'total_score' => 100,
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);
        $item = $group->items()->create([
            'school_id' => $school->id,
            'name' => 'Exam',
            'max_score' => 100,
            'raw_max_score' => 100,
            'weight' => 100,
            'sort_order' => 1,
        ]);
        $school->studentEnrollments()->create([
            'student_profile_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $secondTerm->id,
            'class_arm_id' => $classArm->id,
            'status' => 'active',
        ]);
        StudentAssessmentScore::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'class_term_subject_id' => $secondSubject->id,
            'assessment_group_id' => $group->id,
            'assessment_item_id' => $item->id,
            'recorded_by' => $user->id,
            'score' => 60,
        ]);

        $run = app(GenerateReportCardRunAction::class)->handle($school, $template->refresh(), $session, $secondTerm, $classArm, $user);
        $snapshot = $run->cards()->firstOrFail()->calculated_snapshot;

        $this->assertSame(70, $snapshot['subjects'][0]['cumulative_percentage']);
        $this->assertCount(2, $snapshot['subjects'][0]['term_results']);
        $this->assertSame(70, $snapshot['summary']['cumulative_average']);
    }

    public function test_broadsheet_displays_a_generated_class_run(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);

        $this->actingAs($user)->get(route('school.reports.broadsheet', ['run' => $run->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SchoolAdmin/Reports/Broadsheet')
                ->where('selectedRun.id', $run->id)
                ->has('subjects', 1)
                ->has('rows', 1));
    }

    public function test_approves_only_a_fully_prepared_class_run(): void
    {
        [$user, $school, $session, $term, $classArm, $template] = $this->createReportContext();
        $run = app(GenerateReportCardRunAction::class)->handle($school, $template, $session, $term, $classArm, $user);
        $run->cards()->update(['status' => 'prepared']);

        $this->actingAs($user)->post(route('school.report-cards.approval.store', $run))->assertRedirect();

        $this->assertSame('approved', $run->refresh()->status);
        $this->assertSame('approved', $run->cards()->firstOrFail()->status);
        $this->assertNotNull($run->approved_at);
    }

    /** @return array{User, School, AcademicSession, Term, ClassArm, ReportCardTemplate, StudentProfile} */
    private function createReportContext(): array
    {
        $user = User::factory()->create();
        $school = School::factory()->approved()->create(['owner_user_id' => $user->id]);
        $school->users()->create(['user_id' => $user->id, 'role' => 'school_super_admin', 'status' => 'active']);
        $user->update(['current_school_id' => $school->id]);

        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Primary']);
        $schoolClass = SchoolClass::factory()->create(['school_id' => $school->id, 'class_group_id' => $classGroup->id, 'name' => 'Primary 1']);
        $classArm = ClassArm::factory()->create(['school_id' => $school->id, 'school_class_id' => $schoolClass->id, 'name' => 'Primary 1A']);
        $session = AcademicSession::factory()->create(['school_id' => $school->id, 'name' => '2026/2027']);
        $term = Term::factory()->create(['school_id' => $school->id, 'academic_session_id' => $session->id, 'name' => 'First Term']);
        $subject = Subject::factory()->create(['name' => 'Mathematics', 'status' => 'active']);
        $schoolSubject = $school->schoolSubjects()->create(['subject_id' => $subject->id, 'is_active' => true]);
        $classTermSubject = ClassTermSubject::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'class_arm_id' => $classArm->id,
            'school_subject_id' => $schoolSubject->id,
        ]);
        $assessmentGroup = AssessmentGroup::factory()->create([
            'school_id' => $school->id,
            'class_term_subject_id' => $classTermSubject->id,
            'name' => 'Standard assessment',
            'total_score' => 100,
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);
        $assessmentItem = $assessmentGroup->items()->create([
            'school_id' => $school->id,
            'name' => 'Exam',
            'max_score' => 100,
            'raw_max_score' => 100,
            'weight' => 100,
            'sort_order' => 1,
        ]);
        $student = StudentProfile::factory()->create([
            'school_id' => $school->id,
            'admission_no' => 'ADM-001',
            'first_name' => 'Ayo',
            'last_name' => 'Mensah',
            'status' => 'active',
        ]);
        $school->studentEnrollments()->create([
            'student_profile_id' => $student->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
            'class_arm_id' => $classArm->id,
            'status' => 'active',
        ]);
        StudentAssessmentScore::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'class_term_subject_id' => $classTermSubject->id,
            'assessment_group_id' => $assessmentGroup->id,
            'assessment_item_id' => $assessmentItem->id,
            'recorded_by' => $user->id,
            'score' => 80,
        ]);
        $scheme = $school->gradingSchemes()->create(['class_group_id' => $classGroup->id, 'name' => 'Primary scale', 'is_default' => true]);
        $scheme->bands()->create(['minimum_score' => 70, 'maximum_score' => 100, 'grade' => 'A', 'remark' => 'Excellent', 'sort_order' => 1]);
        $template = $school->reportCardTemplates()->create([
            'class_group_id' => $classGroup->id,
            'grading_scheme_id' => $scheme->id,
            'name' => 'Primary term report',
            'term_result_mode' => 'independent',
        ]);

        return [$user, $school, $session, $term, $classArm, $template, $student];
    }
}
