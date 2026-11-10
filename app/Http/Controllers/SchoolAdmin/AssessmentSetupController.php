<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Actions\Schools\ApplyAssessmentFlowAction;
use App\Http\Controllers\Controller;
use App\Models\AssessmentGroup;
use App\Models\School;
use App\Services\Schools\AssessmentTargetResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentSetupController extends Controller
{
    public function __construct(
        private AssessmentTargetResolver $assessmentTargetResolver,
        private ApplyAssessmentFlowAction $applyAssessmentFlow,
    ) {}

    public function index(Request $request): Response
    {
        $school = $this->authorizeSchool($request);

        return Inertia::render('SchoolAdmin/Assessments/Index', [
            'classTermSubjects' => $school->classTermSubjects()
                ->with(['academicSession', 'term', 'classArm.schoolClass.classGroup', 'schoolSubject.subject'])
                ->latest()
                ->get(),
            'classArms' => $school->classArms()
                ->with('schoolClass.classGroup')
                ->orderBy('name')
                ->get(),
            'classGroups' => $school->classGroups()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'assessmentGroups' => AssessmentGroup::query()
                ->whereBelongsTo($school)
                ->with(['classTermSubject.academicSession', 'classTermSubject.term', 'classTermSubject.classArm', 'classTermSubject.schoolSubject.subject', 'items' => fn ($query) => $query->orderBy('sort_order')])
                ->latest()
                ->get()
                ->unique('class_term_subject_id')
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchool($request);
        $validated = $request->validate([
            'target_scope' => ['required', Rule::in(['selected', 'arm', 'group', 'all'])],
            'class_term_subject_ids' => ['array'],
            'class_term_subject_ids.*' => [Rule::exists('class_term_subjects', 'id')->where('school_id', $school->id)],
            'class_arm_id' => ['nullable', Rule::exists('class_arms', 'id')->where('school_id', $school->id)],
            'class_group_id' => ['nullable', Rule::exists('class_groups', 'id')->where('school_id', $school->id)],
            'name' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:100'],
            'items.*.max_score' => ['required', 'numeric', 'min:1', 'max:10000'],
            'items.*.raw_max_score' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'items.*.weight' => ['nullable', 'numeric', 'min:1', 'max:1000'],
        ]);

        $classTermSubjectIds = $this->assessmentTargetResolver->resolve($request->user(), $school, $validated);

        if ($classTermSubjectIds->isEmpty()) {
            return back()->withErrors(['class_term_subject_ids' => 'Select at least one class arm subject.']);
        }

        $appliedCount = $this->applyAssessmentFlow->handle($school, $classTermSubjectIds, $validated);

        return back()->with('status', 'Assessment flow applied to '.$appliedCount.' subject(s).');
    }

    private function authorizeSchool(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);

        return $school;
    }
}
