<?php

namespace App\Http\Middleware;

use App\Services\Schools\SchoolPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolPermission
{
    public function __construct(private SchoolPermissionService $schoolPermissionService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module, string $accessLevel = 'readonly'): Response
    {
        $school = $request->user()?->currentSchool;

        abort_if($school === null, 403);
        abort_unless($this->schoolPermissionService->can($request->user(), $school, $module, $accessLevel), 403);

        return $next($request);
    }
}
