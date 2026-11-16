<?php

namespace App\Models;

use Database\Factories\ImpersonationAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationAudit extends Model
{
    /** @use HasFactory<ImpersonationAuditFactory> */
    use HasFactory;

    protected $fillable = [
        'impersonator_user_id',
        'target_user_id',
        'school_id',
        'impersonator_name',
        'impersonator_email',
        'target_name',
        'target_email',
        'school_name',
        'status',
        'started_at',
        'ended_at',
        'start_ip',
        'end_ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_user_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
