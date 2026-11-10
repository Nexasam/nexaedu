<?php

namespace App\Models;

use App\Models\SchoolReportCardTemplateSelection;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_user_id',
        'principal_staff_profile_id',
        'name',
        'code',
        'slug',
        'status',
        'logo_path',
        'primary_color',
        'secondary_color',
        'brand_font',
        'address',
        'phone',
        'email',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        $version = $this->updated_at?->timestamp ?? 0;
        $storageUrl = Storage::disk('public')->url($this->logo_path);
        $path = parse_url($storageUrl, PHP_URL_PATH);

        return ($path ?: $storageUrl)."?v={$version}";
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(SchoolDomain::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(SchoolUser::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(SchoolRole::class);
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function classArms(): HasMany
    {
        return $this->hasMany(ClassArm::class);
    }

    public function academicSessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function schoolSubjects(): HasMany
    {
        return $this->hasMany(SchoolSubject::class);
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    public function principalStaffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'principal_staff_profile_id');
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function parentProfiles(): HasMany
    {
        return $this->hasMany(ParentProfile::class);
    }

    public function teacherProfiles(): HasMany
    {
        return $this->hasMany(TeacherProfile::class);
    }

    public function classTermSubjects(): HasMany
    {
        return $this->hasMany(ClassTermSubject::class);
    }

    public function teacherClassArms(): HasMany
    {
        return $this->hasMany(TeacherClassArm::class);
    }

    public function teacherClassTermSubjects(): HasMany
    {
        return $this->hasMany(TeacherClassTermSubject::class);
    }

    public function assessmentGroups(): HasMany
    {
        return $this->hasMany(AssessmentGroup::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function studentAssessmentScores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }

    public function gradingSchemes(): HasMany
    {
        return $this->hasMany(GradingScheme::class);
    }

    public function reportCardTemplates(): HasMany
    {
        return $this->hasMany(ReportCardTemplate::class);
    }

    public function reportCardTemplateSelections(): HasMany
    {
        return $this->hasMany(SchoolReportCardTemplateSelection::class);
    }

    public function platformReportCardTemplateAccesses(): HasMany
    {
        return $this->hasMany(PlatformReportCardTemplateAccess::class);
    }

    public function reportCardTraitDefinitions(): HasMany
    {
        return $this->hasMany(ReportCardTraitDefinition::class);
    }

    public function reportCardRuns(): HasMany
    {
        return $this->hasMany(ReportCardRun::class);
    }

    public function studentReportCards(): HasMany
    {
        return $this->hasMany(StudentReportCard::class);
    }
}
