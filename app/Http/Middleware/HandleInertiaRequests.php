<?php

namespace App\Http\Middleware;

use App\Services\Schools\SchoolPermissionService;
use App\Services\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $currentSchool = app(CurrentSchool::class)->get();
        $school = $currentSchool ?? $request->user()?->currentSchool;
        $permissions = ($school && $request->user())
            ? app(SchoolPermissionService::class)->permissionsFor($request->user(), $school)
            : [];
        $sharedSchool = $school ? [
            'id' => $school->id,
            'name' => $school->name,
            'code' => $school->code,
            'status' => $school->status,
            'logo_path' => $school->logo_path,
            'logo_url' => $school->logoUrl(),
            'primary_color' => $school->primary_color,
            'secondary_color' => $school->secondary_color,
            'brand_font' => $school->brand_font,
        ] : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'permissions' => $permissions,
            ],
            'flash' => [
                'status' => $request->session()->get('status'),
            ],
            'currentSchool' => $sharedSchool,
            'school' => $sharedSchool,
        ];
    }
}
