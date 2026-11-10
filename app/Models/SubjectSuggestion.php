<?php

namespace App\Models;

use Database\Factories\SubjectSuggestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectSuggestion extends Model
{
    /** @use HasFactory<SubjectSuggestionFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'suggested_by',
        'name',
        'reason',
        'status',
        'matched_subject_id',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function suggester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_by');
    }

    public function matchedSubject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'matched_subject_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
