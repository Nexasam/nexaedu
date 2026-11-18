<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolRole;
use App\Models\SchoolUser;
use App\Models\StaffProfile;
use App\Models\User;
use App\Notifications\StaffPortalInvite;
use App\Services\Schools\CsvFileReader;
use App\Services\Schools\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StaffSetupController extends Controller
{
    /**
     * @var array<int, string>
     */
    private array $roles = ['teacher', 'admin', 'cleaner', 'gateman', 'bursar', 'nurse', 'librarian'];

    public function __construct(
        private PermissionCatalog $permissionCatalog,
        private CsvFileReader $csvFileReader,
    ) {}

    public function index(Request $request): Response
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $this->ensureDefaultRoles($school);

        return Inertia::render('SchoolAdmin/StaffSetup/Index', [
            'roles' => $school->roles()
                ->with('permissions')
                ->orderBy('name')
                ->get(),
            'permissionModules' => $this->permissionCatalog->modules(),
            'accessLevels' => $this->permissionCatalog->accessLevels(),
            'staff' => StaffProfile::query()
                ->whereBelongsTo($school)
                ->with([
                    'teacherProfile',
                    'user.schoolUsers' => fn ($query) => $query
                        ->whereBelongsTo($school)
                        ->with(['schoolRole', 'permissionOverrides']),
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $this->ensureDefaultRoles($school);

        $this->createStaff($school, $this->validateStaff($request, $school));

        return back()->with('status', 'Staff member added.');
    }

    public function import(Request $request): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        $this->ensureDefaultRoles($school);

        $request->validate([
            'staff_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $rows = $this->csvFileReader->read($request->file('staff_file')->getRealPath(), 'staff_file')['records'];
        $created = 0;

        DB::transaction(function () use ($rows, $school, &$created): void {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $attributes = collect(array_keys($this->staffRules($school)))
                    ->mapWithKeys(fn (string $field): array => [$field => str($field)->replace('_', ' ')->append(" on row {$rowNumber}")->toString()])
                    ->all();
                $validator = Validator::make($row, $this->staffRules($school), [], $attributes);

                $this->createStaff($school, $validator->validate());
                $created++;
            }
        });

        return back()->with('status', "{$created} staff records imported.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStaff(Request $request, School $school, ?StaffProfile $staffProfile = null): array
    {
        return $request->validate($this->staffRules($school, $staffProfile));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function staffRules(School $school, ?StaffProfile $staffProfile = null): array
    {
        $userId = $staffProfile?->user_id;

        return [
            'staff_no' => ['nullable', 'string', 'max:50', Rule::unique('staff_profiles')->where('school_id', $school->id)->ignore($staffProfile?->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'email' => [Rule::requiredIf($staffProfile?->user_id !== null), 'nullable', 'email', 'max:255', Rule::unique('staff_profiles')->where('school_id', $school->id)->ignore($staffProfile?->id), Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:40'],
            'gender' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::exists('school_roles', 'slug')->where('school_id', $school->id)],
            'qualification' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'employment_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'send_invite' => ['sometimes', 'boolean'],
        ];
    }

    public function update(Request $request, StaffProfile $staffProfile): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);
        abort_unless($staffProfile->school_id === $school->id, 404);
        $this->ensureDefaultRoles($school);
        $validated = $this->validateStaff($request, $school, $staffProfile);

        DB::transaction(function () use ($school, $staffProfile, $validated): void {
            $schoolRole = $school->roles()->where('slug', $validated['role'])->firstOrFail();
            unset($validated['send_invite']);
            $staffProfile->update($validated);

            if ($staffProfile->user) {
                $staffProfile->user->update([
                    'name' => $staffProfile->first_name.' '.$staffProfile->last_name,
                    'email' => $staffProfile->email,
                ]);
                $school->users()->whereBelongsTo($staffProfile->user)->update([
                    'role' => $schoolRole->slug,
                    'school_role_id' => $schoolRole->id,
                ]);
            }

            if ($staffProfile->role === 'teacher') {
                $teacher = $school->teacherProfiles()->firstOrCreate(
                    ['staff_profile_id' => $staffProfile->id],
                    ['user_id' => $staffProfile->user_id],
                );
                $teacher->update([
                    'user_id' => $staffProfile->user_id,
                    'staff_no' => $staffProfile->staff_no,
                    'phone' => $staffProfile->phone,
                    'gender' => $staffProfile->gender,
                    'employment_date' => $staffProfile->employment_date,
                ]);
            }
        });

        return back()->with('status', 'Staff member updated.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function createStaff(School $school, array $validated): void
    {
        DB::transaction(function () use ($school, $validated): void {
            $user = null;
            $schoolRole = $school->roles()->where('slug', $validated['role'])->firstOrFail();
            $schoolUser = null;

            if (! empty($validated['email'])) {
                $user = User::query()->create([
                    'name' => $validated['first_name'].' '.$validated['last_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make(Str::password(32)),
                    'current_school_id' => $school->id,
                ]);

                $schoolUser = $school->users()->create([
                    'user_id' => $user->id,
                    'role' => $schoolRole->slug,
                    'school_role_id' => $schoolRole->id,
                    'status' => ! empty($validated['send_invite']) ? 'invited' : 'pending_invite',
                    'invite_token' => Str::random(64),
                    'invited_at' => ! empty($validated['send_invite']) ? now() : null,
                    'invite_expires_at' => now()->addDays(14),
                ]);
            }

            unset($validated['send_invite']);

            $staff = $school->staffProfiles()->create([
                ...$validated,
                'user_id' => $user?->id,
                'status' => 'active',
            ]);

            if ($staff->role === 'teacher') {
                $school->teacherProfiles()->create([
                    'staff_profile_id' => $staff->id,
                    'user_id' => $user?->id,
                    'staff_no' => $staff->staff_no,
                    'phone' => $staff->phone,
                    'gender' => $staff->gender,
                    'employment_date' => $staff->employment_date,
                ]);
            }

            if ($user !== null && $schoolUser !== null && $schoolUser->status === 'invited') {
                $user->notify(new StaffPortalInvite($school, $schoolUser));
            }
        });
    }

    public function invite(Request $request, StaffProfile $staffProfile): RedirectResponse
    {
        $school = $this->authorizeSchoolSuperAdmin($request);

        abort_unless($staffProfile->school_id === $school->id, 404);
        abort_if($staffProfile->user === null, 422, 'This staff member does not have an email account.');

        $schoolUser = SchoolUser::query()
            ->whereBelongsTo($school)
            ->whereBelongsTo($staffProfile->user)
            ->firstOrFail();

        $schoolUser->forceFill([
            'status' => 'invited',
            'invite_token' => Str::random(64),
            'invited_at' => now(),
            'invite_expires_at' => now()->addDays(14),
        ])->save();

        $staffProfile->user->notify(new StaffPortalInvite($school, $schoolUser));

        return back()->with('status', 'Staff portal invite sent.');
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

    private function authorizeSchoolSuperAdmin(Request $request): School
    {
        $school = $request->user()->currentSchool;

        abort_if($school === null, 403);
        abort_unless($request->user()->hasSchoolRole($school, 'school_super_admin'), 403);

        return $school;
    }
}
