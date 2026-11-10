<?php

namespace App\Models;

use Database\Factories\SchoolDomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDomain extends Model
{
    /** @use HasFactory<SchoolDomainFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'domain',
        'type',
        'is_primary',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
