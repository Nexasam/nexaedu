<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentSubjectController extends Controller
{
    public function index(Request $request): Response
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        return Inertia::render('SchoolAdmin/Students/Subjects', [
            'students' => StudentProfile::query()
                ->whereBelongsTo($school)
                ->with([
                    'enrollments' => fn ($query) => $query->latest(),
                    'enrollments.classArm.schoolClass.classGroup',
                    'enrollments.academicSession',
                    'enrollments.term',
                ])
                ->latest()
                ->get(),
            'classTermSubjects' => $school->classTermSubjects()
                ->with(['academicSession', 'term', 'classArm.schoolClass.classGroup', 'schoolSubject.subject', 'teacherAssignments.teacherProfile.staffProfile'])
                ->latest()
                ->get(),
        ]);
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
