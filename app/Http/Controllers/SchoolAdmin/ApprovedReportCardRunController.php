<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovedReportCardRunController extends Controller
{
    public function store(Request $request, ReportCardRun $reportCardRun): RedirectResponse
    {
        $school = $request->user()->currentSchool;
        abort_unless($school && $reportCardRun->school_id === $school->id, 404);

        if ($reportCardRun->cards()->where('status', '!=', 'prepared')->exists() || ! $reportCardRun->cards()->exists()) {
            throw ValidationException::withMessages([
                'report_card_run' => 'Every student card must be prepared before this class result can be approved.',
            ]);
        }

        DB::transaction(function () use ($reportCardRun, $request, $school): void {
            $reportCardRun->cards()->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
            $reportCardRun->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
            $reportCardRun->events()->create([
                'school_id' => $school->id,
                'actor_id' => $request->user()->id,
                'event' => 'approved',
            ]);
        });

        return back()->with('status', 'Class results approved and locked from preparation changes.');
    }

    public function destroy(Request $request, ReportCardRun $reportCardRun): RedirectResponse
    {
        $school = $request->user()->currentSchool;
        abort_unless($school && $reportCardRun->school_id === $school->id, 404);
        abort_unless($reportCardRun->status === 'approved', 422);

        DB::transaction(function () use ($reportCardRun, $request, $school): void {
            $reportCardRun->cards()->update(['status' => 'prepared', 'approved_by' => null, 'approved_at' => null]);
            $reportCardRun->update(['status' => 'draft', 'approved_by' => null, 'approved_at' => null]);
            $reportCardRun->events()->create([
                'school_id' => $school->id,
                'actor_id' => $request->user()->id,
                'event' => 'approval_revoked',
            ]);
        });

        return back()->with('status', 'Approval revoked. The prepared report cards can be edited again.');
    }
}
