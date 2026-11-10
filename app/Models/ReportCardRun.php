<?php

namespace App\Models;

use Database\Factories\ReportCardRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCardRun extends Model
{
    /** @use HasFactory<ReportCardRunFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id', 'report_card_template_id', 'academic_session_id', 'term_id', 'class_arm_id', 'status',
        'generated_by', 'generated_at', 'approved_by', 'approved_at', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime', 'approved_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportCardTemplate::class, 'report_card_template_id');
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

    public function cards(): HasMany
    {
        return $this->hasMany(StudentReportCard::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ReportCardEvent::class);
    }
}
