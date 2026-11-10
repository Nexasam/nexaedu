<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolUser;
use App\Services\Schools\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserPermissionOverrideController extends Controller
{
    public function __construct(private PermissionCatalog $permissionCatalog) {}

    public function update(Request $request, SchoolUser $schoolUser): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        abort_unless($schoolUser->school_id === $school->id, 404);

        $moduleKeys = collect($this->permissionCatalog->modules())->pluck('key')->all();
        $validated = $request->validate([
            'overrides' => ['required', 'array'],
            'overrides.*.module' => ['required', Rule::in($moduleKeys)],
            'overrides.*.access_level' => ['required', Rule::in($this->permissionCatalog->accessLevels())],
            'overrides.*.enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['overrides'] as $override) {
            if (! $override['enabled']) {
                $schoolUser->permissionOverrides()->where('module', $override['module'])->delete();

                continue;
            }

            $schoolUser->permissionOverrides()->updateOrCreate(
                ['module' => $override['module']],
                [
                    'access_level' => $override['access_level'],
                    'reason' => $validated['reason'] ?? null,
                ],
            );
        }

        return back()->with('status', 'Staff permission overrides updated.');
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
