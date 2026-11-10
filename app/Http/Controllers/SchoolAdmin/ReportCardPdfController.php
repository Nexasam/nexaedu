<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardRun;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Services\Schools\ReportCardSnapshotPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReportCardPdfController extends Controller
{
    public function __construct(private ReportCardSnapshotPresenter $snapshotPresenter) {}

    public function __invoke(Request $request, ReportCardRun $reportCardRun): Response
    {
        $school = $this->school($request);
        abort_unless($reportCardRun->school_id === $school->id, 404);

        $reportCardRun->load(['academicSession', 'term', 'classArm.schoolClass.classGroup']);
        $template = ReportCardTemplate::query()
            ->whereBelongsTo($school)
            ->where('is_active', true)
            ->whereKey($request->integer('template') ?: $reportCardRun->report_card_template_id)
            ->with('gradingScheme.bands')
            ->firstOrFail();
        abort_unless($template->class_group_id === null || $template->class_group_id === $reportCardRun->classArm->schoolClass->class_group_id, 404);

        $cards = $reportCardRun->cards()
            ->with(['student', 'ratings.traitDefinition'])
            ->orderBy('id')
            ->get();
        $cards = $this->snapshotPresenter->enrichMissingClassStatistics($cards);
        $card = $cards->firstWhere('id', $request->integer('card')) ?? $cards->firstOrFail();
        $studentName = trim("{$card->student->first_name} {$card->student->last_name}");

        return Pdf::loadView('pdf.report-card', [
            'school' => $school,
            'run' => $reportCardRun,
            'template' => $template,
            'card' => $card,
            'snapshot' => $card->calculated_snapshot ?? [],
            'logoDataUri' => $this->logoDataUri($school),
            'signatureDataUris' => collect(['class_teacher', 'principal'])->mapWithKeys(fn (string $type): array => [
                $type => $this->signatureDataUri(data_get($card->signature_snapshot, "{$type}.path")),
            ])->all(),
        ])->setPaper('a4')->stream("{$studentName}-report-card.pdf");
    }

    private function school(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);

        return $school;
    }

    private function logoDataUri(School $school): ?string
    {
        if (! $school->logo_path || ! Storage::disk('public')->exists($school->logo_path)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($school->logo_path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode(Storage::disk('public')->get($school->logo_path));
    }

    private function signatureDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mimeType = Storage::disk('local')->mimeType($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
