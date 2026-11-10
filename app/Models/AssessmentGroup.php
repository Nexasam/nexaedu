<?php

namespace App\Models;

use Database\Factories\AssessmentGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentGroup extends Model
{
    /** @use HasFactory<AssessmentGroupFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'class_term_subject_id', 'name', 'total_score', 'completed_at', 'completed_by'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classTermSubject(): BelongsTo
    {
        return $this->belongsTo(ClassTermSubject::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssessmentItem::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }
}
