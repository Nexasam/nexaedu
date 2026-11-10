<?php

namespace App\Models;

use Database\Factories\SchoolSubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolSubject extends Model
{
    /** @use HasFactory<SchoolSubjectFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'subject_id', 'custom_name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classTermSubjects(): HasMany
    {
        return $this->hasMany(ClassTermSubject::class);
    }
}
