<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolOnboardingIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $school = $request->user()?->currentSchool;

        if ($school === null) {
            return redirect()->route('school.onboarding.create');
        }

        if ($school->status === 'pending') {
            return redirect()->route('school.onboarding.pending');
        }

        abort_unless($school->status === 'approved', 403);

        return $next($request);
    }
}
