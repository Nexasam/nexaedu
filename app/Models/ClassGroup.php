<?php

namespace App\Models;

use Database\Factories\ClassGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClassGroup extends Model
{
    /** @use HasFactory<ClassGroupFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'name', 'sort_order'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function gradingSchemes(): HasMany
    {
        return $this->hasMany(GradingScheme::class);
    }

    public function reportCardTemplates(): HasMany
    {
        return $this->hasMany(ReportCardTemplate::class);
    }

    public function reportCardTemplateSelection(): HasOne
    {
        return $this->hasOne(SchoolReportCardTemplateSelection::class);
    }

    public function reportCardTraitDefinitions(): HasMany
    {
        return $this->hasMany(ReportCardTraitDefinition::class);
    }
}
