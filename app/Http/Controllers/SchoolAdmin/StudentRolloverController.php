<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolAdmin\StoreStudentRolloverRequest;
use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Services\Schools\RolloverStudents;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentRolloverController extends Controller
{
    public function index(Request $request): Response
    {
        $school = $this->schoolFor($request);
        $sourceSessionId = $request->integer('source_session_id') ?: null;
        $students = collect();

        if ($sourceSessionId && $school->academicSessions()->whereKey($sourceSessionId)->exists()) {
            $students = StudentEnrollment::query()
                ->whereBelongsTo($school)
                ->where('academic_session_id', $sourceSessionId)
                ->with(['studentProfile', 'classArm.schoolClass.classGroup'])
                ->orderByDesc('term_id')
                ->orderByDesc('id')
                ->get()
                ->unique('student_profile_id')
                ->values();
        }

        return Inertia::render('SchoolAdmin/Students/Rollover', [
            'sessions' => AcademicSession::query()->whereBelongsTo($school)->with('terms')->orderByDesc('starts_on')->get(),
            'classArms' => ClassArm::query()->whereBelongsTo($school)->with('schoolClass.classGroup')->orderBy('name')->get(),
            'sourceSessionId' => $sourceSessionId,
            'enrollments' => $students,
        ]);
    }

    public function store(StoreStudentRolloverRequest $request, RolloverStudents $rolloverStudents): RedirectResponse
    {
        $count = $rolloverStudents->handle($request->user()->currentSchool, $request->validated());

        return back()->with('status', "{$count} student rollover decisions applied.");
    }

    private function schoolFor(Request $request): School
    {
        $school = $request->user()->currentSchool;
        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
