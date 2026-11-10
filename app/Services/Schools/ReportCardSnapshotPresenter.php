<?php

namespace App\Services\Schools;

use App\Models\StudentReportCard;
use Illuminate\Support\Collection;

class ReportCardSnapshotPresenter
{
    public function __construct(private ReportCardCalculationService $reportCardCalculation) {}

    /** @param Collection<int, StudentReportCard> $cards */
    public function enrichMissingClassStatistics(Collection $cards): Collection
    {
        $subjectStatistics = $cards
            ->flatMap(fn (StudentReportCard $card): array => $card->calculated_snapshot['subjects'] ?? [])
            ->groupBy('class_term_subject_id')
            ->map(fn (Collection $subjects): array => [
                'class_average' => round((float) $subjects->avg('percentage'), 2),
                'highest' => round((float) $subjects->max('percentage'), 2),
                'lowest' => round((float) $subjects->min('percentage'), 2),
            ]);
        $classAverage = round((float) $cards->avg(
            fn (StudentReportCard $card): float => (float) data_get($card->calculated_snapshot, 'summary.average', 0)
        ), 2);
        $subjectPositions = $subjectStatistics->keys()->mapWithKeys(function (int $subjectId) use ($cards): array {
            $scores = $cards->mapWithKeys(function (StudentReportCard $card) use ($subjectId): array {
                $subject = collect($card->calculated_snapshot['subjects'] ?? [])->firstWhere('class_term_subject_id', $subjectId);

                return $subject === null ? [] : [$card->id => (float) $subject['percentage']];
            });

            return [$subjectId => $this->reportCardCalculation->positionsByScore($scores)];
        });

        return $cards->each(function (StudentReportCard $card) use ($classAverage, $subjectPositions, $subjectStatistics): void {
            $snapshot = $card->calculated_snapshot ?? [];
            $snapshot['summary']['class_average'] ??= $classAverage;
            $snapshot['subjects'] = collect($snapshot['subjects'] ?? [])
                ->map(function (array $subject) use ($card, $subjectPositions, $subjectStatistics): array {
                    $subject['class_statistics'] ??= $subjectStatistics->get($subject['class_term_subject_id']);
                    $subject['position'] ??= $subjectPositions->get($subject['class_term_subject_id'])?->get($card->id);

                    return $subject;
                })
                ->all();

            $card->setAttribute('calculated_snapshot', $snapshot);
        });
    }
}
