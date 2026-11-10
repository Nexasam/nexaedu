<?php

namespace App\Actions\Schools;

use App\Models\AssessmentGroup;
use App\Models\School;
use App\Models\StudentAssessmentScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveStudentScoresAction
{
    /**
     * @param  Collection<int, array{student_profile_id: int, items: array<int, array{assessment_item_id: int, score: mixed}>}>  $rows
     * @param  Collection<int, int>  $eligibleStudentIds
     */
    public function handle(School $school, AssessmentGroup $group, Collection $rows, Collection $eligibleStudentIds, int $recordedBy, bool $canOverrideCompleted = false): void
    {
        if ($group->completed_at !== null && ! $canOverrideCompleted) {
            throw ValidationException::withMessages([
                'scores' => 'This score sheet has been marked as completed and can only be changed by the school super admin.',
            ]);
        }

        $items = $group->items->keyBy('id');

        DB::transaction(function () use ($school, $group, $rows, $eligibleStudentIds, $items, $recordedBy): void {
            foreach ($rows as $row) {
                $studentId = (int) $row['student_profile_id'];

                if (! $eligibleStudentIds->contains($studentId)) {
                    throw ValidationException::withMessages([
                        'scores' => 'One or more students do not belong to this class arm for the selected term.',
                    ]);
                }

                foreach ($row['items'] as $scoreRow) {
                    $item = $items->get((int) $scoreRow['assessment_item_id']);
                    $score = $scoreRow['score'];

                    if (! $item) {
                        throw ValidationException::withMessages(['scores' => 'One or more assessment items are invalid for this flow.']);
                    }

                    if ($score === null || $score === '') {
                        continue;
                    }

                    if ((float) $score > $item->rawMaximumScore()) {
                        throw ValidationException::withMessages([
                            'scores' => "{$item->name} cannot be greater than {$item->rawMaximumScore()}.",
                        ]);
                    }

                    StudentAssessmentScore::query()->updateOrCreate(
                        [
                            'student_profile_id' => $studentId,
                            'assessment_item_id' => $item->id,
                        ],
                        [
                            'school_id' => $school->id,
                            'class_term_subject_id' => $group->class_term_subject_id,
                            'assessment_group_id' => $group->id,
                            'recorded_by' => $recordedBy,
                            'score' => $score,
                        ]
                    );
                }
            }
        });
    }
}
