<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\Schools\SchoolReadinessReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolReadinessReportController extends Controller
{
    public function __construct(private SchoolReadinessReport $schoolReadinessReport) {}

    public function __invoke(Request $request): Response
    {
        $school = $this->authorizeSchool($request);

        return Inertia::render('SchoolAdmin/Reports/Readiness', [
            'report' => $this->schoolReadinessReport->build($school),
        ]);
    }

    private function authorizeSchool(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);

        return $school;
    }
}
