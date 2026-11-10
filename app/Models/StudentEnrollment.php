<?php

namespace App\Models;

use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    /** @use HasFactory<StudentEnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_profile_id',
        'academic_session_id',
        'term_id',
        'class_arm_id',
        'enrolled_on',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_on' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class);
    }
}
