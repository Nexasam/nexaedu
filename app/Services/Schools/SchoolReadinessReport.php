<?php

namespace App\Services\Schools;

use App\Models\AcademicSession;
use App\Models\AssessmentGroup;
use App\Models\ClassTermSubject;
use App\Models\School;
use App\Models\StudentAssessmentScore;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class SchoolReadinessReport
{
    /**
     * @return array{arms: array<int, array<string, mixed>>, summary: array<string, mixed>, issues: array<int, array<string, mixed>>}
     */
    public function build(School $school): array
    {
        $academicSession = AcademicSession::query()
            ->whereBelongsTo($school)
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->latest('id')
            ->first();

        $classTermSubjects = ClassTermSubject::query()
            ->whereBelongsTo($school)
            ->when($academicSession, fn ($query) => $query->whereBelongsTo($academicSession))
            ->with(['academicSession', 'term', 'classArm.schoolClass.classGroup', 'schoolSubject.subject'])
            ->get();

        $assessmentGroups = AssessmentGroup::query()
            ->whereBelongsTo($school)
            ->whereHas('classTermSubject', fn ($query) => $query->when($academicSession, fn ($sessionQuery) => $sessionQuery->whereBelongsTo($academicSession)))
            ->with(['items' => fn ($query) => $query->orderBy('sort_order')])
            ->latest()
            ->get()
            ->unique('class_term_subject_id')
            ->keyBy('class_term_subject_id');

        $students = StudentProfile::query()
            ->whereBelongsTo($school)
            ->where('status', 'active')
            ->with([
                'parents',
                'enrollments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->when($academicSession, fn ($sessionQuery) => $sessionQuery->whereBelongsTo($academicSession))
                    ->with(['academicSession', 'term', 'classArm.schoolClass.classGroup']),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $scores = StudentAssessmentScore::query()
            ->whereBelongsTo($school)
            ->get(['student_profile_id', 'assessment_item_id', 'score'])
            ->keyBy(fn (StudentAssessmentScore $score) => $score->student_profile_id.':'.$score->assessment_item_id);

        $issues = collect();
        $arms = $classTermSubjects
            ->pluck('classArm')
            ->filter()
            ->unique('id')
            ->sortBy(fn ($classArm) => [
                $classArm->schoolClass?->classGroup?->sort_order ?? 0,
                $classArm->schoolClass?->sort_order ?? 0,
                $classArm->name,
            ])
            ->values()
            ->map(function ($classArm) use ($assessmentGroups, $classTermSubjects, $issues, $scores, $students) {
                $armSubjects = $classTermSubjects
                    ->where('class_arm_id', $classArm->id)
                    ->values();
                $armStudents = $students
                    ->filter(fn (StudentProfile $student) => $student->enrollments->contains('class_arm_id', $classArm->id))
                    ->values();

                $studentRows = $armStudents->map(function (StudentProfile $student) use ($armSubjects, $assessmentGroups, $classArm, $issues, $scores) {
                    $subjectRows = $armSubjects->map(function (ClassTermSubject $subject) use ($assessmentGroups, $classArm, $issues, $scores, $student) {
                        $group = $assessmentGroups->get($subject->id);
                        $missingItems = $group?->items
                            ->filter(fn ($item) => ! $scores->has($student->id.':'.$item->id) || $scores->get($student->id.':'.$item->id)->score === null || $scores->get($student->id.':'.$item->id)->score === '')
                            ->values() ?? collect();
                        $hasAllScores = $group !== null && $group->items->isNotEmpty() && $missingItems->isEmpty();
                        $isReady = $hasAllScores || $group?->completed_at !== null;
                        $issue = $this->subjectIssue($group, $missingItems);
                        $score = $group?->items->sum(fn ($item) => (float) ($scores->get($student->id.':'.$item->id)?->score ?? 0)) ?? 0;

                        if (! $isReady) {
                            $issues->push([
                                'class_arm' => $this->classArmName($classArm),
                                'student' => $this->studentName($student),
                                'admission_no' => $student->admission_no,
                                'subject' => $this->subjectName($subject),
                                'issue' => $issue,
                            ]);
                        }

                        return [
                            'id' => $subject->id,
                            'name' => $this->subjectName($subject),
                            'assessment_flow' => $group?->name,
                            'score' => $score,
                            'total_score' => $group?->total_score,
                            'term' => $subject->term?->name,
                            'ready' => $isReady,
                            'issue' => $isReady ? null : $issue,
                        ];
                    })->values();

                    return [
                        'id' => $student->id,
                        'name' => $this->studentName($student),
                        'admission_no' => $student->admission_no,
                        'gender' => $student->gender,
                        'date_of_birth' => $student->date_of_birth?->toDateString(),
                        'status' => $student->status,
                        'guardians' => $student->parents->map(fn ($parent) => [
                            'id' => $parent->id,
                            'name' => trim($parent->first_name.' '.$parent->last_name),
                            'relationship' => $parent->pivot->relationship,
                            'email' => $parent->email,
                            'phone' => $parent->phone,
                        ])->values()->all(),
                        'enrollments' => $student->enrollments->map(fn ($enrollment) => [
                            'id' => $enrollment->id,
                            'session' => $enrollment->academicSession?->name,
                            'term' => $enrollment->term?->name,
                            'class_arm' => $this->classArmName($enrollment->classArm),
                            'status' => $enrollment->status,
                        ])->values()->all(),
                        'ready' => $subjectRows->isNotEmpty() && $subjectRows->every(fn (array $subject) => $subject['ready']),
                        'subjects' => $subjectRows->all(),
                    ];
                })->values();

                return [
                    'id' => $classArm->id,
                    'name' => $this->classArmName($classArm),
                    'ready' => $studentRows->isNotEmpty() && $studentRows->every(fn (array $student) => $student['ready']),
                    'students' => $studentRows->all(),
                ];
            })
            ->all();

        return [
            'arms' => $arms,
            'issues' => $issues->values()->all(),
            'summary' => [
                'ready' => $issues->isEmpty() && collect($arms)->sum(fn (array $arm) => count($arm['students'])) > 0,
                'issue_count' => $issues->count(),
                'class_arm_count' => count($arms),
                'student_count' => $students->count(),
                'subject_count' => $classTermSubjects->count(),
                'academic_session' => $academicSession?->name,
            ],
        ];
    }

    private function subjectIssue(?AssessmentGroup $group, Collection $missingItems): string
    {
        if ($group === null) {
            return 'No assessment flow has been created.';
        }

        if ($group->items->isEmpty()) {
            return 'Assessment flow has no score items.';
        }

        if ($missingItems->isNotEmpty()) {
            return 'Missing '.$missingItems->pluck('name')->join(', ');
        }

        return 'Score sheet has not been marked as completed.';
    }

    private function subjectName(ClassTermSubject $subject): string
    {
        return $subject->schoolSubject?->custom_name
            ?: $subject->schoolSubject?->subject?->name
            ?: 'Subject';
    }

    private function studentName(StudentProfile $student): string
    {
        return trim($student->first_name.' '.$student->last_name);
    }

    private function classArmName($classArm): string
    {
        return collect([
            $classArm->schoolClass?->classGroup?->name,
            $classArm->schoolClass?->name,
            $classArm->name,
        ])->filter()->join(' • ');
    }
}
