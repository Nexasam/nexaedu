<?php

namespace App\Actions\Schools;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegisterSchoolAction
{
    public function __construct(private GenerateSchoolCodeAction $generateSchoolCode) {}

    /**
     * @param  array{name: string, slug: string, address?: string|null, phone?: string|null, email?: string|null}  $data
     */
    public function handle(User $owner, array $data): School
    {
        return DB::transaction(function () use ($owner, $data): School {
            $school = School::create([
                'owner_user_id' => $owner->id,
                'name' => $data['name'],
                'code' => $this->generateSchoolCode->handle(),
                'slug' => Str::slug($data['slug']),
                'status' => 'pending',
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
            ]);

            $school->domains()->create([
                'domain' => $school->slug.'.'.parse_url(config('app.url'), PHP_URL_HOST),
                'type' => 'subdomain',
                'is_primary' => true,
            ]);

            $school->users()->create([
                'user_id' => $owner->id,
                'role' => 'school_super_admin',
                'status' => 'active',
            ]);

            $owner->forceFill(['current_school_id' => $school->id])->save();

            return $school;
        });
    }
}
