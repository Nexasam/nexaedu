<?php

namespace App\Services\Schools;

use Illuminate\Support\Collection;

class ReportCardCalculationService
{
    /**
     * Assign competition ranks to scores keyed by student ID.
     *
     * @param  Collection<int, int|float>  $scores
     * @return Collection<int, int>
     */
    public function positionsByScore(Collection $scores): Collection
    {
        $lastScore = null;
        $position = 0;
        $rank = 0;

        return $scores->sortDesc()->map(function (int|float $score) use (&$lastScore, &$position, &$rank): int {
            $rank++;
            $numericScore = (float) $score;

            if ($lastScore === null || $numericScore !== $lastScore) {
                $position = $rank;
                $lastScore = $numericScore;
            }

            return $position;
        });
    }

    /**
     * @param  Collection<int, array{id: int, name?: string, raw_max_score: int|float, weight: int|float}>  $items
     * @param  Collection<int, array{assessment_item_id: int, score: int|float|null}>  $scores
     * @return array{score: float, maximum_score: float, assessment_items: array<int, array<string, float|int|string>>}
     */
    public function calculateSubjectScore(Collection $items, Collection $scores): array
    {
        $scoresByItem = $scores->keyBy('assessment_item_id');
        $maximumScore = (float) $items->sum('weight');
        $assessmentItems = $items->map(function (array $item) use ($scoresByItem): array {
            $rawMaximumScore = (float) $item['raw_max_score'];
            $rawScore = (float) ($scoresByItem->get($item['id'])['score'] ?? 0);
            $weightedScore = $rawMaximumScore > 0
                ? $rawScore / $rawMaximumScore * (float) $item['weight']
                : 0.0;

            return [
                'id' => $item['id'],
                'name' => $item['name'] ?? 'Assessment',
                'raw_score' => round($rawScore, 2),
                'raw_max_score' => $rawMaximumScore,
                'weight' => (float) $item['weight'],
                'weighted_score' => round($weightedScore, 2),
            ];
        })->values();

        return [
            'score' => round((float) $assessmentItems->sum('weighted_score'), 2),
            'maximum_score' => $maximumScore,
            'assessment_items' => $assessmentItems->all(),
        ];
    }

    /**
     * @param  Collection<int, array{minimum_score: int|float|string, maximum_score: int|float|string, grade: string, remark: string|null}>  $bands
     * @return array{grade: string, remark: string|null}
     */
    public function gradeFor(float $score, Collection $bands): array
    {
        $band = $bands->first(function (array $band) use ($score): bool {
            return $score >= (float) $band['minimum_score'] && $score <= (float) $band['maximum_score'];
        });

        return [
            'grade' => $band['grade'] ?? 'Ungraded',
            'remark' => $band['remark'] ?? null,
        ];
    }
}
