<?php

namespace App\Models;

use Database\Factories\TeacherClassTermSubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherClassTermSubject extends Model
{
    /** @use HasFactory<TeacherClassTermSubjectFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'teacher_profile_id', 'class_term_subject_id'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    public function classTermSubject(): BelongsTo
    {
        return $this->belongsTo(ClassTermSubject::class);
    }
}
