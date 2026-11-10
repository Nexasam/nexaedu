<?php

namespace App\Models;

use Database\Factories\AcademicSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSession extends Model
{
    /** @use HasFactory<AcademicSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id', 'name', 'starts_on', 'ends_on', 'is_current',
        'score_entries_locked', 'score_lock_at', 'score_unlock_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_current' => 'boolean',
            'score_entries_locked' => 'boolean',
            'score_lock_at' => 'datetime',
            'score_unlock_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function reportCardRuns(): HasMany
    {
        return $this->hasMany(ReportCardRun::class);
    }
}
