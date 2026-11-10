<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassTermSubject;
use App\Models\School;
use App\Models\SchoolSubject;
use App\Models\TeacherClassArm;
use App\Models\TeacherClassTermSubject;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        return Inertia::render('SchoolAdmin/Teachers/Assignments', [
            'teachers' => TeacherProfile::query()
                ->whereBelongsTo($school)
                ->with('staffProfile')
                ->orderBy('staff_no')
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
            'schoolSubjects' => SchoolSubject::query()
                ->whereBelongsTo($school)
                ->with('subject')
                ->where('is_active', true)
                ->orderBy('custom_name')
                ->get(),
            'classTermSubjects' => ClassTermSubject::query()
                ->whereBelongsTo($school)
                ->with(['academicSession', 'term', 'classArm.schoolClass.classGroup', 'schoolSubject.subject', 'teacherAssignments.teacherProfile.staffProfile'])
                ->latest()
                ->get(),
            'classArmAssignments' => $school->teacherClassArms()
                ->with(['teacherProfile.staffProfile', 'academicSession', 'term', 'classArm.schoolClass.classGroup'])
                ->latest()
                ->get(),
        ]);
    }

    public function storeClassArm(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $validated = $request->validate([
            'teacher_profile_id' => ['required', Rule::exists('teacher_profiles', 'id')->where('school_id', $school->id)],
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('school_id', $school->id)],
            'term_id' => ['nullable', Rule::exists('terms', 'id')->where('school_id', $school->id)],
            'class_arm_ids' => ['required', 'array', 'min:1'],
            'class_arm_ids.*' => ['required', Rule::exists('class_arms', 'id')->where('school_id', $school->id)],
            'is_class_teacher' => ['sometimes', 'boolean'],
        ]);

        foreach ($validated['class_arm_ids'] as $classArmId) {
            if ($validated['is_class_teacher'] ?? false) {
                $school->teacherClassArms()
                    ->where('academic_session_id', $validated['academic_session_id'])
                    ->where('term_id', $validated['term_id'] ?? null)
                    ->where('class_arm_id', $classArmId)
                    ->update(['is_class_teacher' => false]);
            }

            $school->teacherClassArms()->updateOrCreate([
                'teacher_profile_id' => $validated['teacher_profile_id'],
                'academic_session_id' => $validated['academic_session_id'],
                'term_id' => $validated['term_id'] ?? null,
                'class_arm_id' => $classArmId,
            ], [
                'is_class_teacher' => $validated['is_class_teacher'] ?? false,
            ]);
        }

        return back()->with('status', 'Class arms assigned to teacher.');
    }

    public function storeSubjects(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $validated = $request->validate([
            'teacher_profile_id' => ['required', Rule::exists('teacher_profiles', 'id')->where('school_id', $school->id)],
            'academic_session_id' => ['required', Rule::exists('academic_sessions', 'id')->where('school_id', $school->id)],
            'term_id' => ['required', Rule::exists('terms', 'id')->where('school_id', $school->id)],
            'class_arm_id' => ['required', Rule::exists('class_arms', 'id')->where('school_id', $school->id)],
            'school_subject_ids' => [Rule::requiredIf(! $request->boolean('assign_all_subjects')), 'array'],
            'school_subject_ids.*' => ['required', Rule::exists('school_subjects', 'id')->where('school_id', $school->id)],
            'assign_all_subjects' => ['sometimes', 'boolean'],
        ]);

        $subjectIds = ! empty($validated['assign_all_subjects'])
            ? $school->schoolSubjects()->where('is_active', true)->pluck('id')->all()
            : $validated['school_subject_ids'];

        DB::transaction(function () use ($school, $validated, $subjectIds): void {
            foreach ($subjectIds as $schoolSubjectId) {
                $classTermSubject = $school->classTermSubjects()->firstOrCreate([
                    'academic_session_id' => $validated['academic_session_id'],
                    'term_id' => $validated['term_id'],
                    'class_arm_id' => $validated['class_arm_id'],
                    'school_subject_id' => $schoolSubjectId,
                ]);

                $school->teacherClassTermSubjects()->firstOrCreate([
                    'teacher_profile_id' => $validated['teacher_profile_id'],
                    'class_term_subject_id' => $classTermSubject->id,
                ]);
            }
        });

        return back()->with('status', 'Subject assignments saved.');
    }

    public function destroyClassArm(Request $request, TeacherClassArm $teacherClassArm): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        abort_unless($teacherClassArm->school_id === $school->id, 404);

        $teacherClassArm->delete();

        return back()->with('status', 'Teacher unassigned from class arm.');
    }

    public function destroySubject(Request $request, TeacherClassTermSubject $teacherClassTermSubject): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        abort_unless($teacherClassTermSubject->school_id === $school->id, 404);

        $teacherClassTermSubject->delete();

        return back()->with('status', 'Teacher unassigned from subject.');
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
