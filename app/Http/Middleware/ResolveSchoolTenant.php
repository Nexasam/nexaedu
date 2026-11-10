<?php

namespace App\Http\Middleware;

use App\Models\SchoolDomain;
use App\Services\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSchoolTenant
{
    public function __construct(private CurrentSchool $currentSchool) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $domain = SchoolDomain::query()
            ->with('school')
            ->where('domain', $request->getHost())
            ->first();

        if ($domain === null || $domain->school->status !== 'approved') {
            abort(404);
        }

        $this->currentSchool->set($domain->school);
        $request->attributes->set('school', $domain->school);

        return $next($request);
    }
}
