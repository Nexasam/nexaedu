<?php

namespace App\Services\Schools;

use App\Models\School;
use App\Models\User;

class SchoolPermissionService
{
    public function __construct(private PermissionCatalog $permissionCatalog) {}

    public function accessLevel(User $user, School $school, string $module): string
    {
        $schoolUser = $user->schoolUsers()
            ->whereBelongsTo($school)
            ->whereIn('status', ['active', 'invited', 'pending_invite'])
            ->with(['schoolRole.permissions', 'permissionOverrides'])
            ->first();

        if ($schoolUser === null) {
            return 'none';
        }

        if ($schoolUser->role === 'school_super_admin') {
            return 'readwrite';
        }

        $roleAccess = $schoolUser->schoolRole?->permissions
            ->firstWhere('module', $module)
            ?->access_level ?? 'none';

        return $schoolUser->permissionOverrides
            ->firstWhere('module', $module)
            ?->access_level ?? $roleAccess;
    }

    public function can(User $user, School $school, string $module, string $requiredAccess = 'readonly'): bool
    {
        $accessLevel = $this->accessLevel($user, $school, $module);

        if ($requiredAccess === 'readonly') {
            return in_array($accessLevel, ['readonly', 'readwrite'], true);
        }

        return $accessLevel === 'readwrite';
    }

    /**
     * @return array<string, string>
     */
    public function permissionsFor(User $user, School $school): array
    {
        $permissions = [];

        foreach ($this->permissionCatalog->modules() as $module) {
            $permissions[$module['key']] = $this->accessLevel($user, $school, $module['key']);
        }

        return $permissions;
    }
}
