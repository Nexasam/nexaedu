<?php

namespace App\Models;

use Database\Factories\ClassTermSubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassTermSubject extends Model
{
    /** @use HasFactory<ClassTermSubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_session_id',
        'term_id',
        'class_arm_id',
        'school_subject_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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

    public function schoolSubject(): BelongsTo
    {
        return $this->belongsTo(SchoolSubject::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherClassTermSubject::class);
    }

    public function assessmentGroups(): HasMany
    {
        return $this->hasMany(AssessmentGroup::class);
    }
}
