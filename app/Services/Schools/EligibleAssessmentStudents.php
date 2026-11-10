<?php

namespace App\Services\Schools;

use App\Models\AssessmentGroup;
use App\Models\School;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class EligibleAssessmentStudents
{
    /**
     * @return Collection<int, StudentProfile>
     */
    public function forGroup(School $school, AssessmentGroup $group): Collection
    {
        $classTermSubject = $group->classTermSubject;

        return StudentProfile::query()
            ->whereBelongsTo($school)
            ->where('status', 'active')
            ->whereHas('enrollments', fn ($query) => $query
                ->where('class_arm_id', $classTermSubject->class_arm_id)
                ->where('academic_session_id', $classTermSubject->academic_session_id)
                ->where('term_id', $classTermSubject->term_id)
                ->where('status', 'active'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
