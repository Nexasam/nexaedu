<?php

namespace App\Models;

use Database\Factories\AssessmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentItem extends Model
{
    /** @use HasFactory<AssessmentItemFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'assessment_group_id', 'name', 'max_score', 'raw_max_score', 'weight', 'sort_order'];

    protected function casts(): array
    {
        return [
            'raw_max_score' => 'decimal:2',
            'weight' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assessmentGroup(): BelongsTo
    {
        return $this->belongsTo(AssessmentGroup::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }

    public function rawMaximumScore(): float
    {
        return (float) ($this->raw_max_score ?? $this->max_score);
    }

    public function reportWeight(): float
    {
        return (float) ($this->weight ?? $this->max_score);
    }
}
