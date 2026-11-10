<?php

namespace App\Models;

use Database\Factories\TeacherProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    /** @use HasFactory<TeacherProfileFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'staff_profile_id', 'user_id', 'staff_no', 'phone', 'gender', 'employment_date'];

    protected function casts(): array
    {
        return [
            'employment_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function classTermSubjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassTermSubject::class);
    }

    public function classArmAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassArm::class);
    }
}
