<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolAdmin\UpdateReportCardPolicyRequest;
use App\Models\ReportCardTemplate;
use Illuminate\Http\RedirectResponse;

class ReportCardPolicyController extends Controller
{
    public function __invoke(UpdateReportCardPolicyRequest $request, ReportCardTemplate $reportCardTemplate): RedirectResponse
    {
        $school = $request->user()->currentSchool;
        abort_unless($reportCardTemplate->school_id === $school->id, 404);

        $validated = $request->validated();
        $reportCardTemplate->update([
            'term_result_mode' => $validated['term_result_mode'],
            'term_weights' => $validated['term_result_mode'] === 'cumulative'
                ? collect($validated['term_weights'])->map(fn ($weight): float => (float) $weight)->values()->all()
                : null,
        ]);

        return back()->with('status', 'Reporting policy updated. New report runs will use this term calculation mode.');
    }
}
