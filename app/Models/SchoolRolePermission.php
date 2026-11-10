<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolRolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_role_id',
        'module',
        'access_level',
    ];

    public function schoolRole(): BelongsTo
    {
        return $this->belongsTo(SchoolRole::class);
    }
}
