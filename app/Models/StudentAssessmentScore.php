<?php

namespace App\Models;

use Database\Factories\StudentAssessmentScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentScore extends Model
{
    /** @use HasFactory<StudentAssessmentScoreFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_profile_id',
        'class_term_subject_id',
        'assessment_group_id',
        'assessment_item_id',
        'recorded_by',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
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

    public function classTermSubject(): BelongsTo
    {
        return $this->belongsTo(ClassTermSubject::class);
    }

    public function assessmentGroup(): BelongsTo
    {
        return $this->belongsTo(AssessmentGroup::class);
    }

    public function assessmentItem(): BelongsTo
    {
        return $this->belongsTo(AssessmentItem::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
