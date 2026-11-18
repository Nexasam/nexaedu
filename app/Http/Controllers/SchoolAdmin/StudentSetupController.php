<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Services\Schools\CsvFileReader;
use App\Services\Schools\ResolveStudentGuardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentSetupController extends Controller
{
    public function __construct(
        private ResolveStudentGuardian $resolveStudentGuardian,
        private CsvFileReader $csvFileReader,
    ) {}

    public function index(Request $request): Response
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        return Inertia::render('SchoolAdmin/StudentSetup/Index', [
            'students' => StudentProfile::query()
                ->whereBelongsTo($school)
                ->with([
                    'enrollments' => fn ($query) => $query->latest('id'),
                    'enrollments.classArm.schoolClass.classGroup',
                    'parents',
                ])
                ->latest()
                ->get(),
            'classArms' => ClassArm::query()
                ->whereBelongsTo($school)
                ->with('schoolClass.classGroup')
                ->orderBy('name')
                ->get(),
            'sessions' => AcademicSession::query()
                ->whereBelongsTo($school)
                ->with('terms')
                ->orderByDesc('is_current')
                ->orderByDesc('starts_on')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $this->createStudent($school, $this->validateStudent($request, $school));

        return back()->with('status', 'Student added.');
    }

    public function update(Request $request, StudentProfile $studentProfile): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($studentProfile->school_id === $school->id, 404);
        $validated = $request->validate($this->studentRules($school, $studentProfile));
        $enrollment = StudentEnrollment::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($studentProfile)
            ->findOrFail($validated['enrollment_id']);

        DB::transaction(function () use ($school, $studentProfile, $enrollment, $validated): void {
            $studentProfile->update([
                'admission_no' => $validated['admission_no'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'status' => $validated['status'],
            ]);
            $enrollment->update([
                'academic_session_id' => $validated['academic_session_id'],
                'term_id' => $validated['term_id'] ?? null,
                'class_arm_id' => $validated['class_arm_id'],
            ]);
            $studentProfile->parents()->detach();
            $this->resolveStudentGuardian->handle($school, $studentProfile, $validated);
        });

        return back()->with('status', 'Student updated.');
    }

    public function import(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $request->validate([
            'student_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $rows = $this->csvFileReader->read($request->file('student_file')->getRealPath(), 'student_file')['records'];
        $created = 0;

        DB::transaction(function () use ($rows, $school, &$created): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $attributes = collect(array_keys($this->studentRules($school)))
                    ->mapWithKeys(fn (string $field): array => [$field => str($field)->replace('_', ' ')->append(" on row {$rowNumber}")->toString()])
                    ->all();
                $validator = Validator::make($row, $this->studentRules($school), [], $attributes);

                $this->createStudent($school, $validator->validate());
                $created++;
            }
        });

        return back()->with('status', "{$created} student records imported.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStudent(Request $request, School $school): array
    {
        return $request->validate($this->studentRules($school));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function studentRules(School $school, ?StudentProfile $studentProfile = null): array
    {
        return [
            'admission_no' => ['required', 'string', 'max:50', Rule::unique('student_profiles')->where('school_id', $school->id)->ignore($studentProfile?->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('school_id', $school->id)],
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('school_id', $school->id)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $school->id)],
            'enrollment_id' => [$studentProfile ? 'required' : 'nullable', Rule::exists('student_enrollments', 'id')->where('school_id', $school->id)],
            'status' => [$studentProfile ? 'required' : 'nullable', Rule::in(['active', 'withdrawn', 'graduated', 'archived'])],
            'parent_first_name' => ['nullable', 'required_with:parent_phone,parent_email', 'string', 'max:100'],
            'parent_last_name' => ['nullable', 'required_with:parent_phone,parent_email', 'string', 'max:100'],
            'parent_email' => ['nullable', 'email', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:40'],
            'parent_relationship' => ['nullable', 'required_with:parent_first_name,parent_last_name', 'string', 'max:40'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createStudent(School $school, array $validated): void
    {
        DB::transaction(function () use ($school, $validated): void {
            $student = $school->studentProfiles()->create([
                'admission_no' => $validated['admission_no'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'status' => 'active',
            ]);

            $school->studentEnrollments()->create([
                'student_profile_id' => $student->id,
                'academic_session_id' => $validated['academic_session_id'],
                'term_id' => $validated['term_id'] ?? null,
                'class_arm_id' => $validated['class_arm_id'],
                'enrolled_on' => now()->toDateString(),
                'status' => 'active',
            ]);

            $this->resolveStudentGuardian->handle($school, $student, $validated);
        });
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
