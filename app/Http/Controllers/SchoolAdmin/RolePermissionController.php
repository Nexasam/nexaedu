<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolRole;
use App\Services\Schools\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    public function __construct(private PermissionCatalog $permissionCatalog) {}

    /**
     * @var array<int, string>
     */
    private array $roles = ['teacher', 'admin', 'cleaner', 'gateman', 'bursar', 'nurse', 'librarian'];

    public function index(Request $request): Response
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $this->ensureDefaultRoles($school);

        return Inertia::render('SchoolAdmin/RolesPermissions/Index', [
            'roles' => $school->roles()
                ->with('permissions')
                ->orderBy('name')
                ->get(),
            'permissionModules' => $this->permissionCatalog->modules(),
            'accessLevels' => $this->permissionCatalog->accessLevels(),
        ]);
    }

    public function update(Request $request, SchoolRole $schoolRole): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        abort_unless($schoolRole->school_id === $school->id, 404);

        $moduleKeys = collect($this->permissionCatalog->modules())->pluck('key')->all();
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*.module' => ['required', Rule::in($moduleKeys)],
            'permissions.*.access_level' => ['required', Rule::in($this->permissionCatalog->accessLevels())],
        ]);

        foreach ($validated['permissions'] as $permission) {
            $schoolRole->permissions()->updateOrCreate(
                ['module' => $permission['module']],
                ['access_level' => $permission['access_level']],
            );
        }

        return back()->with('status', 'Role permissions updated.');
    }

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }

    private function ensureDefaultRoles(School $school): void
    {
        foreach ($this->roles as $role) {
            $schoolRole = SchoolRole::query()->firstOrCreate(
                ['school_id' => $school->id, 'slug' => $role],
                [
                    'name' => Str::headline($role),
                    'description' => Str::headline($role).' access profile.',
                    'is_system' => true,
                ],
            );

            foreach ($this->permissionCatalog->defaultRoleAccess($role) as $module => $accessLevel) {
                $schoolRole->permissions()->firstOrCreate(
                    ['module' => $module],
                    ['access_level' => $accessLevel],
                );
            }
        }
    }
}
