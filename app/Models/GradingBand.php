<?php

namespace App\Models;

use Database\Factories\GradingBandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingBand extends Model
{
    /** @use HasFactory<GradingBandFactory> */
    use HasFactory;

    protected $fillable = ['grading_scheme_id', 'minimum_score', 'maximum_score', 'grade', 'label', 'remark', 'sort_order'];

    protected function casts(): array
    {
        return ['minimum_score' => 'decimal:2', 'maximum_score' => 'decimal:2'];
    }

    public function gradingScheme(): BelongsTo
    {
        return $this->belongsTo(GradingScheme::class);
    }
}
