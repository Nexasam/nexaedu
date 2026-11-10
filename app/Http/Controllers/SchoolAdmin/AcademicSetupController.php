<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolAdmin\DuplicateAcademicSessionRequest;
use App\Http\Requests\SchoolAdmin\UpdateSessionScoreLockRequest;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolSubject;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Schools\AcademicSessionScoreLock;
use App\Services\Schools\DuplicateAcademicSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSetupController extends Controller
{
    public function __construct(
        private DuplicateAcademicSession $duplicateAcademicSession,
        private AcademicSessionScoreLock $academicSessionScoreLock,
    ) {}

    public function index(Request $request): Response
    {
        $school = $request->user()->currentSchool;

        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return Inertia::render('SchoolAdmin/AcademicSetup/Index', [
            'school' => $school,
            'classGroups' => ClassGroup::query()
                ->whereBelongsTo($school)
                ->withCount('classes')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'classes' => SchoolClass::query()
                ->whereBelongsTo($school)
                ->with(['classGroup', 'arms'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'classArms' => ClassArm::query()
                ->whereBelongsTo($school)
                ->with('schoolClass.classGroup')
                ->orderBy('name')
                ->get(),
            'sessions' => AcademicSession::query()
                ->whereBelongsTo($school)
                ->with('terms')
                ->latest('is_current')
                ->orderByDesc('starts_on')
                ->orderBy('name')
                ->get()
                ->each(fn (AcademicSession $session) => $session->setAttribute('is_score_locked', $this->academicSessionScoreLock->isLocked($session))),
            'schoolSubjects' => SchoolSubject::query()
                ->whereBelongsTo($school)
                ->with('subject')
                ->latest()
                ->get(),
            'subjects' => Subject::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function storeClassGroup(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('class_groups')->where('school_id', $school->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $school->classGroups()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Class group added.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'class_group_id' => [
                'required',
                Rule::exists('class_groups', 'id')->where('school_id', $school->id),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('school_classes')
                    ->where('school_id', $school->id)
                    ->where('class_group_id', $request->integer('class_group_id')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $school->classes()->create([
            'class_group_id' => $validated['class_group_id'],
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'Class added.');
    }

    public function storeClassArm(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'school_class_id' => [
                'required',
                Rule::exists('school_classes', 'id')->where('school_id', $school->id),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('class_arms')
                    ->where('school_id', $school->id)
                    ->where('school_class_id', $request->integer('school_class_id')),
            ],
            'label' => ['nullable', 'string', 'max:30'],
        ]);

        $school->classArms()->create($validated);

        return back()->with('status', 'Class arm added.');
    }

    public function storeAcademicSession(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('academic_sessions')->where('school_id', $school->id),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_current')) {
            $school->academicSessions()->update(['is_current' => false]);
        }

        $school->academicSessions()->create([
            ...$validated,
            'is_current' => $request->boolean('is_current'),
        ]);

        return back()->with('status', 'Academic session added.');
    }

    public function storeTerm(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'academic_session_id' => [
                'required',
                Rule::exists('academic_sessions', 'id')->where('school_id', $school->id),
            ],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('terms')
                    ->where('school_id', $school->id)
                    ->where('academic_session_id', $request->integer('academic_session_id')),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_current')) {
            $school->terms()->update(['is_current' => false]);
        }

        $school->terms()->create([
            ...$validated,
            'is_current' => $request->boolean('is_current'),
        ]);

        return back()->with('status', 'Term added.');
    }

    public function storeSchoolSubject(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'subject_id' => [
                'required',
                Rule::exists('subjects', 'id')->where('status', 'active'),
                Rule::unique('school_subjects')->where('school_id', $school->id),
            ],
            'custom_name' => ['nullable', 'string', 'max:100'],
        ]);

        $school->schoolSubjects()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('status', 'Subject added to school.');
    }

    public function storeDefaultClassGroups(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $created = 0;

        foreach ($this->defaultClassGroups() as $index => $name) {
            $group = $school->classGroups()->firstOrCreate(
                ['name' => $name],
                ['sort_order' => $index + 1],
            );

            if ($group->wasRecentlyCreated) {
                $created++;
            }
        }

        return back()->with('status', "{$created} class groups added from defaults.");
    }

    public function storeDefaultClasses(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $created = 0;

        DB::transaction(function () use ($school, &$created): void {
            foreach ($this->defaultClasses() as $groupIndex => $groupDefaults) {
                $group = $school->classGroups()->firstOrCreate(
                    ['name' => $groupDefaults['group']],
                    ['sort_order' => $groupIndex + 1],
                );

                foreach ($groupDefaults['classes'] as $classIndex => $className) {
                    $schoolClass = $school->classes()->firstOrCreate(
                        [
                            'class_group_id' => $group->id,
                            'name' => $className,
                        ],
                        ['sort_order' => $classIndex + 1],
                    );

                    if ($schoolClass->wasRecentlyCreated) {
                        $created++;
                    }
                }
            }
        });

        return back()->with('status', "{$created} classes added from defaults.");
    }

    public function storeBulkSchoolSubjects(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $validated = $request->validate([
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => [
                'integer',
                Rule::exists('subjects', 'id')->where('status', 'active'),
            ],
        ]);

        $existingSubjectIds = $school->schoolSubjects()
            ->whereIn('subject_id', $validated['subject_ids'])
            ->pluck('subject_id')
            ->all();
        $newSubjectIds = collect($validated['subject_ids'])
            ->unique()
            ->diff($existingSubjectIds)
            ->values();

        $newSubjectIds->each(function (int $subjectId) use ($school): void {
            $school->schoolSubjects()->create([
                'subject_id' => $subjectId,
                'custom_name' => null,
                'is_active' => true,
            ]);
        });

        return back()->with('status', "{$newSubjectIds->count()} subjects added to school.");
    }

    public function updateClassGroup(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($classGroup->school_id === $school->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('class_groups')->where('school_id', $school->id)->ignore($classGroup->id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $classGroup->update(['name' => $validated['name'], 'sort_order' => $validated['sort_order'] ?? 0]);

        return back()->with('status', 'Class group updated.');
    }

    public function destroyClassGroup(Request $request, ClassGroup $classGroup): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($classGroup->school_id === $school->id, 404);

        if (! $this->deleteSafely($classGroup)) {
            return back()->withErrors(['class_group' => 'This class group is already in use. Delete its classes first.']);
        }

        return back()->with('status', 'Class group deleted.');
    }

    public function updateClass(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($schoolClass->school_id === $school->id, 404);

        $validated = $request->validate([
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')->where('school_id', $school->id)],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('school_classes')
                    ->where('school_id', $school->id)
                    ->where('class_group_id', $request->integer('class_group_id'))
                    ->ignore($schoolClass->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $schoolClass->update(['class_group_id' => $validated['class_group_id'], 'name' => $validated['name'], 'sort_order' => $validated['sort_order'] ?? 0]);

        return back()->with('status', 'Class updated.');
    }

    public function destroyClass(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($schoolClass->school_id === $school->id, 404);

        if (! $this->deleteSafely($schoolClass)) {
            return back()->withErrors(['class' => 'This class is already in use. Delete its class arms first.']);
        }

        return back()->with('status', 'Class deleted.');
    }

    public function updateClassArm(Request $request, ClassArm $classArm): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($classArm->school_id === $school->id, 404);

        $validated = $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $school->id)],
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('class_arms')
                    ->where('school_id', $school->id)
                    ->where('school_class_id', $request->integer('school_class_id'))
                    ->ignore($classArm->id),
            ],
            'label' => ['nullable', 'string', 'max:30'],
        ]);

        $classArm->update($validated);

        return back()->with('status', 'Class arm updated.');
    }

    public function destroyClassArm(Request $request, ClassArm $classArm): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($classArm->school_id === $school->id, 404);

        if (! $this->deleteSafely($classArm)) {
            return back()->withErrors(['class_arm' => 'This class arm is already in use by students or subjects.']);
        }

        return back()->with('status', 'Class arm deleted.');
    }

    public function updateAcademicSession(Request $request, AcademicSession $academicSession): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($academicSession->school_id === $school->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('academic_sessions')->where('school_id', $school->id)->ignore($academicSession->id)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_current')) {
            $school->academicSessions()->whereKeyNot($academicSession->id)->update(['is_current' => false]);
        }

        $academicSession->update([...$validated, 'is_current' => $request->boolean('is_current')]);

        return back()->with('status', 'Academic session updated.');
    }

    public function destroyAcademicSession(Request $request, AcademicSession $academicSession): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($academicSession->school_id === $school->id, 404);

        if (! $this->deleteSafely($academicSession)) {
            return back()->withErrors(['academic_session' => 'This academic session is already in use. Delete its terms or student enrollments first.']);
        }

        return back()->with('status', 'Academic session deleted.');
    }

    public function duplicateAcademicSession(DuplicateAcademicSessionRequest $request, AcademicSession $academicSession): RedirectResponse
    {
        $school = $request->user()->currentSchool;

        $newSession = $this->duplicateAcademicSession->handle($school, $academicSession, $request->validated());

        return back()->with('status', "{$newSession->name} was created from {$academicSession->name}. Scores were not copied.");
    }

    public function updateSessionScoreLock(UpdateSessionScoreLockRequest $request, AcademicSession $academicSession): RedirectResponse
    {
        $academicSession->update($request->validated());

        return back()->with('status', 'Score entry access updated for '.$academicSession->name.'.');
    }

    public function updateTerm(Request $request, Term $term): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($term->school_id === $school->id, 404);

        $validated = $request->validate([
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('school_id', $school->id)],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('terms')
                    ->where('school_id', $school->id)
                    ->where('academic_session_id', $request->integer('academic_session_id'))
                    ->ignore($term->id),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('is_current')) {
            $school->terms()->whereKeyNot($term->id)->update(['is_current' => false]);
        }

        $term->update([...$validated, 'is_current' => $request->boolean('is_current')]);

        return back()->with('status', 'Term updated.');
    }

    public function destroyTerm(Request $request, Term $term): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($term->school_id === $school->id, 404);

        if (! $this->deleteSafely($term)) {
            return back()->withErrors(['term' => 'This term is already in use by students or subjects.']);
        }

        return back()->with('status', 'Term deleted.');
    }

    public function updateSchoolSubject(Request $request, SchoolSubject $schoolSubject): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($schoolSubject->school_id === $school->id, 404);

        $validated = $request->validate([
            'subject_id' => [
                'required',
                Rule::exists('subjects', 'id')->where('status', 'active'),
                Rule::unique('school_subjects')->where('school_id', $school->id)->ignore($schoolSubject->id),
            ],
            'custom_name' => ['nullable', 'string', 'max:100'],
        ]);

        $schoolSubject->update($validated);

        return back()->with('status', 'School subject updated.');
    }

    public function destroySchoolSubject(Request $request, SchoolSubject $schoolSubject): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($schoolSubject->school_id === $school->id, 404);

        if (! $this->deleteSafely($schoolSubject)) {
            return back()->withErrors(['school_subject' => 'This school subject is already in use.']);
        }

        return back()->with('status', 'School subject removed.');
    }

    private function deleteSafely(mixed $model): bool
    {
        try {
            $model->delete();

            return true;
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    private function defaultClassGroups(): array
    {
        return ['Creche', 'Nursery', 'Primary', 'Secondary'];
    }

    /**
     * @return array<int, array{group: string, classes: array<int, string>}>
     */
    private function defaultClasses(): array
    {
        return [
            ['group' => 'Creche', 'classes' => ['Creche 1', 'Creche 2']],
            ['group' => 'Nursery', 'classes' => ['Nursery 1', 'Nursery 2', 'Nursery 3']],
            ['group' => 'Primary', 'classes' => ['Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6']],
            ['group' => 'Secondary', 'classes' => ['JSS 1', 'JSS 2', 'JSS 3', 'SSS 1', 'SSS 2', 'SSS 3']],
        ];
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
