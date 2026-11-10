<?php

namespace App\Models;

use Database\Factories\ReportCardEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardEvent extends Model
{
    /** @use HasFactory<ReportCardEventFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'report_card_run_id', 'student_report_card_id', 'actor_id', 'event', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ReportCardRun::class, 'report_card_run_id');
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(StudentReportCard::class, 'student_report_card_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
