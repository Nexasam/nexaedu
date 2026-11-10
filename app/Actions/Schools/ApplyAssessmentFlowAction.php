<?php

namespace App\Actions\Schools;

use App\Models\AssessmentGroup;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplyAssessmentFlowAction
{
    /**
     * @param  Collection<int, int>  $classTermSubjectIds
     * @param  array{name: string, items: array<int, array{name: string, max_score: int|float, raw_max_score?: int|float|null, weight?: int|float|null}>}  $data
     */
    public function handle(School $school, Collection $classTermSubjectIds, array $data): int
    {
        $totalScore = collect($data['items'])->sum(fn (array $item): float => (float) ($item['weight'] ?? $item['raw_max_score'] ?? $item['max_score']));

        DB::transaction(function () use ($school, $data, $totalScore, $classTermSubjectIds): void {
            foreach ($classTermSubjectIds as $classTermSubjectId) {
                $group = AssessmentGroup::query()
                    ->whereBelongsTo($school)
                    ->where('class_term_subject_id', $classTermSubjectId)
                    ->latest()
                    ->first();

                if ($group) {
                    $group->update([
                        'name' => $data['name'],
                        'total_score' => $totalScore,
                    ]);
                } else {
                    $group = $school->assessmentGroups()->create([
                        'class_term_subject_id' => $classTermSubjectId,
                        'name' => $data['name'],
                        'total_score' => $totalScore,
                    ]);
                }

                $itemNames = collect($data['items'])->pluck('name');

                foreach ($data['items'] as $index => $item) {
                    $rawMaximumScore = $item['raw_max_score'] ?? $item['max_score'];
                    $reportWeight = $item['weight'] ?? $rawMaximumScore;

                    $group->items()->updateOrCreate(
                        ['name' => $item['name']],
                        [
                            'school_id' => $school->id,
                            // max_score remains the score-entry ceiling for backwards compatibility.
                            'max_score' => $rawMaximumScore,
                            'raw_max_score' => $rawMaximumScore,
                            'weight' => $reportWeight,
                            'sort_order' => $index + 1,
                        ]
                    );
                }

                $group->items()->whereNotIn('name', $itemNames)->delete();
            }
        });

        return $classTermSubjectIds->count();
    }
}
