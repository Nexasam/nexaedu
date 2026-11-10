<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardRun;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Services\Schools\ReportCardSnapshotPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportCardPreviewController extends Controller
{
    public function __construct(private ReportCardSnapshotPresenter $snapshotPresenter) {}

    public function __invoke(Request $request, ReportCardRun $reportCardRun): Response
    {
        $school = $this->school($request);
        abort_unless($reportCardRun->school_id === $school->id, 404);

        $reportCardRun->load([
            'template',
            'academicSession',
            'term',
            'classArm.schoolClass.classGroup',
        ]);
        $classGroupId = $reportCardRun->classArm->schoolClass->class_group_id;
        $templates = ReportCardTemplate::query()
            ->whereBelongsTo($school)
            ->where('is_active', true)
            ->where(function ($query) use ($classGroupId): void {
                $query->whereNull('class_group_id')->orWhere('class_group_id', $classGroupId);
            })
            ->orderBy('name')
            ->get();
        $selectedTemplate = $templates->firstWhere('id', $request->integer('template'))
            ?? $templates->firstWhere('id', $reportCardRun->report_card_template_id)
            ?? $templates->firstOrFail();
        $selectedTemplate->loadMissing('gradingScheme.bands');
        $cards = $reportCardRun->cards()
            ->with(['student', 'ratings.traitDefinition'])
            ->orderBy('id')
            ->get();
        $cards = $this->snapshotPresenter->enrichMissingClassStatistics($cards);
        $cards->each(function ($card): void {
            $card->setAttribute('signature_urls', collect(['class_teacher', 'principal'])
                ->filter(fn (string $type): bool => filled(data_get($card->signature_snapshot, "{$type}.path")))
                ->mapWithKeys(fn (string $type): array => [$type => route('school.report-cards.signature', [$card->id, $type])])
                ->all());
        });
        $allPrepared = $cards->isNotEmpty() && $cards->every(
            fn ($card): bool => in_array($card->status, ['prepared', 'approved'], true)
        );
        $mode = $request->string('mode')->toString() === 'all' ? 'all' : 'student';

        if ($mode === 'student') {
            $selectedCard = $cards->firstWhere('id', $request->integer('card')) ?? $cards->firstOrFail();
            $cards = collect([$selectedCard]);
        }

        return Inertia::render('SchoolAdmin/ReportCards/Preview', [
            'run' => $reportCardRun,
            'cards' => $cards->values(),
            'mode' => $mode,
            'allPrepared' => $allPrepared,
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'schoolProfile' => [
                'name' => $school->name,
                'code' => $school->code,
                'address' => $school->address,
                'phone' => $school->phone,
                'email' => $school->email,
                'logo_url' => $school->logoUrl(),
            ],
        ]);
    }

    private function school(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);

        return $school;
    }
}
