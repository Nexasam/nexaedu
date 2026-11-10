<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\ClassArm;
use App\Models\ClassGroup;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StaffAndStudentSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_super_admin_can_create_teacher_staff_record(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();

        $this
            ->actingAs($user)
            ->post(route('school.staff.store'), [
                'staff_no' => 'STAFF-001',
                'first_name' => 'Amina',
                'last_name' => 'Bello',
                'email' => 'amina@example.test',
                'phone' => '08010000000',
                'gender' => 'female',
                'role' => 'teacher',
                'employment_date' => '2026-09-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('staff_profiles', [
            'school_id' => $school->id,
            'staff_no' => 'STAFF-001',
            'email' => 'amina@example.test',
            'role' => 'teacher',
        ]);
        $this->assertDatabaseHas('teacher_profiles', [
            'school_id' => $school->id,
            'staff_no' => 'STAFF-001',
        ]);
        $this->assertDatabaseHas('school_users', [
            'school_id' => $school->id,
            'role' => 'teacher',
        ]);
    }

    public function test_school_super_admin_can_create_student_with_enrollment_and_guardian(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Primary']);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'name' => 'Primary 1',
        ]);
        $classArm = ClassArm::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $schoolClass->id,
            'name' => 'Primary 1A',
        ]);
        $session = AcademicSession::factory()->create([
            'school_id' => $school->id,
            'name' => '2026/2027',
        ]);
        $term = Term::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'name' => 'First Term',
        ]);

        $this
            ->actingAs($user)
            ->post(route('school.students.store'), [
                'admission_no' => 'ADM-001',
                'first_name' => 'David',
                'last_name' => 'Ojo',
                'gender' => 'male',
                'date_of_birth' => '2018-02-10',
                'class_arm_id' => $classArm->id,
                'academic_session_id' => $session->id,
                'term_id' => $term->id,
                'parent_first_name' => 'Grace',
                'parent_last_name' => 'Ojo',
                'parent_email' => 'grace.ojo@example.test',
                'parent_phone' => '08020000000',
                'parent_relationship' => 'mother',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_profiles', [
            'school_id' => $school->id,
            'admission_no' => 'ADM-001',
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'school_id' => $school->id,
            'class_arm_id' => $classArm->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
        ]);
        $this->assertDatabaseHas('parent_profiles', [
            'school_id' => $school->id,
            'email' => 'grace.ojo@example.test',
        ]);
        $this->assertDatabaseHas('school_users', [
            'school_id' => $school->id,
            'role' => 'parent',
        ]);
    }

    public function test_school_super_admin_can_import_staff_from_csv(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();
        $file = UploadedFile::fake()->createWithContent(
            'staff.csv',
            "staff_no,first_name,last_name,email,phone,gender,role,employment_date\nSTAFF-CSV-1,Mary,Ade,mary.ade@example.test,08030000000,female,teacher,2026-09-01\n",
        );

        $this
            ->actingAs($user)
            ->post(route('school.staff.import'), ['staff_file' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('staff_profiles', [
            'school_id' => $school->id,
            'staff_no' => 'STAFF-CSV-1',
            'email' => 'mary.ade@example.test',
        ]);
        $this->assertDatabaseHas('teacher_profiles', [
            'school_id' => $school->id,
            'staff_no' => 'STAFF-CSV-1',
        ]);
    }

    public function test_school_super_admin_can_import_students_from_csv(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();
        [$classArm, $session, $term] = $this->createAcademicPlacement($school);
        $file = UploadedFile::fake()->createWithContent(
            'students.csv',
            "admission_no,first_name,last_name,gender,date_of_birth,class_arm_id,academic_session_id,term_id,parent_first_name,parent_last_name,parent_email,parent_phone,parent_relationship\nADM-CSV-1,Tola,Akin,female,2018-02-10,{$classArm->id},{$session->id},{$term->id},Bisi,Akin,bisi.akin@example.test,08040000000,mother\n",
        );

        $this
            ->actingAs($user)
            ->post(route('school.students.import'), ['student_file' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('student_profiles', [
            'school_id' => $school->id,
            'admission_no' => 'ADM-CSV-1',
        ]);
        $this->assertDatabaseHas('student_enrollments', [
            'school_id' => $school->id,
            'class_arm_id' => $classArm->id,
            'academic_session_id' => $session->id,
            'term_id' => $term->id,
        ]);
        $this->assertDatabaseHas('parent_profiles', [
            'school_id' => $school->id,
            'email' => 'bisi.akin@example.test',
        ]);
    }

    public function test_school_super_admin_can_update_and_delete_academic_setup_records(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Primary']);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'name' => 'Primary 1',
        ]);
        $classArm = ClassArm::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $schoolClass->id,
            'name' => 'Primary 1A',
        ]);
        $session = AcademicSession::factory()->create(['school_id' => $school->id, 'name' => '2026/2027']);
        $term = Term::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'name' => 'First Term',
        ]);
        $subject = Subject::factory()->create(['name' => 'Mathematics', 'status' => 'active']);
        $schoolSubject = $school->schoolSubjects()->create([
            'subject_id' => $subject->id,
            'custom_name' => 'Maths',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('school.class-groups.update', $classGroup), [
            'name' => 'Primary School',
            'sort_order' => 1,
        ])->assertRedirect();
        $this->actingAs($user)->put(route('school.classes.update', $schoolClass), [
            'class_group_id' => $classGroup->id,
            'name' => 'Primary One',
            'sort_order' => 1,
        ])->assertRedirect();
        $this->actingAs($user)->put(route('school.class-arms.update', $classArm), [
            'school_class_id' => $schoolClass->id,
            'name' => 'Primary One A',
            'label' => 'A',
        ])->assertRedirect();
        $this->actingAs($user)->put(route('school.academic-sessions.update', $session), [
            'name' => '2027/2028',
            'starts_on' => null,
            'ends_on' => null,
            'is_current' => true,
        ])->assertRedirect();
        $this->actingAs($user)->put(route('school.terms.update', $term), [
            'academic_session_id' => $session->id,
            'name' => 'Second Term',
            'starts_on' => null,
            'ends_on' => null,
            'is_current' => true,
        ])->assertRedirect();
        $this->actingAs($user)->put(route('school.school-subjects.update', $schoolSubject), [
            'subject_id' => $subject->id,
            'custom_name' => 'Further Maths',
        ])->assertRedirect();

        $this->assertDatabaseHas('class_groups', ['id' => $classGroup->id, 'name' => 'Primary School']);
        $this->assertDatabaseHas('school_classes', ['id' => $schoolClass->id, 'name' => 'Primary One']);
        $this->assertDatabaseHas('class_arms', ['id' => $classArm->id, 'name' => 'Primary One A']);
        $this->assertDatabaseHas('academic_sessions', ['id' => $session->id, 'name' => '2027/2028', 'is_current' => true]);
        $this->assertDatabaseHas('terms', ['id' => $term->id, 'name' => 'Second Term', 'is_current' => true]);
        $this->assertDatabaseHas('school_subjects', ['id' => $schoolSubject->id, 'custom_name' => 'Further Maths']);

        $this->actingAs($user)->delete(route('school.school-subjects.destroy', $schoolSubject))->assertRedirect();
        $this->actingAs($user)->delete(route('school.terms.destroy', $term))->assertRedirect();
        $this->actingAs($user)->delete(route('school.academic-sessions.destroy', $session))->assertRedirect();
        $this->actingAs($user)->delete(route('school.class-arms.destroy', $classArm))->assertRedirect();
        $this->actingAs($user)->delete(route('school.classes.destroy', $schoolClass))->assertRedirect();
        $this->actingAs($user)->delete(route('school.class-groups.destroy', $classGroup))->assertRedirect();

        $this->assertDatabaseMissing('school_subjects', ['id' => $schoolSubject->id]);
        $this->assertDatabaseMissing('terms', ['id' => $term->id]);
        $this->assertDatabaseMissing('academic_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('class_arms', ['id' => $classArm->id]);
        $this->assertDatabaseMissing('school_classes', ['id' => $schoolClass->id]);
        $this->assertDatabaseMissing('class_groups', ['id' => $classGroup->id]);
    }

    public function test_school_super_admin_can_add_default_academic_groups_and_classes(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();

        $this
            ->actingAs($user)
            ->post(route('school.class-groups.defaults.store'))
            ->assertRedirect();
        $this
            ->actingAs($user)
            ->post(route('school.classes.defaults.store'))
            ->assertRedirect();

        foreach (['Creche', 'Nursery', 'Primary', 'Secondary'] as $groupName) {
            $this->assertDatabaseHas('class_groups', [
                'school_id' => $school->id,
                'name' => $groupName,
            ]);
        }

        foreach (['Creche 1', 'Nursery 1', 'Primary 1', 'Primary 6', 'JSS 1', 'SSS 3'] as $className) {
            $this->assertDatabaseHas('school_classes', [
                'school_id' => $school->id,
                'name' => $className,
            ]);
        }
    }

    public function test_school_super_admin_can_bulk_add_school_subjects(): void
    {
        [$user, $school] = $this->createSchoolSuperAdmin();
        $mathematics = Subject::factory()->create(['name' => 'Mathematics', 'status' => 'active']);
        $english = Subject::factory()->create(['name' => 'English Language', 'status' => 'active']);

        $this
            ->actingAs($user)
            ->post(route('school.school-subjects.bulk.store'), [
                'subject_ids' => [$mathematics->id, $english->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('school_subjects', [
            'school_id' => $school->id,
            'subject_id' => $mathematics->id,
        ]);
        $this->assertDatabaseHas('school_subjects', [
            'school_id' => $school->id,
            'subject_id' => $english->id,
        ]);
    }

    /**
     * @return array{0: ClassArm, 1: AcademicSession, 2: Term}
     */
    private function createAcademicPlacement(School $school): array
    {
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id, 'name' => 'Primary']);
        $schoolClass = SchoolClass::factory()->create([
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'name' => 'Primary 1',
        ]);
        $classArm = ClassArm::factory()->create([
            'school_id' => $school->id,
            'school_class_id' => $schoolClass->id,
            'name' => 'Primary 1A',
        ]);
        $session = AcademicSession::factory()->create([
            'school_id' => $school->id,
            'name' => '2026/2027',
        ]);
        $term = Term::factory()->create([
            'school_id' => $school->id,
            'academic_session_id' => $session->id,
            'name' => 'First Term',
        ]);

        return [$classArm, $session, $term];
    }

    /**
     * @return array{0: User, 1: School}
     */
    private function createSchoolSuperAdmin(): array
    {
        $user = User::factory()->create();
        $school = School::factory()->approved()->create(['owner_user_id' => $user->id]);

        $school->users()->create([
            'user_id' => $user->id,
            'role' => 'school_super_admin',
            'status' => 'active',
        ]);
        $user->forceFill(['current_school_id' => $school->id])->save();

        return [$user, $school];
    }
}
