import DataTable from '@/Components/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

function teacherName(assignment) {
    const staff = assignment.teacher_profile?.staff_profile;

    return `${staff?.first_name ?? ''} ${staff?.last_name ?? ''}`.trim() || assignment.teacher_profile?.staff_no || 'Teacher';
}

const pillClasses = [
    'bg-[var(--brand-primary-soft)] text-[var(--brand-primary)] ring-[var(--brand-primary-border)]',
    'bg-sky-50 text-sky-700 ring-sky-100',
    'bg-amber-50 text-amber-700 ring-amber-100',
    'bg-rose-50 text-rose-700 ring-rose-100',
    'bg-violet-50 text-violet-700 ring-violet-100',
];

function TeacherPills({ teachers }) {
    if (!teachers.length) {
        return <span className="text-slate-400">Unassigned</span>;
    }

    return (
        <div className="flex flex-wrap gap-1.5">
            {teachers.map((teacher, index) => (
                <span key={`${teacher}-${index}`} className={`rounded-full px-2.5 py-1 text-xs font-bold ring-1 ${pillClasses[index % pillClasses.length]}`}>
                    {teacher}
                </span>
            ))}
        </div>
    );
}

export default function Subjects({ students, classTermSubjects }) {
    const rows = students.flatMap((student) => {
        const enrollment = student.enrollments?.[0];

        if (!enrollment) {
            return [{
                id: `student-${student.id}`,
                student: `${student.first_name} ${student.last_name}`,
                classArm: 'Not enrolled',
                subject: 'No subjects',
                teachers: [],
                teacherSearch: '',
            }];
        }

        const subjects = classTermSubjects.filter((item) => (
            String(item.class_arm_id) === String(enrollment.class_arm_id)
            && String(item.academic_session_id) === String(enrollment.academic_session_id)
            && (!enrollment.term_id || String(item.term_id) === String(enrollment.term_id))
        ));

        if (subjects.length === 0) {
            return [{
                id: `student-${student.id}`,
                student: `${student.first_name} ${student.last_name}`,
                classArm: enrollment.class_arm?.name,
                subject: 'No subjects assigned',
                teachers: [],
                teacherSearch: '',
            }];
        }

        return subjects.map((subject) => ({
            teachers: subject.teacher_assignments?.map(teacherName) ?? [],
            id: `${student.id}-${subject.id}`,
            student: `${student.first_name} ${student.last_name}`,
            classArm: enrollment.class_arm?.name,
            session: enrollment.academic_session?.name,
            term: enrollment.term?.name,
            subject: subject.school_subject?.custom_name || subject.school_subject?.subject?.name,
            teacherSearch: subject.teacher_assignments?.map(teacherName).join(' ') ?? '',
        }));
    });

    return (
        <AuthenticatedLayout title="Student subjects" subtitle="See each student’s class-arm subjects and assigned teachers.">
            <Head title="Student subjects" />

            <DataTable
                columns={[
                    { key: 'student', label: 'Student', className: 'font-semibold text-slate-950' },
                    { key: 'classArm', label: 'Class arm', className: 'text-slate-600' },
                    { key: 'subject', label: 'Subject', className: 'text-slate-600' },
                    {
                        key: 'teachers',
                        label: 'Teacher(s)',
                        className: 'text-slate-600',
                        render: (row) => <TeacherPills teachers={row.teachers} />,
                        sortValue: (row) => row.teacherSearch,
                    },
                ]}
                rows={rows}
                emptyLabel="No student subject records yet."
                searchPlaceholder="Search students, subjects, or teachers"
            />
        </AuthenticatedLayout>
    );
}
