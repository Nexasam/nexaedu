<?php

namespace App\Actions\Schools;

use App\Models\AcademicSession;
use App\Models\AssessmentGroup;
use App\Models\ClassArm;
use App\Models\ClassTermSubject;
use App\Models\ReportCardRun;
use App\Models\ReportCardTemplate;
use App\Models\School;
use App\Models\StudentAssessmentScore;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\Term;
use App\Models\User;
use App\Services\Schools\CumulativeReportCardService;
use App\Services\Schools\ReportCardCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateReportCardRunAction
{
    public function __construct(
        private ReportCardCalculationService $reportCardCalculation,
        private CumulativeReportCardService $cumulativeReportCard,
    ) {}

    public function handle(
        School $school,
        ReportCardTemplate $template,
        AcademicSession $academicSession,
        Term $term,
        ClassArm $classArm,
        User $actor,
    ): ReportCardRun {
        $this->ensureReportingContextBelongsToSchool($school, $template, $academicSession, $term, $classArm);

        return DB::transaction(function () use ($school, $template, $academicSession, $term, $classArm, $actor): ReportCardRun {
            $run = ReportCardRun::query()->firstOrCreate(
                [
                    'school_id' => $school->id,
                    'report_card_template_id' => $template->id,
                    'academic_session_id' => $academicSession->id,
                    'term_id' => $term->id,
                    'class_arm_id' => $classArm->id,
                ],
                [
                    'status' => 'draft',
                    'generated_by' => $actor->id,
                    'generated_at' => now(),
                ],
            );

            if ($run->status !== 'draft') {
                throw ValidationException::withMessages([
                    'report_card_run' => 'Only draft report card runs can be regenerated.',
                ]);
            }

            $subjects = $this->subjectsForRun($school, $academicSession, $term, $classArm);
            $assessmentGroups = $this->assessmentGroupsForSubjects($school, $subjects);

            if ($assessmentGroups->count() !== $subjects->count() || $assessmentGroups->contains(fn (AssessmentGroup $group): bool => $group->completed_at === null)) {
                throw ValidationException::withMessages([
                    'report_card_run' => 'Complete every score sheet for this class arm before generating report cards.',
                ]);
            }

            $students = StudentEnrollment::query()
                ->whereBelongsTo($school)
                ->whereBelongsTo($academicSession)
                ->whereBelongsTo($term)
                ->whereBelongsTo($classArm)
                ->where('status', 'active')
                ->with(['studentProfile' => fn ($query) => $query->where('status', 'active')])
                ->get()
                ->pluck('studentProfile')
                ->filter()
                ->values();

            $snapshots = $this->snapshotsForStudents($school, $students, $subjects, $assessmentGroups, $template);
            $snapshots = $this->cumulativeReportCard->enrich($snapshots, $template, $academicSession, $term, $classArm);
            $snapshots = $this->assignPositions($snapshots);
            $snapshots = $this->addClassStatistics($snapshots);

            foreach ($students as $student) {
                $run->cards()->updateOrCreate(
                    ['student_profile_id' => $student->id],
                    [
                        'school_id' => $school->id,
                        'calculated_snapshot' => $snapshots->get($student->id),
                        'status' => 'draft',
                    ],
                );
            }

            $run->forceFill(['generated_by' => $actor->id, 'generated_at' => now()])->save();
            $run->events()->create([
                'school_id' => $school->id,
                'actor_id' => $actor->id,
                'event' => 'generated',
                'payload' => ['student_count' => $students->count()],
            ]);

            return $run->fresh(['cards']);
        });
    }

    private function ensureReportingContextBelongsToSchool(
        School $school,
        ReportCardTemplate $template,
        AcademicSession $academicSession,
        Term $term,
        ClassArm $classArm,
    ): void {
        if (
            $template->school_id !== $school->id
            || $academicSession->school_id !== $school->id
            || $term->school_id !== $school->id
            || $term->academic_session_id !== $academicSession->id
            || $classArm->school_id !== $school->id
        ) {
            throw ValidationException::withMessages([
                'report_card_run' => 'The selected reporting records must belong to the same school and academic session.',
            ]);
        }
    }

    /** @return Collection<int, ClassTermSubject> */
    private function subjectsForRun(School $school, AcademicSession $academicSession, Term $term, ClassArm $classArm): Collection
    {
        return ClassTermSubject::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($academicSession)
            ->whereBelongsTo($term)
            ->whereBelongsTo($classArm)
            ->with('schoolSubject.subject')
            ->orderBy('id')
            ->get();
    }

    /** @param Collection<int, ClassTermSubject> $subjects */
    private function assessmentGroupsForSubjects(School $school, Collection $subjects): Collection
    {
        return AssessmentGroup::query()
            ->whereBelongsTo($school)
            ->whereIn('class_term_subject_id', $subjects->pluck('id'))
            ->with(['items' => fn ($query) => $query->orderBy('sort_order')])
            ->latest('id')
            ->get()
            ->unique('class_term_subject_id')
            ->keyBy('class_term_subject_id');
    }

    /**
     * @param  Collection<int, StudentProfile>  $students
     * @param  Collection<int, ClassTermSubject>  $subjects
     * @param  Collection<int, AssessmentGroup>  $assessmentGroups
     * @return Collection<int, array<string, mixed>>
     */
    private function snapshotsForStudents(School $school, Collection $students, Collection $subjects, Collection $assessmentGroups, ReportCardTemplate $template): Collection
    {
        $scores = StudentAssessmentScore::query()
            ->whereBelongsTo($school)
            ->whereIn('student_profile_id', $students->pluck('id'))
            ->whereIn('class_term_subject_id', $subjects->pluck('id'))
            ->get(['student_profile_id', 'assessment_item_id', 'score'])
            ->groupBy('student_profile_id');
        $bands = $template->gradingScheme->bands
            ->map(fn ($band) => [
                'minimum_score' => $band->minimum_score,
                'maximum_score' => $band->maximum_score,
                'grade' => $band->grade,
                'remark' => $band->remark,
            ]);

        return $students->mapWithKeys(function ($student) use ($assessmentGroups, $bands, $scores, $subjects): array {
            $subjectResults = $subjects->map(function (ClassTermSubject $subject) use ($assessmentGroups, $bands, $scores, $student): array {
                $group = $assessmentGroups->get($subject->id);
                $result = $this->reportCardCalculation->calculateSubjectScore(
                    $group->items->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'raw_max_score' => $item->rawMaximumScore(),
                        'weight' => $item->reportWeight(),
                    ]),
                    ($scores->get($student->id) ?? collect())
                        ->map(fn ($score) => ['assessment_item_id' => $score->assessment_item_id, 'score' => $score->score]),
                );
                $percentage = $result['maximum_score'] > 0 ? round($result['score'] / $result['maximum_score'] * 100, 2) : 0.0;
                $grade = $this->reportCardCalculation->gradeFor($percentage, $bands);

                return [
                    'class_term_subject_id' => $subject->id,
                    'school_subject_id' => $subject->school_subject_id,
                    'name' => $subject->schoolSubject?->custom_name ?: $subject->schoolSubject?->subject?->name ?: 'Subject',
                    'score' => $result['score'],
                    'maximum_score' => $result['maximum_score'],
                    'percentage' => $percentage,
                    'assessment_items' => $result['assessment_items'],
                    ...$grade,
                ];
            });
            $total = round((float) $subjectResults->sum('score'), 2);
            $maximum = round((float) $subjectResults->sum('maximum_score'), 2);

            return [$student->id => [
                'subjects' => $subjectResults->values()->all(),
                'summary' => [
                    'total' => $total,
                    'maximum_score' => $maximum,
                    'average' => $maximum > 0 ? round($total / $maximum * 100, 2) : 0.0,
                    'position' => null,
                ],
            ]];
        });
    }

    /** @param Collection<int, array<string, mixed>> $snapshots */
    private function assignPositions(Collection $snapshots): Collection
    {
        $lastTotal = null;
        $position = 0;
        $rank = 0;

        return $snapshots->sortByDesc(fn (array $snapshot): float => (float) ($snapshot['summary']['ranking_score'] ?? $snapshot['summary']['total']))
            ->map(function (array $snapshot) use (&$lastTotal, &$position, &$rank): array {
                $rank++;
                $total = (float) ($snapshot['summary']['ranking_score'] ?? $snapshot['summary']['total']);

                if ($lastTotal === null || $total !== $lastTotal) {
                    $position = $rank;
                    $lastTotal = $total;
                }

                $snapshot['summary']['position'] = $position;

                return $snapshot;
            });
    }

    /** @param Collection<int, array<string, mixed>> $snapshots */
    private function addClassStatistics(Collection $snapshots): Collection
    {
        $subjectIds = $snapshots
            ->flatMap(fn (array $snapshot): array => $snapshot['subjects'])
            ->pluck('class_term_subject_id')
            ->unique();
        $subjectStatistics = $snapshots
            ->flatMap(fn (array $snapshot): array => $snapshot['subjects'])
            ->groupBy('class_term_subject_id')
            ->map(fn (Collection $results): array => [
                'class_average' => round((float) $results->avg('percentage'), 2),
                'highest' => round((float) $results->max('percentage'), 2),
                'lowest' => round((float) $results->min('percentage'), 2),
            ]);
        $subjectPositions = $subjectIds->mapWithKeys(function (int $subjectId) use ($snapshots): array {
            $scores = $snapshots->mapWithKeys(function (array $snapshot, int $studentId) use ($subjectId): array {
                $subject = collect($snapshot['subjects'])->firstWhere('class_term_subject_id', $subjectId);

                return $subject === null ? [] : [$studentId => (float) $subject['percentage']];
            });

            return [$subjectId => $this->reportCardCalculation->positionsByScore($scores)];
        });
        $overallClassAverage = round((float) $snapshots->avg('summary.average'), 2);

        return $snapshots->map(function (array $snapshot, int $studentId) use ($overallClassAverage, $subjectPositions, $subjectStatistics): array {
            $snapshot['subjects'] = collect($snapshot['subjects'])
                ->map(function (array $subject) use ($studentId, $subjectPositions, $subjectStatistics): array {
                    $subject['class_statistics'] = $subjectStatistics->get($subject['class_term_subject_id']);
                    $subject['position'] = $subjectPositions->get($subject['class_term_subject_id'])?->get($studentId);

                    return $subject;
                })
                ->all();
            $snapshot['summary']['class_average'] = $overallClassAverage;

            return $snapshot;
        });
    }
}
