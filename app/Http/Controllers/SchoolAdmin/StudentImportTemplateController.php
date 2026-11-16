<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\Schools\CsvTemplateDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportTemplateController extends Controller
{
    public function __invoke(Request $request, CsvTemplateDownload $download): StreamedResponse
    {
        $school = $this->schoolFor($request);
        $classArm = $school->classArms()->orderBy('name')->first();
        $session = $school->academicSessions()->orderByDesc('is_current')->orderByDesc('starts_on')->first();
        $term = $session?->terms()->orderByDesc('is_current')->orderBy('starts_on')->first();

        return $download->response(
            Str::slug($school->name).'-student-import-template.csv',
            [
                'admission_no', 'first_name', 'last_name', 'gender', 'date_of_birth',
                'class_arm_id', 'academic_session_id', 'term_id', 'parent_first_name',
                'parent_last_name', 'parent_email', 'parent_phone', 'parent_relationship',
            ],
            [
                'ADM-001', 'David', 'Ojo', 'male', '2018-02-10', $classArm?->id,
                $session?->id, $term?->id, 'Grace', 'Ojo', 'grace.ojo@example.com',
                '08020000000', 'mother',
            ],
        );
    }

    private function schoolFor(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
