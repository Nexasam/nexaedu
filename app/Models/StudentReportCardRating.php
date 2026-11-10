<?php

namespace App\Models;

use Database\Factories\StudentReportCardRatingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentReportCardRating extends Model
{
    /** @use HasFactory<StudentReportCardRatingFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'student_report_card_id', 'report_card_trait_definition_id', 'rating'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(StudentReportCard::class, 'student_report_card_id');
    }

    public function traitDefinition(): BelongsTo
    {
        return $this->belongsTo(ReportCardTraitDefinition::class, 'report_card_trait_definition_id');
    }
}
