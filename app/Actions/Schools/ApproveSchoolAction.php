<?php

namespace App\Actions\Schools;

use App\Models\School;
use App\Models\User;

class ApproveSchoolAction
{
    public function handle(School $school, User $approver): School
    {
        $school->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ])->save();

        return $school;
    }
}
