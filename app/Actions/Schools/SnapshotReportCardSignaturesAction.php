<?php

namespace App\Actions\Schools;

use App\Models\ReportCardRun;
use App\Models\School;
use App\Models\StaffProfile;
use App\Models\TeacherClassArm;
use Illuminate\Support\Facades\Storage;

class SnapshotReportCardSignaturesAction
{
    /** @return array<string, array<string, mixed>> */
    public function handle(School $school, ReportCardRun $run): array
    {
        $classTeacher = TeacherClassArm::query()
            ->whereBelongsTo($school)
            ->where('class_arm_id', $run->class_arm_id)
            ->where('academic_session_id', $run->academic_session_id)
            ->where('is_class_teacher', true)
            ->where(fn ($query) => $query->where('term_id', $run->term_id)->orWhereNull('term_id'))
            ->orderByRaw('term_id is null')
            ->with('teacherProfile.staffProfile')
            ->first()?->teacherProfile?->staffProfile;

        return collect([
            'class_teacher' => $this->signer($classTeacher, 'Class Teacher'),
            'principal' => $this->signer($school->principalStaffProfile()->first(), 'Principal'),
        ])->filter()->all();
    }

    /** @return array<string, mixed>|null */
    private function signer(?StaffProfile $staff, string $defaultTitle): ?array
    {
        if (! $staff?->signature_path || ! Storage::disk('local')->exists($staff->signature_path)) {
            return null;
        }

        return [
            'staff_profile_id' => $staff->id,
            'name' => collect([$staff->first_name, $staff->middle_name, $staff->last_name])->filter()->join(' '),
            'title' => $staff->job_title ?: $defaultTitle,
            'path' => $staff->signature_path,
            'sha256' => hash('sha256', Storage::disk('local')->get($staff->signature_path)),
            'signed_at' => now()->toIso8601String(),
        ];
    }
}
