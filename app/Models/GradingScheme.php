<?php

namespace App\Models;

use Database\Factories\GradingSchemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScheme extends Model
{
    /** @use HasFactory<GradingSchemeFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'class_group_id', 'name', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function bands(): HasMany
    {
        return $this->hasMany(GradingBand::class)->orderBy('minimum_score');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ReportCardTemplate::class);
    }
}
