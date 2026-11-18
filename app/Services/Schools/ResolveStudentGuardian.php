<?php

namespace App\Services\Schools;

use App\Models\ParentProfile;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResolveStudentGuardian
{
    /** @param array<string, mixed> $data */
    public function handle(School $school, StudentProfile $student, array $data): ?ParentProfile
    {
        if (empty($data['parent_first_name'])) {
            return null;
        }

        $email = filled($data['parent_email'] ?? null)
            ? Str::lower(trim($data['parent_email']))
            : null;
        $parent = $email
            ? $school->parentProfiles()->whereRaw('LOWER(email) = ?', [$email])->first()
            : null;

        if ($parent === null) {
            $parent = $this->createParent($school, $data, $email);
        } else {
            $parent->fill([
                'first_name' => $parent->first_name ?: $data['parent_first_name'],
                'last_name' => $parent->last_name ?: $data['parent_last_name'],
                'phone' => $parent->phone ?: ($data['parent_phone'] ?? null),
            ])->save();
        }

        $parent->students()->syncWithoutDetaching([
            $student->id => [
                'school_id' => $school->id,
                'relationship' => $data['parent_relationship'],
            ],
        ]);

        return $parent;
    }

    /** @param array<string, mixed> $data */
    private function createParent(School $school, array $data, ?string $email): ParentProfile
    {
        $user = null;

        if ($email !== null) {
            if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages([
                    'parent_email' => 'This email belongs to another account and is not registered as a parent in this school.',
                ]);
            }

            $user = User::query()->create([
                'name' => $data['parent_first_name'].' '.$data['parent_last_name'],
                'email' => $email,
                'password' => Hash::make(Str::password(32)),
                'current_school_id' => $school->id,
            ]);

            $school->users()->create([
                'user_id' => $user->id,
                'role' => 'parent',
                'status' => 'active',
            ]);
        }

        return $school->parentProfiles()->create([
            'user_id' => $user?->id,
            'first_name' => $data['parent_first_name'],
            'last_name' => $data['parent_last_name'],
            'email' => $email,
            'phone' => $data['parent_phone'] ?? null,
        ]);
    }
}
