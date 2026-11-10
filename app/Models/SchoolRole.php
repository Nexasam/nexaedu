<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(SchoolRolePermission::class);
    }

    public function schoolUsers(): HasMany
    {
        return $this->hasMany(SchoolUser::class);
    }
}
