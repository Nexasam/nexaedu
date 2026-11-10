<?php

namespace App\Models;

use Database\Factories\PlatformReportCardTemplateAccessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformReportCardTemplateAccess extends Model
{
    /** @use HasFactory<PlatformReportCardTemplateAccessFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id', 'platform_report_card_template_id', 'status', 'payment_method', 'payment_reference',
        'amount', 'currency', 'payment_note', 'granted_by', 'granted_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'granted_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function platformTemplate(): BelongsTo
    {
        return $this->belongsTo(PlatformReportCardTemplate::class, 'platform_report_card_template_id');
    }

    public function granter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
