<?php

namespace App\Models;

use Database\Factories\PlatformReportCardTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformReportCardTemplate extends Model
{
    /** @use HasFactory<PlatformReportCardTemplateFactory> */
    use HasFactory;

    protected $fillable = ['name', 'education_level', 'education_levels', 'term_result_mode', 'layout', 'grading_bands', 'traits', 'is_active'];

    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'education_levels' => 'array',
            'grading_bands' => 'array',
            'traits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function selections(): HasMany
    {
        return $this->hasMany(SchoolReportCardTemplateSelection::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(PlatformReportCardTemplateAccess::class);
    }
}
