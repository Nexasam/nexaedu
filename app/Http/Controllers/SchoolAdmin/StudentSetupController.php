<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentSetupController extends Controller
{
    public function index(Request $request): Response
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        return Inertia::render('SchoolAdmin/StudentSetup/Index', [
            'students' => StudentProfile::query()
                ->whereBelongsTo($school)
                ->with(['enrollments.classArm.schoolClass.classGroup', 'parents'])
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

    public function import(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        $request->validate([
            'student_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $rows = $this->readCsv($request->file('student_file')->getRealPath());
        $created = 0;

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, $this->studentRules($school), [], [
                'admission_no' => 'admission no on row '.($index + 2),
                'first_name' => 'first name on row '.($index + 2),
                'last_name' => 'last name on row '.($index + 2),
            ]);

            $this->createStudent($school, $validator->validate());
            $created++;
        }

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
    private function studentRules(School $school): array
    {
        return [
            'admission_no' => ['required', 'string', 'max:50', Rule::unique('student_profiles')->where('school_id', $school->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date'],
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('school_id', $school->id)],
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('school_id', $school->id)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $school->id)],
            'parent_first_name' => ['nullable', 'required_with:parent_phone,parent_email', 'string', 'max:100'],
            'parent_last_name' => ['nullable', 'required_with:parent_phone,parent_email', 'string', 'max:100'],
            'parent_email' => ['nullable', 'email', 'max:255', Rule::unique('parent_profiles', 'email')->where('school_id', $school->id), Rule::unique('users', 'email')],
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

            if (! empty($validated['parent_first_name'])) {
                $user = null;

                if (! empty($validated['parent_email'])) {
                    $user = User::query()->create([
                        'name' => $validated['parent_first_name'].' '.$validated['parent_last_name'],
                        'email' => $validated['parent_email'],
                        'password' => Hash::make('password'),
                        'current_school_id' => $school->id,
                    ]);

                    $school->users()->create([
                        'user_id' => $user->id,
                        'role' => 'parent',
                        'status' => 'active',
                    ]);
                }

                $parent = $school->parentProfiles()->create([
                    'user_id' => $user?->id,
                    'first_name' => $validated['parent_first_name'],
                    'last_name' => $validated['parent_last_name'],
                    'email' => $validated['parent_email'] ?? null,
                    'phone' => $validated['parent_phone'] ?? null,
                ]);

                $parent->students()->attach($student->id, [
                    'school_id' => $school->id,
                    'relationship' => $validated['parent_relationship'],
                ]);
            }
        });
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'student_file' => 'The student import file could not be opened.',
            ]);
        }

        $headers = fgetcsv($handle);
        $rows = [];

        if ($headers === false) {
            fclose($handle);

            return $rows;
        }

        while (($values = fgetcsv($handle)) !== false) {
            $row = [];

            foreach ($headers as $index => $header) {
                $row[trim((string) $header)] = $values[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
