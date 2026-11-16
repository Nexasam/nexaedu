<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ImpersonationAudit;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class PlatformUserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->where('global_role', '!=', 'platform_admin')
            ->whereHas('schoolUsers')
            ->with([
                'currentSchool:id,name,code,status,owner_user_id',
                'schoolUsers:id,school_id,user_id,school_role_id,role,status',
                'schoolUsers.schoolRole:id,name',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                $school = $user->currentSchool;
                $membership = $user->schoolUsers->firstWhere('school_id', $user->current_school_id)
                    ?? $user->schoolUsers->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'school' => $school ? [
                        'id' => $school->id,
                        'name' => $school->name,
                        'code' => $school->code,
                        'status' => $school->status,
                    ] : null,
                    'role' => $membership?->schoolRole?->name ?? str($membership?->role ?? 'No role')->replace('_', ' ')->title()->toString(),
                    'membership_status' => $membership?->status ?? 'unassigned',
                    'is_school_owner' => $school?->owner_user_id === $user->id,
                    'can_impersonate' => $school?->status === 'approved'
                        && $membership?->school_id === $school->id
                        && $membership?->status === 'active',
                ];
            });

        $recentSessions = ImpersonationAudit::query()
            ->latest('started_at')
            ->limit(25)
            ->get()
            ->map(fn (ImpersonationAudit $audit): array => [
                'id' => $audit->id,
                'impersonator_name' => $audit->impersonator_name,
                'target_name' => $audit->target_name,
                'school_name' => $audit->school_name,
                'status' => $audit->status,
                'started_at' => $audit->started_at?->toDayDateTimeString(),
                'ended_at' => $audit->ended_at?->toDayDateTimeString(),
            ]);

        return Inertia::render('Platform/Users/Index', [
            'users' => $users,
            'recentSessions' => $recentSessions,
        ]);
    }
}
