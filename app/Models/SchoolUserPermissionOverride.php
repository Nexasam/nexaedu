<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolUserPermissionOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_user_id',
        'module',
        'access_level',
        'reason',
    ];

    public function schoolUser(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class);
    }
}
