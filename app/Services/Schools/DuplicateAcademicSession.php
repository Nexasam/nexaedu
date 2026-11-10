<?php

namespace App\Services\Schools;

use App\Models\AcademicSession;
use App\Models\ClassTermSubject;
use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\TeacherClassArm;
use App\Models\TeacherClassTermSubject;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DuplicateAcademicSession
{
    /** @param array{name: string, starts_on?: string|null, ends_on?: string|null, is_current?: bool, copy_enrollments?: bool, copy_teacher_assignments?: bool, copy_assessment_flows?: bool} $data */
    public function handle(School $school, AcademicSession $sourceSession, array $data): AcademicSession
    {
        return DB::transaction(function () use ($school, $sourceSession, $data): AcademicSession {
            if ($data['is_current'] ?? false) {
                $school->academicSessions()->update(['is_current' => false]);
                $school->terms()->update(['is_current' => false]);
            }

            $newSession = $school->academicSessions()->create([
                'name' => $data['name'],
                'starts_on' => $data['starts_on'] ?? null,
                'ends_on' => $data['ends_on'] ?? null,
                'is_current' => $data['is_current'] ?? false,
            ]);

            $sourceSession->load([
                'terms.classTermSubjects.assessmentGroups.items',
            ]);

            $termMap = [];
            $subjectMap = [];

            foreach ($sourceSession->terms as $sourceTerm) {
                $newTerm = $school->terms()->create([
                    'academic_session_id' => $newSession->id,
                    'name' => $sourceTerm->name,
                    'starts_on' => $this->shiftDate($sourceTerm->starts_on, $sourceSession->starts_on, $newSession->starts_on),
                    'ends_on' => $this->shiftDate($sourceTerm->ends_on, $sourceSession->starts_on, $newSession->starts_on),
                    'is_current' => false,
                ]);
                $termMap[$sourceTerm->id] = $newTerm->id;

                foreach ($sourceTerm->classTermSubjects as $sourceSubject) {
                    $newSubject = $school->classTermSubjects()->create([
                        'academic_session_id' => $newSession->id,
                        'term_id' => $newTerm->id,
                        'class_arm_id' => $sourceSubject->class_arm_id,
                        'school_subject_id' => $sourceSubject->school_subject_id,
                    ]);
                    $subjectMap[$sourceSubject->id] = $newSubject->id;

                    if ($data['copy_assessment_flows'] ?? true) {
                        $this->copyLatestAssessmentFlow($school, $sourceSubject, $newSubject);
                    }
                }
            }

            if ($data['copy_enrollments'] ?? true) {
                $this->copyEnrollments($school, $sourceSession, $newSession, $termMap);
            }

            if ($data['copy_teacher_assignments'] ?? true) {
                $this->copyTeacherAssignments($school, $sourceSession, $newSession, $termMap, $subjectMap);
            }

            return $newSession;
        });
    }

    private function copyLatestAssessmentFlow(School $school, ClassTermSubject $sourceSubject, ClassTermSubject $newSubject): void
    {
        $sourceGroup = $sourceSubject->assessmentGroups->sortByDesc('id')->first();

        if (! $sourceGroup) {
            return;
        }

        $newGroup = $school->assessmentGroups()->create([
            'class_term_subject_id' => $newSubject->id,
            'name' => $sourceGroup->name,
            'total_score' => $sourceGroup->total_score,
        ]);

        foreach ($sourceGroup->items->sortBy('sort_order') as $item) {
            $newGroup->items()->create([
                'school_id' => $school->id,
                'name' => $item->name,
                'max_score' => $item->max_score,
                'sort_order' => $item->sort_order,
            ]);
        }
    }

    /** @param array<int, int> $termMap */
    private function copyEnrollments(School $school, AcademicSession $sourceSession, AcademicSession $newSession, array $termMap): void
    {
        StudentEnrollment::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($sourceSession)
            ->each(function (StudentEnrollment $enrollment) use ($school, $newSession, $termMap): void {
                $school->studentEnrollments()->create([
                    'student_profile_id' => $enrollment->student_profile_id,
                    'academic_session_id' => $newSession->id,
                    'term_id' => $enrollment->term_id ? ($termMap[$enrollment->term_id] ?? null) : null,
                    'class_arm_id' => $enrollment->class_arm_id,
                    'enrolled_on' => $newSession->starts_on,
                    'status' => $enrollment->status,
                ]);
            });
    }

    /** @param array<int, int> $termMap @param array<int, int> $subjectMap */
    private function copyTeacherAssignments(School $school, AcademicSession $sourceSession, AcademicSession $newSession, array $termMap, array $subjectMap): void
    {
        TeacherClassArm::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($sourceSession, 'academicSession')
            ->each(function (TeacherClassArm $assignment) use ($school, $newSession, $termMap): void {
                $school->teacherClassArms()->create([
                    'teacher_profile_id' => $assignment->teacher_profile_id,
                    'academic_session_id' => $newSession->id,
                    'term_id' => $assignment->term_id ? ($termMap[$assignment->term_id] ?? null) : null,
                    'class_arm_id' => $assignment->class_arm_id,
                ]);
            });

        TeacherClassTermSubject::query()
            ->whereBelongsTo($school)
            ->whereIn('class_term_subject_id', array_keys($subjectMap))
            ->each(function (TeacherClassTermSubject $assignment) use ($school, $subjectMap): void {
                $school->teacherClassTermSubjects()->create([
                    'teacher_profile_id' => $assignment->teacher_profile_id,
                    'class_term_subject_id' => $subjectMap[$assignment->class_term_subject_id],
                ]);
            });
    }

    private function shiftDate(?CarbonInterface $date, ?CarbonInterface $sourceStart, ?CarbonInterface $newStart): ?CarbonInterface
    {
        if (! $date || ! $sourceStart || ! $newStart) {
            return null;
        }

        return $newStart->copy()->addDays($sourceStart->diffInDays($date, false));
    }
}
