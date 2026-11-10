<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Actions\Schools\GenerateReportCardRunAction;
use App\Actions\Schools\SelectReportCardTemplateAction;
use App\Actions\Schools\UpdateStudentReportCardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolAdmin\StoreReportCardRunRequest;
use App\Http\Requests\SchoolAdmin\UpdateStudentReportCardRequest;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\PlatformReportCardTemplate;
use App\Models\ReportCardRun;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Models\StudentReportCard;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportCardController extends Controller
{
    public function __construct(
        private GenerateReportCardRunAction $generateReportCardRun,
        private UpdateStudentReportCardAction $updateStudentReportCard,
        private SelectReportCardTemplateAction $selectReportCardTemplate,
    ) {}

    public function index(Request $request): Response
    {
        $school = $this->school($request);

        return Inertia::render('SchoolAdmin/ReportCards/Index', [
            'templates' => $school->reportCardTemplateSelections()
                ->with(['classGroup', 'platformTemplate', 'reportCardTemplate.gradingScheme'])
                ->get()
                ->pluck('reportCardTemplate')
                ->filter()
                ->values(),
            'platformTemplates' => PlatformReportCardTemplate::query()
                ->where('is_active', true)
                ->whereHas('accesses', function ($query) use ($school): void {
                    $query->whereBelongsTo($school)->where('status', 'granted')->whereNull('revoked_at');
                })
                ->orderBy('education_level')
                ->orderBy('name')
                ->get(),
            'templateSelections' => $school->reportCardTemplateSelections()
                ->with(['classGroup', 'platformTemplate', 'reportCardTemplate'])
                ->get(),
            'academicSessions' => $school->academicSessions()->orderByDesc('starts_on')->get(),
            'terms' => $school->terms()->with('academicSession')->orderBy('starts_on')->get(),
            'classArms' => $school->classArms()->with('schoolClass.classGroup')->orderBy('name')->get(),
            'classGroups' => $school->classGroups()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->each(fn ($classGroup) => $classGroup->setAttribute('education_level', $this->selectReportCardTemplate->educationLevelFor($classGroup))),
            'traitDefinitions' => $school->reportCardTraitDefinitions()
                ->with('classGroup')
                ->where('is_active', true)
                ->orderBy('class_group_id')
                ->orderBy('sort_order')
                ->get(),
            'runs' => ReportCardRun::query()
                ->whereBelongsTo($school)
                ->with(['template', 'academicSession', 'term', 'classArm.schoolClass.classGroup'])
                ->withCount('cards')
                ->latest('generated_at')
                ->get(),
        ]);
    }

    public function store(StoreReportCardRunRequest $request): RedirectResponse
    {
        $school = $this->school($request);
        $validated = $request->validated();
        $academicSession = AcademicSession::query()->whereBelongsTo($school)->findOrFail($validated['academic_session_id']);
        $term = Term::query()->whereBelongsTo($school)->findOrFail($validated['term_id']);
        $classArm = ClassArm::query()->whereBelongsTo($school)->findOrFail($validated['class_arm_id']);
        $selection = $school->reportCardTemplateSelections()
            ->where('class_group_id', $classArm->schoolClass()->value('class_group_id'))
            ->where('report_card_template_id', $validated['report_card_template_id'])
            ->firstOrFail();
        $template = ReportCardTemplate::query()->whereBelongsTo($school)->findOrFail($selection->report_card_template_id);

        $this->generateReportCardRun->handle($school, $template, $academicSession, $term, $classArm, $request->user());

        return back()->with('status', 'Draft report cards generated for the selected class arm.');
    }

    public function show(Request $request, ReportCardRun $reportCardRun): Response
    {
        $school = $this->school($request);
        abort_unless($reportCardRun->school_id === $school->id, 404);

        $reportCardRun->load([
            'template',
            'academicSession',
            'term',
            'classArm.schoolClass.classGroup',
        ]);
        $classGroupId = $reportCardRun->classArm->schoolClass->class_group_id;

        return Inertia::render('SchoolAdmin/ReportCards/Show', [
            'run' => $reportCardRun,
            'traitDefinitions' => $school->reportCardTraitDefinitions()
                ->where('is_active', true)
                ->where(function ($query) use ($classGroupId): void {
                    $query->whereNull('class_group_id')->orWhere('class_group_id', $classGroupId);
                })
                ->orderBy('sort_order')
                ->get(),
            'cards' => $reportCardRun->cards()
                ->with(['student', 'ratings'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function updateCard(UpdateStudentReportCardRequest $request, ReportCardRun $reportCardRun, StudentReportCard $studentReportCard): RedirectResponse
    {
        $updatedReportCard = $this->updateStudentReportCard->handle(
            $this->school($request),
            $reportCardRun,
            $studentReportCard,
            $request->validated(),
            $request->user(),
        );

        $status = $updatedReportCard->status === 'prepared'
            ? 'Student report card saved and marked as prepared.'
            : 'Student report card saved as a draft. Complete attendance and choose a promotion decision to mark it as prepared.';

        return back()->with('status', $status);
    }

    private function school(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);

        return $school;
    }
}
