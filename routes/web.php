<?php

use App\Http\Controllers\Platform\MaintenanceController;
use App\Http\Controllers\Platform\ReportCardTemplateAccessController;
use App\Http\Controllers\Platform\ReportCardTemplateController as PlatformReportCardTemplateController;
use App\Http\Controllers\Platform\SchoolApprovalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SchoolAdmin\AcademicSetupController;
use App\Http\Controllers\SchoolAdmin\ApprovedReportCardRunController;
use App\Http\Controllers\SchoolAdmin\AssessmentSetupController;
use App\Http\Controllers\SchoolAdmin\BroadsheetController;
use App\Http\Controllers\SchoolAdmin\DashboardController;
use App\Http\Controllers\SchoolAdmin\ReportCardController;
use App\Http\Controllers\SchoolAdmin\ReportCardPdfController;
use App\Http\Controllers\SchoolAdmin\ReportCardPolicyController;
use App\Http\Controllers\SchoolAdmin\ReportCardPreviewController;
use App\Http\Controllers\SchoolAdmin\ReportCardSignatureController;
use App\Http\Controllers\SchoolAdmin\ReportCardTemplateSelectionController;
use App\Http\Controllers\SchoolAdmin\RolePermissionController;
use App\Http\Controllers\SchoolAdmin\SchoolOnboardingController;
use App\Http\Controllers\SchoolAdmin\SchoolReadinessReportController;
use App\Http\Controllers\SchoolAdmin\SchoolSettingsController;
use App\Http\Controllers\SchoolAdmin\ScoreEntryController;
use App\Http\Controllers\SchoolAdmin\StaffSetupController;
use App\Http\Controllers\SchoolAdmin\StaffSignatureController;
use App\Http\Controllers\SchoolAdmin\StudentSetupController;
use App\Http\Controllers\SchoolAdmin\StudentSubjectController;
use App\Http\Controllers\SchoolAdmin\TeacherAssignmentController;
use App\Http\Controllers\SchoolAdmin\UserPermissionOverrideController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/school/onboarding', [SchoolOnboardingController::class, 'create'])->name('school.onboarding.create');
    Route::post('/school/onboarding', [SchoolOnboardingController::class, 'store'])->name('school.onboarding.store');
    Route::get('/school/onboarding/pending', [SchoolOnboardingController::class, 'pending'])->name('school.onboarding.pending');

    Route::middleware(['school.ready'])->prefix('school')->name('school.')->group(function () {
        Route::get('/academic-setup', [AcademicSetupController::class, 'index'])->middleware('school.permission:academic_setup,readonly')->name('academic-setup.index');
        Route::middleware('school.permission:academic_setup,readwrite')->group(function () {
            Route::post('/class-groups', [AcademicSetupController::class, 'storeClassGroup'])->name('class-groups.store');
            Route::post('/classes', [AcademicSetupController::class, 'storeClass'])->name('classes.store');
            Route::post('/class-arms', [AcademicSetupController::class, 'storeClassArm'])->name('class-arms.store');
            Route::post('/academic-sessions', [AcademicSetupController::class, 'storeAcademicSession'])->name('academic-sessions.store');
            Route::post('/terms', [AcademicSetupController::class, 'storeTerm'])->name('terms.store');
            Route::post('/school-subjects', [AcademicSetupController::class, 'storeSchoolSubject'])->name('school-subjects.store');
            Route::post('/class-groups/defaults', [AcademicSetupController::class, 'storeDefaultClassGroups'])->name('class-groups.defaults.store');
            Route::post('/classes/defaults', [AcademicSetupController::class, 'storeDefaultClasses'])->name('classes.defaults.store');
            Route::post('/school-subjects/bulk', [AcademicSetupController::class, 'storeBulkSchoolSubjects'])->name('school-subjects.bulk.store');
            Route::put('/class-groups/{classGroup}', [AcademicSetupController::class, 'updateClassGroup'])->name('class-groups.update');
            Route::delete('/class-groups/{classGroup}', [AcademicSetupController::class, 'destroyClassGroup'])->name('class-groups.destroy');
            Route::put('/classes/{schoolClass}', [AcademicSetupController::class, 'updateClass'])->name('classes.update');
            Route::delete('/classes/{schoolClass}', [AcademicSetupController::class, 'destroyClass'])->name('classes.destroy');
            Route::put('/class-arms/{classArm}', [AcademicSetupController::class, 'updateClassArm'])->name('class-arms.update');
            Route::delete('/class-arms/{classArm}', [AcademicSetupController::class, 'destroyClassArm'])->name('class-arms.destroy');
            Route::put('/academic-sessions/{academicSession}', [AcademicSetupController::class, 'updateAcademicSession'])->name('academic-sessions.update');
            Route::post('/academic-sessions/{academicSession}/duplicate', [AcademicSetupController::class, 'duplicateAcademicSession'])->name('academic-sessions.duplicate');
            Route::put('/academic-sessions/{academicSession}/score-lock', [AcademicSetupController::class, 'updateSessionScoreLock'])->name('academic-sessions.score-lock.update');
            Route::delete('/academic-sessions/{academicSession}', [AcademicSetupController::class, 'destroyAcademicSession'])->name('academic-sessions.destroy');
            Route::put('/terms/{term}', [AcademicSetupController::class, 'updateTerm'])->name('terms.update');
            Route::delete('/terms/{term}', [AcademicSetupController::class, 'destroyTerm'])->name('terms.destroy');
            Route::put('/school-subjects/{schoolSubject}', [AcademicSetupController::class, 'updateSchoolSubject'])->name('school-subjects.update');
            Route::delete('/school-subjects/{schoolSubject}', [AcademicSetupController::class, 'destroySchoolSubject'])->name('school-subjects.destroy');
        });
        Route::get('/staff-setup', [StaffSetupController::class, 'index'])->middleware('school.permission:staff_setup,readonly')->name('staff-setup.index');
        Route::middleware('school.permission:staff_setup,readwrite')->group(function () {
            Route::post('/staff', [StaffSetupController::class, 'store'])->name('staff.store');
            Route::post('/staff/import', [StaffSetupController::class, 'import'])->name('staff.import');
            Route::post('/staff/{staffProfile}/invite', [StaffSetupController::class, 'invite'])->name('staff.invite');
            Route::post('/staff/{staffProfile}/signature', StaffSignatureController::class)->name('staff.signature');
            Route::put('/school-users/{schoolUser}/permission-overrides', [UserPermissionOverrideController::class, 'update'])->name('school-users.permission-overrides.update');
        });
        Route::get('/teachers/assignments', [TeacherAssignmentController::class, 'index'])->middleware('school.permission:teacher_assignments,readonly')->name('teachers.assignments.index');
        Route::middleware('school.permission:teacher_assignments,readwrite')->group(function () {
            Route::post('/teachers/class-arms', [TeacherAssignmentController::class, 'storeClassArm'])->name('teachers.class-arms.store');
            Route::post('/teachers/subjects', [TeacherAssignmentController::class, 'storeSubjects'])->name('teachers.subjects.store');
            Route::delete('/teachers/class-arms/{teacherClassArm}', [TeacherAssignmentController::class, 'destroyClassArm'])->name('teachers.class-arms.destroy');
            Route::delete('/teachers/subjects/{teacherClassTermSubject}', [TeacherAssignmentController::class, 'destroySubject'])->name('teachers.subjects.destroy');
        });
        Route::get('/roles-permissions', [RolePermissionController::class, 'index'])->middleware('school.permission:role_permissions,readonly')->name('roles-permissions.index');
        Route::put('/roles/{schoolRole}/permissions', [RolePermissionController::class, 'update'])->middleware('school.permission:role_permissions,readwrite')->name('roles.permissions.update');
        Route::get('/student-setup', [StudentSetupController::class, 'index'])->middleware('school.permission:student_setup,readonly')->name('student-setup.index');
        Route::middleware('school.permission:student_setup,readwrite')->group(function () {
            Route::post('/students', [StudentSetupController::class, 'store'])->name('students.store');
            Route::post('/students/import', [StudentSetupController::class, 'import'])->name('students.import');
        });
        Route::get('/students/subjects', [StudentSubjectController::class, 'index'])->middleware('school.permission:student_setup,readonly')->name('students.subjects.index');
        Route::get('/assessments', [AssessmentSetupController::class, 'index'])->middleware('school.permission:assessments,readonly')->name('assessments.index');
        Route::post('/assessments', [AssessmentSetupController::class, 'store'])->middleware('school.permission:assessments,readwrite')->name('assessments.store');
        Route::get('/scores', [ScoreEntryController::class, 'index'])->middleware('school.permission:score_entry,readonly')->name('scores.index');
        Route::get('/reports/readiness', SchoolReadinessReportController::class)->middleware('school.permission:result_readiness,readonly')->name('reports.readiness');
        Route::get('/reports/broadsheet', [BroadsheetController::class, 'index'])->middleware('school.permission:reports,readonly')->name('reports.broadsheet');
        Route::get('/reports/broadsheet/{reportCardRun}/export', [BroadsheetController::class, 'export'])->middleware('school.permission:reports,readonly')->name('reports.broadsheet.export');
        Route::get('/report-cards', [ReportCardController::class, 'index'])->middleware('school.permission:report_card_viewing,readonly')->name('report-cards.index');
        Route::get('/report-cards/{reportCardRun}', [ReportCardController::class, 'show'])->middleware('school.permission:report_card_viewing,readonly')->name('report-cards.show');
        Route::get('/report-cards/{reportCardRun}/preview', ReportCardPreviewController::class)->middleware('school.permission:report_card_viewing,readonly')->name('report-cards.preview');
        Route::get('/report-cards/{reportCardRun}/pdf', ReportCardPdfController::class)->middleware('school.permission:report_card_viewing,readonly')->name('report-cards.pdf');
        Route::get('/report-card-signatures/{studentReportCard}/{type}', ReportCardSignatureController::class)->middleware('school.permission:report_card_viewing,readonly')->name('report-cards.signature');
        Route::get('/scores/templates/{assessmentGroup}', [ScoreEntryController::class, 'template'])->middleware('school.permission:score_entry,readonly')->name('scores.template');
        Route::get('/scores/bulk-template', [ScoreEntryController::class, 'bulkTemplate'])->middleware('school.permission:score_entry,readonly')->name('scores.bulk-template');
        Route::put('/report-card-template-selection', ReportCardTemplateSelectionController::class)->middleware('school.permission:report_card_settings,readwrite')->name('report-card-template-selection.update');
        Route::put('/report-card-templates/{reportCardTemplate}/policy', ReportCardPolicyController::class)->middleware('school.permission:report_card_settings,readwrite')->name('report-card-templates.policy.update');
        Route::post('/report-cards', [ReportCardController::class, 'store'])->middleware('school.permission:report_card_preparation,readwrite')->name('report-cards.store');
        Route::put('/report-cards/{reportCardRun}/students/{studentReportCard}', [ReportCardController::class, 'updateCard'])->middleware('school.permission:report_card_preparation,readwrite')->name('report-cards.students.update');
        Route::post('/report-cards/{reportCardRun}/approval', [ApprovedReportCardRunController::class, 'store'])->middleware('school.permission:report_card_approval,readwrite')->name('report-cards.approval.store');
        Route::delete('/report-cards/{reportCardRun}/approval', [ApprovedReportCardRunController::class, 'destroy'])->middleware('school.permission:report_card_approval,readwrite')->name('report-cards.approval.destroy');
        Route::middleware('school.permission:score_entry,readwrite')->group(function () {
            Route::post('/scores', [ScoreEntryController::class, 'store'])->name('scores.store');
            Route::post('/scores/bulk', [ScoreEntryController::class, 'storeBulk'])->name('scores.bulk-store');
            Route::post('/scores/import', [ScoreEntryController::class, 'import'])->name('scores.import');
            Route::post('/scores/bulk-import', [ScoreEntryController::class, 'bulkImport'])->name('scores.bulk-import');
            Route::post('/scores/{assessmentGroup}/complete', [ScoreEntryController::class, 'complete'])->name('scores.complete');
            Route::post('/scores/{assessmentGroup}/students/{studentProfile}/send-parent', [ScoreEntryController::class, 'sendToParent'])->name('scores.send-parent');
        });
        Route::get('/settings', [SchoolSettingsController::class, 'edit'])->middleware('school.permission:settings,readonly')->name('settings.edit');
        Route::post('/settings', [SchoolSettingsController::class, 'update'])->middleware('school.permission:settings,readwrite')->name('settings.update');
    });

    Route::middleware('platform.admin')->group(function () {
        Route::get('/platform/schools', [SchoolApprovalController::class, 'index'])->name('platform.schools.index');
        Route::post('/platform/schools/{school}/approval', [SchoolApprovalController::class, 'store'])->name('platform.schools.approval.store');
        Route::get('/platform/report-card-templates', [PlatformReportCardTemplateController::class, 'index'])->name('platform.report-card-templates.index');
        Route::post('/platform/report-card-templates', [PlatformReportCardTemplateController::class, 'store'])->name('platform.report-card-templates.store');
        Route::put('/platform/report-card-templates/{platformReportCardTemplate}', [PlatformReportCardTemplateController::class, 'update'])->name('platform.report-card-templates.update');
        Route::delete('/platform/report-card-templates/{platformReportCardTemplate}', [PlatformReportCardTemplateController::class, 'destroy'])->name('platform.report-card-templates.destroy');
        Route::post('/platform/report-card-templates/{platformReportCardTemplate}/accesses', [ReportCardTemplateAccessController::class, 'store'])->name('platform.report-card-templates.accesses.store');
        Route::delete('/platform/report-card-template-accesses/{platformReportCardTemplateAccess}', [ReportCardTemplateAccessController::class, 'destroy'])->name('platform.report-card-template-accesses.destroy');
        Route::get('/platform/maintenance', [MaintenanceController::class, 'index'])->name('platform.maintenance.index');
        Route::post('/platform/maintenance', [MaintenanceController::class, 'store'])
            ->middleware(['password.confirm', 'throttle:6,1'])
            ->name('platform.maintenance.store');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/signature', [StaffSignatureController::class, 'storeMine'])->name('profile.signature');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
