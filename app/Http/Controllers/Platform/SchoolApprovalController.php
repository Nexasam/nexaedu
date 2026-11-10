<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Schools\ApproveSchoolAction;
use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Schools/Index', [
            'schools' => School::query()
                ->with(['owner', 'domains'])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request, School $school, ApproveSchoolAction $approveSchool): RedirectResponse
    {
        $approveSchool->handle($school, $request->user());

        return back()->with('status', "{$school->name} has been approved.");
    }
}
