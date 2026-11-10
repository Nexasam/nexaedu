<?php

namespace App\Services\Schools;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ReportCardRun;
use App\Models\ReportCardTemplate;
use App\Models\Term;
use Illuminate\Support\Collection;

class CumulativeReportCardService
{
    /** @param Collection<int, array<string, mixed>> $snapshots */
    public function enrich(
        Collection $snapshots,
        ReportCardTemplate $template,
        AcademicSession $academicSession,
        Term $currentTerm,
        ClassArm $classArm,
    ): Collection {
        if ($template->term_result_mode !== 'cumulative') {
            return $snapshots;
        }

        $terms = Term::query()
            ->whereBelongsTo($academicSession)
            ->where(fn ($query) => $query
                ->whereDate('starts_on', '<=', $currentTerm->starts_on)
                ->orWhere('id', $currentTerm->id))
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();
        $termIndexes = $terms->pluck('id')->flip();
        $weights = collect($template->term_weights ?? [])->values();
        $previousCards = ReportCardRun::query()
            ->whereBelongsTo($academicSession)
            ->whereBelongsTo($classArm)
            ->whereIn('term_id', $terms->where('id', '!=', $currentTerm->id)->pluck('id'))
            ->with(['term', 'cards'])
            ->get()
            ->sortBy(fn (ReportCardRun $run): int => $termIndexes->get($run->term_id, 999))
            ->flatMap(fn (ReportCardRun $run) => $run->cards->map(fn ($card): array => [
                'term_id' => $run->term_id,
                'term_name' => $run->term->name,
                'student_id' => $card->student_profile_id,
                'subjects' => $card->calculated_snapshot['subjects'] ?? [],
            ]));

        return $snapshots->map(function (array $snapshot, int $studentId) use ($currentTerm, $previousCards, $termIndexes, $terms, $weights): array {
            $priorForStudent = $previousCards->where('student_id', $studentId);
            $snapshot['subjects'] = collect($snapshot['subjects'])->map(function (array $subject) use ($currentTerm, $priorForStudent, $termIndexes, $terms, $weights): array {
                $termResults = $priorForStudent->map(function (array $prior) use ($subject): ?array {
                    $matchingSubject = collect($prior['subjects'])->first(
                        fn (array $candidate): bool => isset($candidate['school_subject_id'])
                            ? (int) $candidate['school_subject_id'] === (int) $subject['school_subject_id']
                            : $candidate['name'] === $subject['name']
                    );

                    return $matchingSubject === null ? null : [
                        'term_id' => $prior['term_id'],
                        'term_name' => $prior['term_name'],
                        'percentage' => (float) $matchingSubject['percentage'],
                    ];
                })->filter()->push([
                    'term_id' => $currentTerm->id,
                    'term_name' => $currentTerm->name,
                    'percentage' => (float) $subject['percentage'],
                ])->sortBy(fn (array $result): int => $termIndexes->get($result['term_id'], 999))->values();
                $weightedTotal = $termResults->sum(function (array $result) use ($termIndexes, $weights): float {
                    $weight = (float) ($weights->get($termIndexes->get($result['term_id'])) ?? 1);

                    return $result['percentage'] * $weight;
                });
                $weightTotal = $termResults->sum(fn (array $result): float => (float) ($weights->get($termIndexes->get($result['term_id'])) ?? 1));
                $subject['term_results'] = $termResults->all();
                $subject['cumulative_percentage'] = $weightTotal > 0 ? round($weightedTotal / $weightTotal, 2) : 0.0;
                $subject['cumulative_complete'] = $termResults->count() === $terms->count();

                return $subject;
            })->all();
            $snapshot['summary']['cumulative_average'] = round((float) collect($snapshot['subjects'])->avg('cumulative_percentage'), 2);
            $snapshot['summary']['ranking_score'] = $snapshot['summary']['cumulative_average'];
            $snapshot['summary']['term_result_mode'] = 'cumulative';

            return $snapshot;
        });
    }
}
