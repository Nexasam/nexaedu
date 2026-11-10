<?php

namespace App\Models;

use Database\Factories\ReportCardTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCardTemplate extends Model
{
    /** @use HasFactory<ReportCardTemplateFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'class_group_id', 'grading_scheme_id', 'name', 'term_result_mode', 'term_weights', 'layout', 'is_active'];

    protected function casts(): array
    {
        return ['term_weights' => 'array', 'layout' => 'array', 'is_active' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function gradingScheme(): BelongsTo
    {
        return $this->belongsTo(GradingScheme::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReportCardRun::class);
    }
}
