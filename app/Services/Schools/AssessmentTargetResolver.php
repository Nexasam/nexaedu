<?php

namespace App\Services\Schools;

use App\Models\School;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class AssessmentTargetResolver
{
    /**
     * @param  array{target_scope: string, class_term_subject_ids?: array<int, mixed>, class_arm_id?: mixed, class_group_id?: mixed}  $data
     * @return Collection<int, int>
     */
    public function resolve(User $user, School $school, array $data): Collection
    {
        $targetIds = match ($data['target_scope']) {
            'all' => $school->classTermSubjects()->pluck('id'),
            'arm' => $school->classTermSubjects()
                ->where('class_arm_id', $data['class_arm_id'] ?? null)
                ->pluck('id'),
            'group' => $school->classTermSubjects()
                ->whereHas('classArm.schoolClass', fn ($query) => $query->where('class_group_id', $data['class_group_id'] ?? null))
                ->pluck('id'),
            default => collect($data['class_term_subject_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values(),
        };

        if ($this->canManageAllAssessmentFlows($user, $school)) {
            return $targetIds;
        }

        return $targetIds->intersect($this->assignedClassTermSubjectIds($user, $school))->values();
    }

    private function canManageAllAssessmentFlows(User $user, School $school): bool
    {
        return $user->schoolUsers()
            ->whereBelongsTo($school)
            ->whereIn('role', ['school_super_admin', 'admin'])
            ->where('status', 'active')
            ->exists();
    }

    /**
     * @return Collection<int, int>
     */
    private function assignedClassTermSubjectIds(User $user, School $school): Collection
    {
        return TeacherProfile::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($user)
            ->with('classTermSubjectAssignments')
            ->first()
            ?->classTermSubjectAssignments
            ->pluck('class_term_subject_id') ?? collect();
    }
}
