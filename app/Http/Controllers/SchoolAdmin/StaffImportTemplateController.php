<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\Schools\CsvTemplateDownload;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffImportTemplateController extends Controller
{
    public function __invoke(Request $request, CsvTemplateDownload $download): StreamedResponse
    {
        $school = $this->schoolFor($request);
        $role = $school->roles()->where('slug', 'teacher')->value('slug')
            ?? $school->roles()->orderBy('name')->value('slug')
            ?? 'teacher';

        return $download->response(
            Str::slug($school->name).'-staff-import-template.csv',
            [
                'staff_no', 'first_name', 'last_name', 'middle_name', 'email', 'phone',
                'gender', 'role', 'qualification', 'specialization', 'department',
                'job_title', 'address', 'emergency_contact_name', 'emergency_contact_phone',
                'employment_date', 'employment_type', 'send_invite',
            ],
            [
                'STAFF-001', 'Amina', 'Bello', '', 'amina.bello@example.com', '08030000000',
                'female', $role, 'B.Ed', 'Mathematics', 'Academics', 'Class Teacher',
                '12 School Road', 'Musa Bello', '08040000000', now()->format('Y-m-d'),
                'full-time', '0',
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
