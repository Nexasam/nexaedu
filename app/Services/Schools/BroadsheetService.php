<?php

namespace App\Services\Schools;

use App\Models\ReportCardRun;
use Illuminate\Support\Collection;

class BroadsheetService
{
    public function __construct(private ReportCardSnapshotPresenter $snapshotPresenter) {}

    /** @return array{subjects: Collection<int, array<string, mixed>>, rows: Collection<int, array<string, mixed>>} */
    public function build(ReportCardRun $run): array
    {
        $cards = $this->snapshotPresenter->enrichMissingClassStatistics(
            $run->cards()->with('student')->get()
        );
        $subjects = $cards
            ->flatMap(fn ($card): array => $card->calculated_snapshot['subjects'] ?? [])
            ->unique(fn (array $subject): int => (int) ($subject['school_subject_id'] ?? $subject['class_term_subject_id']))
            ->values()
            ->map(fn (array $subject): array => [
                'id' => $subject['school_subject_id'] ?? $subject['class_term_subject_id'],
                'name' => $subject['name'],
            ]);
        $rows = $cards->map(function ($card) use ($subjects): array {
            $snapshot = $card->calculated_snapshot ?? [];
            $results = collect($snapshot['subjects'] ?? []);

            return [
                'student_id' => $card->student_profile_id,
                'student_name' => trim($card->student->first_name.' '.$card->student->last_name),
                'admission_no' => $card->student->admission_no,
                'card_status' => $card->status,
                'subjects' => $subjects->map(function (array $subject) use ($results): array {
                    $result = $results->first(fn (array $candidate): bool => (int) ($candidate['school_subject_id'] ?? $candidate['class_term_subject_id']) === (int) $subject['id']);

                    return [
                        'subject_id' => $subject['id'],
                        'percentage' => $result['percentage'] ?? null,
                        'cumulative_percentage' => $result['cumulative_percentage'] ?? null,
                        'position' => $result['position'] ?? null,
                        'grade' => $result['grade'] ?? null,
                        'complete' => $result !== null,
                    ];
                })->all(),
                'overall_percentage' => data_get($snapshot, 'summary.average'),
                'cumulative_average' => data_get($snapshot, 'summary.cumulative_average'),
                'position' => data_get($snapshot, 'summary.position'),
            ];
        })->sortBy('student_name')->values();

        return compact('subjects', 'rows');
    }
}
