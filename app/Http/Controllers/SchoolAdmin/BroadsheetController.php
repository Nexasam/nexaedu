<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardRun;
use App\Services\Schools\BroadsheetService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BroadsheetController extends Controller
{
    public function __construct(private BroadsheetService $broadsheet) {}

    public function index(Request $request): Response
    {
        $school = $request->user()->currentSchool;
        abort_if($school === null, 403);
        $runs = ReportCardRun::query()
            ->whereBelongsTo($school)
            ->with(['academicSession', 'term', 'classArm.schoolClass.classGroup', 'template'])
            ->latest('generated_at')
            ->get();
        $selectedRun = $request->integer('run')
            ? $runs->firstWhere('id', $request->integer('run'))
            : $runs->first();
        $sheet = $selectedRun ? $this->broadsheet->build($selectedRun) : ['subjects' => collect(), 'rows' => collect()];

        return Inertia::render('SchoolAdmin/Reports/Broadsheet', [
            'runs' => $runs,
            'selectedRun' => $selectedRun,
            ...$sheet,
        ]);
    }

    public function export(Request $request, ReportCardRun $reportCardRun): StreamedResponse
    {
        $school = $request->user()->currentSchool;
        abort_unless($school && $reportCardRun->school_id === $school->id, 404);
        $reportCardRun->load(['academicSession', 'term', 'classArm']);
        $sheet = $this->broadsheet->build($reportCardRun);
        $filename = str($reportCardRun->classArm->name.'-'.$reportCardRun->term->name.'-broadsheet')->slug('-').'.csv';

        return response()->streamDownload(function () use ($sheet): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, array_merge(
                ['Student', 'Admission no'],
                $sheet['subjects']->map(fn (array $subject): string => $subject['name'].' %')->all(),
                ['Term average', 'Cumulative average', 'Position', 'Card status']
            ));
            foreach ($sheet['rows'] as $row) {
                fputcsv($output, array_merge(
                    [$row['student_name'], $row['admission_no']],
                    collect($row['subjects'])->map(fn (array $subject) => $subject['cumulative_percentage'] ?? $subject['percentage'])->all(),
                    [$row['overall_percentage'], $row['cumulative_average'], $row['position'], $row['card_status']]
                ));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
