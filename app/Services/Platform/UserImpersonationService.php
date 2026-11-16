<?php

namespace App\Services\Platform;

use App\Models\ImpersonationAudit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserImpersonationService
{
    public const SESSION_AUDIT_ID = 'support_impersonation.audit_id';

    public const SESSION_IMPERSONATOR_ID = 'support_impersonation.impersonator_id';

    public function start(Request $request, User $target): ImpersonationAudit
    {
        $impersonator = $request->user();
        $school = $target->currentSchool;

        abort_unless($impersonator?->global_role === 'platform_admin', 403);
        abort_if($request->session()->has(self::SESSION_IMPERSONATOR_ID), 409, 'End the current support session before starting another one.');
        abort_if($target->global_role === 'platform_admin' || $target->is($impersonator), 422, 'Platform administrators cannot be impersonated.');
        abort_unless($school?->status === 'approved', 422, 'This user does not belong to an approved current school.');
        abort_unless(
            $target->schoolUsers()->whereBelongsTo($school)->where('status', 'active')->exists(),
            422,
            'This user does not have an active membership in their current school.',
        );

        $audit = ImpersonationAudit::query()->create([
            'impersonator_user_id' => $impersonator->id,
            'target_user_id' => $target->id,
            'school_id' => $school->id,
            'impersonator_name' => $impersonator->name,
            'impersonator_email' => $impersonator->email,
            'target_name' => $target->name,
            'target_email' => $target->email,
            'school_name' => $school->name,
            'status' => 'active',
            'started_at' => now(),
            'start_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::guard('web')->login($target);
        $request->session()->regenerate();
        $request->session()->put([
            self::SESSION_AUDIT_ID => $audit->id,
            self::SESSION_IMPERSONATOR_ID => $impersonator->id,
        ]);

        return $audit;
    }

    public function stop(Request $request): User
    {
        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);
        abort_unless($impersonatorId, 403, 'No active support session was found.');

        $impersonator = User::query()
            ->whereKey($impersonatorId)
            ->where('global_role', 'platform_admin')
            ->firstOrFail();

        $this->closeAudit($request, 'ended');
        Auth::guard('web')->login($impersonator);
        $request->session()->regenerate();
        $request->session()->forget([self::SESSION_AUDIT_ID, self::SESSION_IMPERSONATOR_ID]);

        return $impersonator;
    }

    public function closeForLogout(Request $request): void
    {
        if (! $request->session()->has(self::SESSION_IMPERSONATOR_ID)) {
            return;
        }

        $this->closeAudit($request, 'logged_out');
    }

    private function closeAudit(Request $request, string $status): void
    {
        ImpersonationAudit::query()
            ->whereKey($request->session()->get(self::SESSION_AUDIT_ID))
            ->where('status', 'active')
            ->update([
                'status' => $status,
                'ended_at' => now(),
                'end_ip' => $request->ip(),
            ]);
    }
}
