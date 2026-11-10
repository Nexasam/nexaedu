<?php

namespace App\Models;

use Database\Factories\SchoolReportCardTemplateSelectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolReportCardTemplateSelection extends Model
{
    /** @use HasFactory<SchoolReportCardTemplateSelectionFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'class_group_id', 'platform_report_card_template_id', 'report_card_template_id'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function platformTemplate(): BelongsTo
    {
        return $this->belongsTo(PlatformReportCardTemplate::class, 'platform_report_card_template_id');
    }

    public function reportCardTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportCardTemplate::class);
    }
}
