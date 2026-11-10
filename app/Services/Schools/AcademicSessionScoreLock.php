<?php

namespace App\Services\Schools;

use App\Models\AcademicSession;

class AcademicSessionScoreLock
{
    public function isLocked(AcademicSession $academicSession): bool
    {
        if ($academicSession->score_unlock_at?->isPast()) {
            return false;
        }

        if ($academicSession->score_lock_at?->isPast()) {
            return true;
        }

        return (bool) ($academicSession->score_entries_locked ?? false);
    }
}
