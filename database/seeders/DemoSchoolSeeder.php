<?php

namespace Database\Seeders;

use App\Models\PlatformReportCardTemplate;
use App\Models\PlatformReportCardTemplateAccess;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Services\Schools\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        School::query()->where('slug', 'demo-school')->first()?->delete();

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@demo-school.test'],
            ['name' => 'Demo School Admin', 'password' => Hash::make('password'), 'global_role' => 'user'],
        );

        $school = School::query()->firstOrCreate(
            ['slug' => 'demo-school'],
            [
                'owner_user_id' => $admin->id,
                'name' => 'Demo School',
                'code' => '12345678',
                'status' => 'approved',
                'primary_color' => '#0f766e',
                'secondary_color' => '#111827',
                'brand_font' => 'Inter',
                'approved_at' => now(),
            ],
        );

        $admin->forceFill(['current_school_id' => $school->id])->save();
        $school->domains()->firstOrCreate(['domain' => 'demo-school.localhost'], ['type' => 'subdomain', 'is_primary' => true]);
        $school->users()->firstOrCreate(['user_id' => $admin->id], ['role' => 'school_super_admin', 'status' => 'active']);

        $permissionCatalog = app(PermissionCatalog::class);
        foreach (['admin', 'teacher', 'bursar', 'nurse', 'librarian', 'cleaner', 'gateman'] as $roleSlug) {
            $role = $school->roles()->create([
                'name' => str($roleSlug)->headline(),
                'slug' => $roleSlug,
                'description' => str($roleSlug)->headline().' access profile.',
                'is_system' => true,
            ]);

            foreach ($permissionCatalog->defaultRoleAccess($roleSlug) as $module => $accessLevel) {
                $role->permissions()->create(['module' => $module, 'access_level' => $accessLevel]);
            }
        }

        $primary = $school->classGroups()->firstOrCreate(['name' => 'Primary'], ['sort_order' => 1]);
        $nursery = $school->classGroups()->firstOrCreate(['name' => 'Nursery'], ['sort_order' => 0]);
        $creche = $school->classGroups()->firstOrCreate(['name' => 'Creche'], ['sort_order' => 0]);
        $secondary = $school->classGroups()->firstOrCreate(['name' => 'Secondary'], ['sort_order' => 3]);
        $crecheOne = $school->classes()->firstOrCreate(['name' => 'Creche 1'], ['class_group_id' => $creche->id, 'sort_order' => 1]);
        $nurseryOne = $school->classes()->firstOrCreate(['name' => 'Nursery 1'], ['class_group_id' => $nursery->id, 'sort_order' => 1]);
        $nurseryTwo = $school->classes()->firstOrCreate(['name' => 'Nursery 2'], ['class_group_id' => $nursery->id, 'sort_order' => 2]);
        $primaryOne = $school->classes()->firstOrCreate(['name' => 'Primary 1'], ['class_group_id' => $primary->id, 'sort_order' => 1]);
        $primaryTwo = $school->classes()->firstOrCreate(['name' => 'Primary 2'], ['class_group_id' => $primary->id, 'sort_order' => 2]);
        $primaryThree = $school->classes()->firstOrCreate(['name' => 'Primary 3'], ['class_group_id' => $primary->id, 'sort_order' => 3]);
        $jssOne = $school->classes()->firstOrCreate(['name' => 'JSS 1'], ['class_group_id' => $secondary->id, 'sort_order' => 1]);
        $jssTwo = $school->classes()->firstOrCreate(['name' => 'JSS 2'], ['class_group_id' => $secondary->id, 'sort_order' => 2]);
        $crecheArm = $school->classArms()->firstOrCreate(['name' => 'Creche 1A'], ['school_class_id' => $crecheOne->id, 'label' => 'A']);
        $nurseryOneArm = $school->classArms()->firstOrCreate(['name' => 'Nursery 1A'], ['school_class_id' => $nurseryOne->id, 'label' => 'A']);
        $nurseryArm = $school->classArms()->firstOrCreate(['name' => 'Nursery 2A'], ['school_class_id' => $nurseryTwo->id, 'label' => 'A']);
        $armA = $school->classArms()->firstOrCreate(['name' => 'Primary 1A'], ['school_class_id' => $primaryOne->id, 'label' => 'A']);
        $armB = $school->classArms()->firstOrCreate(['name' => 'Primary 1B'], ['school_class_id' => $primaryOne->id, 'label' => 'B']);
        $armC = $school->classArms()->firstOrCreate(['name' => 'Primary 2A'], ['school_class_id' => $primaryTwo->id, 'label' => 'A']);
        $armD = $school->classArms()->firstOrCreate(['name' => 'Primary 3A'], ['school_class_id' => $primaryThree->id, 'label' => 'A']);
        $jssArm = $school->classArms()->firstOrCreate(['name' => 'JSS 1A'], ['school_class_id' => $jssOne->id, 'label' => 'A']);
        $jssTwoArm = $school->classArms()->firstOrCreate(['name' => 'JSS 2A'], ['school_class_id' => $jssTwo->id, 'label' => 'A']);

        $session = $school->academicSessions()->firstOrCreate(
            ['name' => '2026/2027'],
            ['starts_on' => '2026-09-01', 'ends_on' => '2027-07-31', 'is_current' => true],
        );
        $term = $school->terms()->firstOrCreate(
            ['academic_session_id' => $session->id, 'name' => 'First Term'],
            ['starts_on' => '2026-09-01', 'ends_on' => '2026-12-12', 'is_current' => true],
        );

        $schoolSubjects = collect(['English Language', 'Mathematics', 'Basic Science', 'Civic Education'])->map(function (string $name) use ($school) {
            $subject = Subject::query()->firstOrCreate(
                ['name' => $name],
                ['code' => str($name)->slug('_')->upper()->limit(20, ''), 'status' => 'approved'],
            );

            return $school->schoolSubjects()->firstOrCreate(
                ['subject_id' => $subject->id],
                ['custom_name' => null, 'is_active' => true],
            );
        });

        $teachers = collect([
            ['Ada', 'Okafor', 'MAT', 'B.Ed Mathematics'],
            ['Tunde', 'Bello', 'ENG', 'B.A English'],
            ['Kemi', 'Adeoye', 'SCI', 'B.Sc Biology'],
            ['Ibrahim', 'Sani', 'CIV', 'B.Ed Social Studies'],
        ])->map(function (array $data) use ($school) {
            $user = User::query()->firstOrCreate(
                ['email' => strtolower($data[0]).'@demo-school.test'],
                ['name' => $data[0].' '.$data[1], 'password' => Hash::make('password'), 'current_school_id' => $school->id],
            );
            $staff = $school->staffProfiles()->firstOrCreate(
                ['email' => $user->email],
                ['user_id' => $user->id, 'staff_no' => $data[2], 'first_name' => $data[0], 'last_name' => $data[1], 'qualification' => $data[3], 'specialization' => $data[2], 'job_title' => 'Teacher', 'role' => 'teacher', 'status' => 'active'],
            );

            return $school->teacherProfiles()->firstOrCreate(
                ['staff_profile_id' => $staff->id],
                ['user_id' => $user->id, 'staff_no' => $staff->staff_no],
            );
        });

        $teacherRole = $school->roles()->where('slug', 'teacher')->firstOrFail();
        foreach ($teachers as $teacher) {
            $school->users()->firstOrCreate(
                ['user_id' => $teacher->user_id],
                ['role' => 'teacher', 'school_role_id' => $teacherRole->id, 'status' => 'active'],
            );
        }

        foreach ([$crecheArm, $nurseryOneArm, $nurseryArm, $armA, $armB, $armC, $armD, $jssArm, $jssTwoArm] as $arm) {
            $school->teacherClassArms()->firstOrCreate([
                'teacher_profile_id' => $teachers[0]->id,
                'academic_session_id' => $session->id,
                'term_id' => $term->id,
                'class_arm_id' => $arm->id,
            ]);

            foreach ($schoolSubjects as $index => $schoolSubject) {
                $classTermSubject = $school->classTermSubjects()->firstOrCreate([
                    'academic_session_id' => $session->id,
                    'term_id' => $term->id,
                    'class_arm_id' => $arm->id,
                    'school_subject_id' => $schoolSubject->id,
                ]);

                $school->teacherClassTermSubjects()->firstOrCreate([
                    'teacher_profile_id' => $teachers[$index % $teachers->count()]->id,
                    'class_term_subject_id' => $classTermSubject->id,
                ]);

                $assessmentGroup = $school->assessmentGroups()->firstOrCreate([
                    'class_term_subject_id' => $classTermSubject->id,
                    'name' => 'Default assessment flow',
                ], [
                    'total_score' => 100,
                ]);

                foreach ([['CA 1', 20], ['CA 2', 30], ['Exam', 50]] as $itemIndex => $item) {
                    $assessmentGroup->items()->updateOrCreate(
                        ['name' => $item[0]],
                        ['school_id' => $school->id, 'max_score' => $item[1], 'sort_order' => $itemIndex + 1],
                    );
                }
            }
        }

        foreach ([
            ['Ayo', 'Mensah', 'ADM001', $armA],
            ['Ife', 'Balogun', 'ADM002', $armA],
            ['Chika', 'Eze', 'ADM003', $armA],
            ['Sade', 'Lawal', 'ADM004', $armB],
            ['Musa', 'Garba', 'ADM005', $armB],
            ['Tomi', 'Adeyemi', 'ADM006', $armB],
            ['Lara', 'Okon', 'ADM007', $armC],
            ['Daniel', 'Ibrahim', 'ADM008', $armC],
            ['Nora', 'Umeh', 'ADM009', $nurseryArm], ['Femi', 'Cole', 'ADM010', $jssArm],
            ['Amara', 'Nwosu', 'ADM011', $crecheArm], ['Joshua', 'Bamidele', 'ADM012', $nurseryOneArm],
            ['Zainab', 'Yusuf', 'ADM013', $armA], ['Emeka', 'Obi', 'ADM014', $armA],
            ['Grace', 'Ojo', 'ADM015', $armB], ['Kunle', 'Akin', 'ADM016', $armB],
            ['Bisi', 'Ola', 'ADM017', $armC], ['Samuel', 'Musa', 'ADM018', $armC],
            ['Adaeze', 'Okafor', 'ADM019', $armD], ['Tosin', 'Adebayo', 'ADM020', $armD],
            ['Maryam', 'Ali', 'ADM021', $jssArm], ['David', 'Eze', 'ADM022', $jssArm],
            ['Precious', 'John', 'ADM023', $jssTwoArm], ['Hassan', 'Bello', 'ADM024', $jssTwoArm],
        ] as $studentData) {
            $student = $school->studentProfiles()->firstOrCreate(
                ['admission_no' => $studentData[2]],
                ['first_name' => $studentData[0], 'last_name' => $studentData[1], 'status' => 'active'],
            );
            $school->studentEnrollments()->updateOrCreate([
                'student_profile_id' => $student->id,
                'academic_session_id' => $session->id,
                'term_id' => $term->id,
            ], [
                'class_arm_id' => $studentData[3]->id,
                'enrolled_on' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $safsimsTemplate = PlatformReportCardTemplate::query()
            ->where('name', 'SAFSIMS-style Secondary Report')
            ->first();

        if ($safsimsTemplate) {
            PlatformReportCardTemplateAccess::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'platform_report_card_template_id' => $safsimsTemplate->id,
                ],
                [
                    'status' => 'granted',
                    'payment_method' => 'offline',
                    'payment_reference' => 'DEMO-OFFLINE-PAYMENT',
                    'amount' => 0,
                    'currency' => 'NGN',
                    'payment_note' => 'Seeded demonstration entitlement.',
                    'granted_at' => now(),
                    'revoked_at' => null,
                ],
            );
        }
    }
}
