<?php

namespace App\Actions\Schools;

use App\Models\ReportCardRun;
use App\Models\ReportCardTraitDefinition;
use App\Models\School;
use App\Models\StudentReportCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStudentReportCardAction
{
    public function __construct(private SnapshotReportCardSignaturesAction $snapshotSignatures) {}

    /**
     * @param  array{school_days_opened?: int|null, days_present?: int|null, days_absent?: int|null, times_late?: int|null, class_teacher_comment?: string|null, principal_comment?: string|null, promotion_decision: string, next_term_starts_on?: string|null, ratings?: array<int, array{trait_definition_id: int, rating: string|null}>}  $attributes
     */
    public function handle(School $school, ReportCardRun $reportCardRun, StudentReportCard $studentReportCard, array $attributes, User $actor): StudentReportCard
    {
        if (
            $reportCardRun->school_id !== $school->id
            || $studentReportCard->school_id !== $school->id
            || $studentReportCard->report_card_run_id !== $reportCardRun->id
        ) {
            throw ValidationException::withMessages([
                'report_card' => 'The report card must belong to the selected school report run.',
            ]);
        }

        if ($reportCardRun->status !== 'draft') {
            throw ValidationException::withMessages([
                'report_card' => 'Only draft report-card runs can be prepared.',
            ]);
        }

        return DB::transaction(function () use ($actor, $attributes, $reportCardRun, $school, $studentReportCard): StudentReportCard {
            $ratings = $attributes['ratings'] ?? [];
            unset($attributes['ratings']);
            $isPrepared = filled($attributes['school_days_opened'] ?? null)
                && filled($attributes['days_present'] ?? null)
                && filled($attributes['days_absent'] ?? null)
                && ($attributes['promotion_decision'] ?? 'pending') !== 'pending';

            $studentReportCard->fill([
                ...$attributes,
                'status' => $isPrepared ? 'prepared' : 'draft',
                'signature_snapshot' => $isPrepared ? $this->snapshotSignatures->handle($school, $reportCardRun) : null,
            ])->save();

            $classGroupId = $reportCardRun->classArm()->with('schoolClass')->firstOrFail()->schoolClass->class_group_id;
            $traitDefinitions = ReportCardTraitDefinition::query()
                ->where('school_id', $studentReportCard->school_id)
                ->whereIn('id', collect($ratings)->pluck('trait_definition_id'))
                ->where(function ($query) use ($classGroupId): void {
                    $query->whereNull('class_group_id')->orWhere('class_group_id', $classGroupId);
                })
                ->get()
                ->keyBy('id');

            foreach ($ratings as $rating) {
                $traitDefinition = $traitDefinitions->get($rating['trait_definition_id']);

                if ($traitDefinition === null) {
                    throw ValidationException::withMessages([
                        'ratings' => 'One or more personal-development traits are not available for this class group.',
                    ]);
                }

                if (blank($rating['rating'] ?? null)) {
                    $studentReportCard->ratings()->where('report_card_trait_definition_id', $traitDefinition->id)->delete();

                    continue;
                }

                if (! in_array($rating['rating'], $traitDefinition->rating_options ?? [], true)) {
                    throw ValidationException::withMessages([
                        'ratings' => "{$rating['rating']} is not a valid rating for {$traitDefinition->name}.",
                    ]);
                }

                $studentReportCard->ratings()->updateOrCreate(
                    ['report_card_trait_definition_id' => $traitDefinition->id],
                    ['school_id' => $studentReportCard->school_id, 'rating' => $rating['rating']],
                );
            }

            $reportCardRun->events()->create([
                'school_id' => $studentReportCard->school_id,
                'student_report_card_id' => $studentReportCard->id,
                'actor_id' => $actor->id,
                'event' => 'prepared',
                'payload' => ['status' => $studentReportCard->status],
            ]);

            return $studentReportCard->fresh(['student', 'ratings']);
        });
    }
}
