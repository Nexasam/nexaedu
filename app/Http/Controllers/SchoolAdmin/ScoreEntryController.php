<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Actions\Schools\SaveStudentScoresAction;
use App\Http\Controllers\Controller;
use App\Models\AssessmentGroup;
use App\Models\AssessmentItem;
use App\Models\School;
use App\Models\StudentAssessmentScore;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Services\Schools\AcademicSessionScoreLock;
use App\Services\Schools\EligibleAssessmentStudents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScoreEntryController extends Controller
{
    public function __construct(
        private EligibleAssessmentStudents $eligibleAssessmentStudents,
        private SaveStudentScoresAction $saveStudentScores,
        private AcademicSessionScoreLock $academicSessionScoreLock,
    ) {}

    public function index(Request $request): Response
    {
        $school = $this->authorizeSchool($request);

        return Inertia::render('SchoolAdmin/Scores/Index', [
            'classTermSubjects' => $school->classTermSubjects()
                ->with(['academicSession', 'term', 'classArm.schoolClass.classGroup', 'schoolSubject.subject', 'teacherAssignments'])
                ->latest()
                ->get()
                ->each(function ($subject): void {
                    $session = $subject->academicSession;
                    $session?->setAttribute('is_score_locked', $this->academicSessionScoreLock->isLocked($session));
                }),
            'assessmentGroups' => AssessmentGroup::query()
                ->whereBelongsTo($school)
                ->with(['completedBy', 'classTermSubject.academicSession', 'classTermSubject.term', 'classTermSubject.classArm.schoolClass.classGroup', 'classTermSubject.schoolSubject.subject', 'items' => fn ($query) => $query->orderBy('sort_order')])
                ->latest()
                ->get()
                ->unique('class_term_subject_id')
                ->values()
                ->each(function (AssessmentGroup $group): void {
                    $session = $group->classTermSubject?->academicSession;
                    $session?->setAttribute('is_score_locked', $this->academicSessionScoreLock->isLocked($session));
                }),
            'students' => StudentProfile::query()
                ->whereBelongsTo($school)
                ->with(['enrollments.academicSession', 'enrollments.term', 'enrollments.classArm'])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'teachers' => TeacherProfile::query()
                ->whereBelongsTo($school)
                ->with('staffProfile')
                ->whereHas('classTermSubjectAssignments')
                ->orderBy('staff_no')
                ->get(),
            'scores' => StudentAssessmentScore::query()
                ->whereBelongsTo($school)
                ->get(['student_profile_id', 'assessment_group_id', 'assessment_item_id', 'score']),
            'canOverrideCompleted' => $this->isSchoolSuperAdmin($request),
            'canOverrideSessionLock' => $this->isSchoolSuperAdmin($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchool($request);
        $validated = $request->validate([
            'assessment_group_id' => ['required', Rule::exists('assessment_groups', 'id')->where('school_id', $school->id)],
            'scores' => ['required', 'array'],
            'scores.*.student_profile_id' => ['required', Rule::exists('student_profiles', 'id')->where('school_id', $school->id)],
            'scores.*.items' => ['required', 'array'],
            'scores.*.items.*.assessment_item_id' => ['required', Rule::exists('assessment_items', 'id')->where('school_id', $school->id)],
            'scores.*.items.*.score' => ['nullable', 'numeric', 'min:0'],
        ]);

        $group = $this->assessmentGroup($school, (int) $validated['assessment_group_id']);
        $this->ensureScoreEntryIsUnlocked($request, $group);
        $eligibleStudentIds = $this->eligibleAssessmentStudents->forGroup($school, $group)->pluck('id');

        $this->saveStudentScores->handle($school, $group, collect($validated['scores']), $eligibleStudentIds, $request->user()->id, $this->isSchoolSuperAdmin($request));

        return back()->with('status', 'Scores saved.');
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchool($request);
        $validated = $request->validate([
            'score_sheets' => ['required', 'array', 'min:1'],
            'score_sheets.*.assessment_group_id' => ['required', 'distinct', Rule::exists('assessment_groups', 'id')->where('school_id', $school->id)],
            'score_sheets.*.scores' => ['required', 'array'],
            'score_sheets.*.scores.*.student_profile_id' => ['required', Rule::exists('student_profiles', 'id')->where('school_id', $school->id)],
            'score_sheets.*.scores.*.items' => ['required', 'array'],
            'score_sheets.*.scores.*.items.*.assessment_item_id' => ['required', Rule::exists('assessment_items', 'id')->where('school_id', $school->id)],
            'score_sheets.*.scores.*.items.*.score' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $school, $validated): void {
            foreach ($validated['score_sheets'] as $scoreSheet) {
                $group = $this->assessmentGroup($school, (int) $scoreSheet['assessment_group_id']);
                $this->ensureScoreEntryIsUnlocked($request, $group);
                $eligibleStudentIds = $this->eligibleAssessmentStudents->forGroup($school, $group)->pluck('id');

                $this->saveStudentScores->handle(
                    $school,
                    $group,
                    collect($scoreSheet['scores']),
                    $eligibleStudentIds,
                    $request->user()->id,
                    $this->isSchoolSuperAdmin($request),
                );
            }
        });

        return back()->with('status', count($validated['score_sheets']).' subject score sheets saved.');
    }

    public function template(Request $request, AssessmentGroup $assessmentGroup): StreamedResponse
    {
        $school = $this->authorizeSchool($request);
        $group = $this->assessmentGroup($school, $assessmentGroup->id);
        $students = $this->eligibleAssessmentStudents->forGroup($school, $group);
        $items = $group->items;
        $classTermSubject = $group->classTermSubject;
        $subject = $classTermSubject->schoolSubject?->custom_name
            ?: $classTermSubject->schoolSubject?->subject?->name
            ?: 'Subject';
        $filename = str($subject.'-'.$classTermSubject->classArm?->name.'-'.$group->name.'-scores')
            ->slug('-')
            ->append('.csv')
            ->toString();

        return response()->streamDownload(function () use ($students, $items, $classTermSubject, $subject): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['admission_no', 'student_name', 'class_arm', 'subject', ...$items->pluck('name')->all()]);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->admission_no,
                    trim($student->first_name.' '.$student->last_name),
                    $classTermSubject->classArm?->name,
                    $subject,
                    ...array_fill(0, $items->count(), ''),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function bulkTemplate(Request $request): StreamedResponse
    {
        $school = $this->authorizeSchool($request);
        $validated = $request->validate([
            'assessment_group_ids' => ['required', 'array', 'min:1'],
            'assessment_group_ids.*' => ['required', 'distinct', Rule::exists('assessment_groups', 'id')->where('school_id', $school->id)],
            'student_profile_ids' => ['sometimes', 'array'],
            'student_profile_ids.*' => ['required', 'distinct', Rule::exists('student_profiles', 'id')->where('school_id', $school->id)],
        ]);
        $groups = $this->assessmentGroups($school, $validated['assessment_group_ids']);
        $studentIds = collect($validated['student_profile_ids'] ?? [])->map(fn ($id) => (int) $id);
        $itemHeaders = $groups->flatMap->items
            ->mapWithKeys(fn ($item) => [$item->id => $this->assessmentItemHeader($item)])
            ->all();
        $scores = StudentAssessmentScore::query()
            ->whereBelongsTo($school)
            ->whereIn('assessment_group_id', $groups->pluck('id'))
            ->when($studentIds->isNotEmpty(), fn ($query) => $query->whereIn('student_profile_id', $studentIds))
            ->get()
            ->keyBy(fn (StudentAssessmentScore $score) => $score->student_profile_id.':'.$score->assessment_item_id);

        return response()->streamDownload(function () use ($groups, $itemHeaders, $scores, $studentIds): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [...$this->bulkTemplateFixedHeaders(), ...array_values($itemHeaders)]);

            foreach ($groups as $group) {
                $subject = $group->classTermSubject;
                $students = $this->eligibleAssessmentStudents->forGroup($group->school, $group)
                    ->when($studentIds->isNotEmpty(), fn ($eligible) => $eligible->whereIn('id', $studentIds));

                foreach ($students as $student) {
                    $scoreColumns = collect($itemHeaders)->mapWithKeys(fn ($header, $itemId) => [
                        $header => $scores->get($student->id.':'.$itemId)?->score ?? '',
                    ]);

                    fputcsv($handle, [
                        $group->id,
                        $student->id,
                        $student->admission_no,
                        $this->spreadsheetSafe(trim($student->first_name.' '.$student->last_name)),
                        $this->spreadsheetSafe($subject->classArm?->name ?? ''),
                        $this->spreadsheetSafe($subject->schoolSubject?->custom_name ?: $subject->schoolSubject?->subject?->name ?: 'Subject'),
                        $this->spreadsheetSafe($subject->academicSession?->name ?? ''),
                        $this->spreadsheetSafe($subject->term?->name ?? ''),
                        ...$scoreColumns->values()->all(),
                    ]);
                }
            }

            fclose($handle);
        }, 'filtered-score-entry-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchool($request);
        $validated = $request->validate([
            'assessment_group_id' => ['required', Rule::exists('assessment_groups', 'id')->where('school_id', $school->id)],
            'score_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $group = $this->assessmentGroup($school, (int) $validated['assessment_group_id']);
        $this->ensureScoreEntryIsUnlocked($request, $group);
        $eligibleStudents = $this->eligibleAssessmentStudents->forGroup($school, $group)->keyBy('admission_no');
        $rows = $this->csvRows($request->file('score_file')->getRealPath());
        $expectedHeaders = ['admission_no', 'student_name', 'class_arm', 'subject', ...$group->items->pluck('name')->all()];

        if (($rows['headers'] ?? []) !== $expectedHeaders) {
            throw ValidationException::withMessages([
                'score_file' => 'This file does not match the selected assessment flow. Download a fresh template and try again.',
            ]);
        }

        $payload = collect($rows['records'])->map(function (array $record) use ($eligibleStudents, $group) {
            $student = $eligibleStudents->get($record['admission_no'] ?? '');

            if (! $student) {
                throw ValidationException::withMessages([
                    'score_file' => "Admission number {$record['admission_no']} is not in this class arm for the selected term.",
                ]);
            }

            return [
                'student_profile_id' => $student->id,
                'items' => $group->items->map(fn ($item) => [
                    'assessment_item_id' => $item->id,
                    'score' => $record[$item->name] ?? null,
                ])->all(),
            ];
        });

        $this->saveStudentScores->handle($school, $group, $payload, $eligibleStudents->pluck('id'), $request->user()->id, $this->isSchoolSuperAdmin($request));

        return back()->with('status', 'Scores imported.');
    }

    public function bulkImport(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchool($request);
        $validated = $request->validate([
            'assessment_group_ids' => ['required', 'array', 'min:1'],
            'assessment_group_ids.*' => ['required', 'distinct', Rule::exists('assessment_groups', 'id')->where('school_id', $school->id)],
            'score_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);
        $groups = $this->assessmentGroups($school, $validated['assessment_group_ids'])->keyBy('id');
        $rows = $this->csvRows($request->file('score_file')->getRealPath());

        if (array_slice($rows['headers'] ?? [], 0, count($this->bulkTemplateFixedHeaders())) !== $this->bulkTemplateFixedHeaders()) {
            throw ValidationException::withMessages([
                'score_file' => 'This is not a valid filtered score-entry template. Download a fresh template and try again.',
            ]);
        }

        $expectedItemHeaders = $groups->flatMap->items->map(fn (AssessmentItem $item) => $this->assessmentItemHeader($item));

        if ($expectedItemHeaders->diff($rows['headers'])->isNotEmpty()) {
            throw ValidationException::withMessages(['score_file' => 'The assessment columns do not match the selected score flows. Download a fresh template and try again.']);
        }

        $eligibleStudentsByGroup = $groups->mapWithKeys(fn (AssessmentGroup $group) => [
            $group->id => $this->eligibleAssessmentStudents->forGroup($school, $group)->keyBy('id'),
        ]);
        $payloads = [];

        foreach ($rows['records'] as $record) {
            $group = $groups->get((int) ($record['assessment_group_id'] ?? 0));

            if (! $group) {
                throw ValidationException::withMessages(['score_file' => 'The file contains a subject outside the selected filter.']);
            }

            $studentId = (int) ($record['student_profile_id'] ?? 0);
            $student = $eligibleStudentsByGroup->get($group->id)->get($studentId);

            if (! $student || $student->admission_no !== ($record['admission_no'] ?? null)) {
                throw ValidationException::withMessages(['score_file' => 'The file contains a student who is not eligible for one of the selected subjects.']);
            }

            $payloads[$group->id][] = [
                'student_profile_id' => $student->id,
                'items' => $group->items->map(fn ($item) => [
                    'assessment_item_id' => $item->id,
                    'score' => $record[$this->assessmentItemHeader($item)] ?? null,
                ])->all(),
            ];
        }

        DB::transaction(function () use ($groups, $payloads, $request, $school): void {
            foreach ($payloads as $groupId => $scores) {
                $group = $groups->get($groupId);
                $this->ensureScoreEntryIsUnlocked($request, $group);
                $eligibleStudentIds = $this->eligibleAssessmentStudents->forGroup($school, $group)->pluck('id');
                $this->saveStudentScores->handle($school, $group, collect($scores), $eligibleStudentIds, $request->user()->id, $this->isSchoolSuperAdmin($request));
            }
        });

        return back()->with('status', count($payloads).' subject score sheets imported.');
    }

    public function complete(Request $request, AssessmentGroup $assessmentGroup): RedirectResponse
    {
        $school = $this->authorizeSchool($request);
        $group = $this->assessmentGroup($school, $assessmentGroup->id);
        $this->ensureScoreEntryIsUnlocked($request, $group);

        if ($group->completed_at !== null && ! $this->isSchoolSuperAdmin($request)) {
            return back()->withErrors(['scores' => 'This score sheet has already been completed.']);
        }

        $group->forceFill([
            'completed_at' => now(),
            'completed_by' => $request->user()->id,
        ])->save();

        return back()->with('status', 'Score sheet marked as completed.');
    }

    public function sendToParent(Request $request, AssessmentGroup $assessmentGroup, StudentProfile $studentProfile): RedirectResponse
    {
        $school = $this->authorizeSchool($request);
        $group = $this->assessmentGroup($school, $assessmentGroup->id);

        abort_unless($studentProfile->school_id === $school->id, 404);

        if ($group->completed_at === null) {
            return back()->withErrors(['scores' => 'Complete this score sheet before sending it to the parent.']);
        }

        $isEligible = $this->eligibleAssessmentStudents
            ->forGroup($school, $group)
            ->contains('id', $studentProfile->id);

        abort_unless($isEligible, 422);

        return back()->with('status', 'Report notification queued for '.$studentProfile->first_name.' '.$studentProfile->last_name.'.');
    }

    private function authorizeSchool(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);

        return $school;
    }

    private function assessmentGroup(School $school, int $assessmentGroupId): AssessmentGroup
    {
        return AssessmentGroup::query()
            ->whereBelongsTo($school)
            ->with(['classTermSubject.academicSession', 'classTermSubject.classArm', 'classTermSubject.schoolSubject.subject', 'items' => fn ($query) => $query->orderBy('sort_order')])
            ->findOrFail($assessmentGroupId);
    }

    /** @param array<int, int|string> $assessmentGroupIds */
    private function assessmentGroups(School $school, array $assessmentGroupIds): Collection
    {
        return AssessmentGroup::query()
            ->whereBelongsTo($school)
            ->whereIn('id', $assessmentGroupIds)
            ->with(['school', 'classTermSubject.academicSession', 'classTermSubject.term', 'classTermSubject.classArm', 'classTermSubject.schoolSubject.subject', 'items' => fn ($query) => $query->orderBy('sort_order')])
            ->get();
    }

    private function assessmentItemHeader(AssessmentItem $item): string
    {
        return $item->name.' / '.$item->rawMaximumScore().' [item:'.$item->id.']';
    }

    /** @return array<int, string> */
    private function bulkTemplateFixedHeaders(): array
    {
        return ['assessment_group_id', 'student_profile_id', 'admission_no', 'student_name', 'class_arm', 'subject', 'academic_session', 'term'];
    }

    private function spreadsheetSafe(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }

    private function ensureScoreEntryIsUnlocked(Request $request, AssessmentGroup $group): void
    {
        $session = $group->classTermSubject?->academicSession;

        if ($session && $this->academicSessionScoreLock->isLocked($session) && ! $this->isSchoolSuperAdmin($request)) {
            throw ValidationException::withMessages([
                'scores' => 'Score entry is locked for '.$session->name.'. Contact the school super admin to unlock it.',
            ]);
        }
    }

    private function isSchoolSuperAdmin(Request $request): bool
    {
        return $request->user()->schoolUsers()
            ->whereBelongsTo($request->user()->currentSchool)
            ->where('role', 'school_super_admin')
            ->where('status', 'active')
            ->exists();
    }

    /**
     * @return array{headers: array<int, string>, records: array<int, array<string, string|null>>}
     */
    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['score_file' => 'The uploaded file could not be read.']);
        }

        $headers = fgetcsv($handle);
        $headers = array_map(fn ($header) => trim((string) $header), $headers ?: []);
        $records = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (collect($row)->every(fn ($value) => trim((string) $value) === '')) {
                continue;
            }

            $records[] = array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));
        }

        fclose($handle);

        return ['headers' => $headers, 'records' => $records];
    }
}
