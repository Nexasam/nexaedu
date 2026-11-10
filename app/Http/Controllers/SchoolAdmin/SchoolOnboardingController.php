<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Actions\Schools\RegisterSchoolAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolAdmin\StoreSchoolOnboardingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolOnboardingController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->current_school_id !== null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('SchoolAdmin/Onboarding/Create');
    }

    public function store(StoreSchoolOnboardingRequest $request, RegisterSchoolAction $registerSchool): RedirectResponse
    {
        $school = $registerSchool->handle($request->user(), $request->validated());

        return redirect()
            ->route('school.onboarding.pending')
            ->with('status', "School {$school->name} has been submitted for platform approval.");
    }

    public function pending(Request $request): Response
    {
        $school = $request->user()
            ->ownedSchools()
            ->with('domains')
            ->latest()
            ->first();

        return Inertia::render('SchoolAdmin/Onboarding/Pending', [
            'school' => $school,
        ]);
    }
}
