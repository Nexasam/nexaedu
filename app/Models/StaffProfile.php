<?php

namespace App\Models;

use Database\Factories\StaffProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'staff_no',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'gender',
        'qualification',
        'specialization',
        'department',
        'job_title',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'role',
        'employment_date',
        'employment_type',
        'status',
        'signature_path',
        'signature_uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'employment_date' => 'date',
            'signature_uploaded_at' => 'datetime',
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

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }
}
