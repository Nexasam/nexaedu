<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'global_role', 'current_school_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function currentSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'current_school_id');
    }

    public function ownedSchools(): HasMany
    {
        return $this->hasMany(School::class, 'owner_user_id');
    }

    public function schoolUsers(): HasMany
    {
        return $this->hasMany(SchoolUser::class);
    }

    public function teacherProfiles(): HasMany
    {
        return $this->hasMany(TeacherProfile::class);
    }

    public function hasSchoolRole(School $school, string ...$roles): bool
    {
        return $this->schoolUsers()
            ->whereBelongsTo($school)
            ->where('status', 'active')
            ->whereIn('role', $roles)
            ->exists();
    }
}
