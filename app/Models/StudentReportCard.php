<?php

namespace App\Models;

use Database\Factories\StudentReportCardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentReportCard extends Model
{
    /** @use HasFactory<StudentReportCardFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id', 'report_card_run_id', 'student_profile_id', 'school_days_opened', 'days_present', 'days_absent',
        'times_late', 'class_teacher_comment', 'principal_comment', 'promotion_decision', 'next_term_starts_on',
        'calculated_snapshot', 'signature_snapshot', 'status', 'approved_by', 'approved_at', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'next_term_starts_on' => 'date',
            'calculated_snapshot' => 'array',
            'signature_snapshot' => 'array',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReportCardRun::class, 'report_card_run_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(StudentReportCardRating::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReportCardEvent::class);
    }
}
