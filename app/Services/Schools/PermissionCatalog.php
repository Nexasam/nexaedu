<?php

namespace App\Services\Schools;

class PermissionCatalog
{
    /**
     * @return array<int, array{key: string, label: string, group: string, description: string}>
     */
    public function modules(): array
    {
        return [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'group' => 'Core', 'description' => 'View school overview and setup progress.'],
            ['key' => 'academic_setup', 'label' => 'Academic setup', 'group' => 'Setup', 'description' => 'Manage class groups, classes, arms, sessions, terms, and subjects.'],
            ['key' => 'staff_setup', 'label' => 'Staff setup', 'group' => 'People', 'description' => 'Create staff records, invite staff, and manage staff access.'],
            ['key' => 'student_setup', 'label' => 'Student setup', 'group' => 'People', 'description' => 'Register students and guardian records.'],
            ['key' => 'class_term_subjects', 'label' => 'Class term subjects', 'group' => 'Academics', 'description' => 'Attach subjects to class arms, sessions, and terms.'],
            ['key' => 'teacher_assignments', 'label' => 'Teacher assignments', 'group' => 'Academics', 'description' => 'Assign teachers to class-term subjects.'],
            ['key' => 'assessments', 'label' => 'Assessments', 'group' => 'Results', 'description' => 'Create assessment groups and score structure.'],
            ['key' => 'score_entry', 'label' => 'Score entry', 'group' => 'Results', 'description' => 'Enter, import, and complete subject scores.'],
            ['key' => 'result_readiness', 'label' => 'Result readiness', 'group' => 'Results', 'description' => 'Review score completion and report-card readiness.'],
            ['key' => 'report_card_settings', 'label' => 'Report-card setup', 'group' => 'Results', 'description' => 'Manage templates, grading scales, and personal-development traits.'],
            ['key' => 'report_card_preparation', 'label' => 'Report-card preparation', 'group' => 'Results', 'description' => 'Generate draft cards and prepare attendance, comments, and promotion decisions.'],
            ['key' => 'report_card_approval', 'label' => 'Report-card approval and publishing', 'group' => 'Results', 'description' => 'Approve, publish, revoke, and release report cards.'],
            ['key' => 'report_card_viewing', 'label' => 'Report-card viewing', 'group' => 'Results', 'description' => 'View and print generated report cards.'],
            ['key' => 'attendance', 'label' => 'Attendance', 'group' => 'Daily operations', 'description' => 'Mark and review student attendance.'],
            ['key' => 'fees', 'label' => 'Fees', 'group' => 'Finance', 'description' => 'Manage billing, payments, and fee reports.'],
            ['key' => 'reports', 'label' => 'Reports', 'group' => 'Insights', 'description' => 'View operational and academic reports.'],
            ['key' => 'settings', 'label' => 'School settings', 'group' => 'Administration', 'description' => 'Manage school branding and tenant settings.'],
            ['key' => 'role_permissions', 'label' => 'Roles and permissions', 'group' => 'Administration', 'description' => 'Configure access for roles and account-specific overrides.'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function accessLevels(): array
    {
        return ['none', 'readonly', 'readwrite'];
    }

    /**
     * @return array<string, string>
     */
    public function defaultRoleAccess(string $slug): array
    {
        $defaults = [
            'school_super_admin' => 'readwrite',
            'admin' => 'readwrite',
            'teacher' => 'none',
            'bursar' => 'none',
            'nurse' => 'none',
            'librarian' => 'none',
            'cleaner' => 'none',
            'gateman' => 'none',
        ];

        $accessLevel = $defaults[$slug] ?? 'none';
        $permissions = [];

        foreach ($this->modules() as $module) {
            $permissions[$module['key']] = $accessLevel;
        }

        if ($slug === 'teacher') {
            $permissions['dashboard'] = 'readonly';
            $permissions['student_setup'] = 'readonly';
            $permissions['attendance'] = 'readwrite';
            $permissions['assessments'] = 'readwrite';
            $permissions['score_entry'] = 'readwrite';
            $permissions['result_readiness'] = 'readonly';
            $permissions['report_card_preparation'] = 'readwrite';
            $permissions['report_card_viewing'] = 'readonly';
        }

        if ($slug === 'bursar') {
            $permissions['dashboard'] = 'readonly';
            $permissions['student_setup'] = 'readonly';
            $permissions['fees'] = 'readwrite';
            $permissions['reports'] = 'readonly';
        }

        if (in_array($slug, ['nurse', 'librarian', 'gateman'], true)) {
            $permissions['dashboard'] = 'readonly';
            $permissions['student_setup'] = 'readonly';
        }

        return $permissions;
    }
}
