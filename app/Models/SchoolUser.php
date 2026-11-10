<?php

namespace App\Models;

use Database\Factories\SchoolUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolUser extends Model
{
    /** @use HasFactory<SchoolUserFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'role',
        'school_role_id',
        'status',
        'invite_token',
        'invited_at',
        'invite_accepted_at',
        'invite_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'invite_accepted_at' => 'datetime',
            'invite_expires_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schoolRole(): BelongsTo
    {
        return $this->belongsTo(SchoolRole::class);
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(SchoolUserPermissionOverride::class);
    }
}
