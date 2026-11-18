<?php

namespace App\Services\Schools;

use App\Models\School;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RolloverStudents
{
    /** @param array<string, mixed> $data */
    public function handle(School $school, array $data): int
    {
        $sourceSession = $school->academicSessions()->findOrFail($data['source_session_id']);
        $targetSession = $school->academicSessions()->findOrFail($data['target_session_id']);
        if (! empty($data['target_term_id']) && ! $targetSession->terms()->whereKey($data['target_term_id'])->exists()) {
            throw ValidationException::withMessages(['target_term_id' => 'The selected term does not belong to the destination session.']);
        }
        $studentIds = collect($data['students'])->pluck('student_profile_id')->unique();
        $eligibleStudentIds = StudentEnrollment::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($sourceSession)
            ->whereIn('student_profile_id', $studentIds)
            ->pluck('student_profile_id')
            ->unique();

        if ($eligibleStudentIds->count() !== $studentIds->count()) {
            throw ValidationException::withMessages(['students' => 'One or more students are not enrolled in the selected source session.']);
        }

        return DB::transaction(function () use ($school, $targetSession, $data): int {
            foreach ($data['students'] as $row) {
                $student = $school->studentProfiles()->findOrFail($row['student_profile_id']);
                $decision = $row['decision'];

                if (in_array($decision, ['graduated', 'withdrawn'], true)) {
                    $student->update(['status' => $decision]);

                    continue;
                }

                $student->update(['status' => 'active']);
                StudentEnrollment::query()->updateOrCreate(
                    [
                        'student_profile_id' => $student->id,
                        'academic_session_id' => $targetSession->id,
                        'term_id' => $data['target_term_id'] ?? null,
                    ],
                    [
                        'school_id' => $school->id,
                        'class_arm_id' => $row['target_class_arm_id'],
                        'enrolled_on' => $targetSession->starts_on ?? now()->toDateString(),
                        'status' => 'active',
                    ],
                );
            }

            return count($data['students']);
        });
    }
}
