<?php

namespace App\Models;

use Database\Factories\ClassArmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassArm extends Model
{
    /** @use HasFactory<ClassArmFactory> */
    use HasFactory;

    protected $fillable = ['school_id', 'school_class_id', 'name', 'label'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function classTermSubjects(): HasMany
    {
        return $this->hasMany(ClassTermSubject::class);
    }

    public function reportCardRuns(): HasMany
    {
        return $this->hasMany(ReportCardRun::class);
    }
}
