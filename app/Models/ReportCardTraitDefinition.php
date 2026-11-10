<?php

namespace App\Models;

use Database\Factories\ReportCardTraitDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCardTraitDefinition extends Model
{
    /** @use HasFactory<ReportCardTraitDefinitionFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'class_group_id', 'name', 'rating_scale', 'rating_options', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['rating_options' => 'array', 'is_active' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(StudentReportCardRating::class);
    }
}
