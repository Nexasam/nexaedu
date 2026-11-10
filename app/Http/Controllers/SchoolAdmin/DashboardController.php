<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Services\Schools\SchoolPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private SchoolPermissionService $schoolPermissionService) {}

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->global_role === 'platform_admin') {
            return redirect()->route('platform.schools.index');
        }

        $school = $user->currentSchool()
            ->with(['domains', 'classGroups'])
            ->first();

        if ($school === null) {
            return redirect()->route('school.onboarding.create');
        }

        if ($school->status !== 'approved') {
            return redirect()->route('school.onboarding.pending');
        }

        abort_unless($this->schoolPermissionService->can($user, $school, 'dashboard', 'readonly'), 403);

        return Inertia::render('Dashboard', [
            'school' => $school,
            'setup' => [
                'classGroups' => $school->classGroups()->count(),
                'classes' => $school->classes()->count(),
                'classArms' => $school->classArms()->count(),
                'sessions' => $school->academicSessions()->count(),
                'subjects' => $school->schoolSubjects()->count(),
                'assessments' => $school->assessmentGroups()->count(),
                'staff' => $school->staffProfiles()->count(),
                'students' => $school->studentProfiles()->count(),
            ],
        ]);
    }
}
