import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import { Head, Link } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, ChevronDown, ClipboardList, Eye, GraduationCap, Layers3, Search, UsersRound, X } from 'lucide-react';
import { useMemo, useState } from 'react';

function StatusBadge({ ready }) {
    return (
        <span className={[
            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold',
            ready ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700',
        ].join(' ')}>
            {ready ? 'Ready' : 'Issue found'}
        </span>
    );
}

function SummaryCard({ label, value, icon: Icon }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-center justify-between gap-3">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</p>
                    <p className="mt-1 text-2xl font-black text-slate-950">{value}</p>
                </div>
                <span className="grid h-10 w-10 place-items-center rounded-lg bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                    <Icon className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />
                </span>
            </div>
        </div>
    );
}

export default function Readiness({ report }) {
    const { arms, summary, issues } = report;
    const [studentSearch, setStudentSearch] = useState('');
    const [collapsedStudents, setCollapsedStudents] = useState([]);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const filteredArms = useMemo(() => {
        const query = studentSearch.trim().toLowerCase();

        if (!query) {
            return arms;
        }

        return arms
            .map((arm) => ({
                ...arm,
                students: arm.students.filter((student) => [
                    student.name,
                    student.admission_no,
                ].filter(Boolean).join(' ').toLowerCase().includes(query)),
            }))
            .filter((arm) => arm.students.length > 0);
    }, [arms, studentSearch]);
    const visibleStudentCount = filteredArms.reduce((sum, arm) => sum + arm.students.length, 0);

    const toggleStudent = (studentId) => {
        setCollapsedStudents((current) => (
            current.includes(studentId)
                ? current.filter((id) => id !== studentId)
                : [...current, studentId]
        ));
    };

    return (
        <AuthenticatedLayout title="School readiness report" subtitle="Check every student report card by class arm, subject, and completion status.">
            <Head title="School readiness report" />

            <div className={[
                'rounded-xl border p-4 shadow-sm',
                summary.ready ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50',
            ].join(' ')}>
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div className="flex gap-3">
                        <span className={[
                            'grid h-11 w-11 shrink-0 place-items-center rounded-lg',
                            summary.ready ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700',
                        ].join(' ')}>
                            {summary.ready ? (
                                <CheckCircle2 className="h-6 w-6" aria-hidden="true" strokeWidth={2.25} />
                            ) : (
                                <AlertCircle className="h-6 w-6" aria-hidden="true" strokeWidth={2.25} />
                            )}
                        </span>
                        <div>
                            <h2 className="text-lg font-black text-slate-950">
                                {summary.ready ? 'All report cards are ready' : `${summary.issue_count} readiness issue(s) found`}
                            </h2>
                            <p className="mt-1 text-sm font-medium text-slate-700">
                                {summary.ready
                                    ? 'Every active student has complete subject scores for the configured class-arm subjects.'
                                    : 'Review the red subject rows below to see exactly where scores or assessment setup are missing.'}
                            </p>
                        </div>
                    </div>
                    <Link
                        href={route('school.scores.index')}
                        className="inline-flex items-center justify-center rounded-lg border border-[var(--brand-primary-border)] bg-white px-4 py-2 text-sm font-bold text-[var(--brand-primary)] shadow-sm transition hover:bg-[var(--brand-primary-soft)]"
                    >
                        Open score entry
                    </Link>
                </div>
            </div>

            <div className="mt-4 grid gap-4 md:grid-cols-4">
                <SummaryCard label="Class arms" value={summary.class_arm_count} icon={Layers3} />
                <SummaryCard label="Students" value={summary.student_count} icon={UsersRound} />
                <SummaryCard label="Subject records" value={summary.subject_count} icon={ClipboardList} />
                <SummaryCard label="Issues" value={summary.issue_count} icon={AlertCircle} />
            </div>

            {issues.length > 0 && (
                <section className="mt-4 rounded-xl border border-red-200 bg-white p-4 shadow-sm">
                    <h2 className="text-base font-bold text-slate-950">Issue pointers</h2>
                    <div className="mt-3 grid gap-2">
                        {issues.slice(0, 12).map((issue, index) => (
                            <div key={`${issue.class_arm}-${issue.admission_no}-${issue.subject}-${index}`} className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">
                                <span className="font-bold">{issue.class_arm}</span>
                                <span> • {issue.student} ({issue.admission_no}) • {issue.subject}: {issue.issue}</span>
                            </div>
                        ))}
                    </div>
                    {issues.length > 12 && (
                        <p className="mt-3 text-sm font-semibold text-red-700">
                            Showing 12 of {issues.length} issues. The full class-arm breakdown is below.
                        </p>
                    )}
                </section>
            )}

            <section className="mt-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <label htmlFor="student-search" className="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Search student
                </label>
                <div className="mt-2 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div className="relative w-full md:max-w-xl">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" strokeWidth={2.25} />
                        <input
                            id="student-search"
                            type="search"
                            value={studentSearch}
                            onChange={(event) => setStudentSearch(event.target.value)}
                            placeholder="Search by student name or admission number"
                            className="block w-full rounded-lg border-slate-300 pl-9 pr-10 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                        />
                        {studentSearch && (
                            <button type="button" onClick={() => setStudentSearch('')} className="absolute right-2 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Clear student search">
                                <X className="h-4 w-4" aria-hidden="true" />
                            </button>
                        )}
                    </div>
                    <div className="text-sm font-semibold text-slate-500">
                        Showing {visibleStudentCount} of {summary.student_count} students
                    </div>
                </div>
            </section>

            <div className="mt-4 space-y-4">
                {filteredArms.map((arm) => (
                    <section key={arm.id} className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-black text-slate-950">{arm.name}</h2>
                                <p className="text-sm text-slate-500">{arm.students.length} visible student(s)</p>
                            </div>
                            <StatusBadge ready={arm.ready} />
                        </div>

                        <div className="divide-y divide-slate-100">
                            {arm.students.map((student) => {
                                const isCollapsed = collapsedStudents.includes(student.id);

                                return (
                                    <div key={student.id} className="p-4">
                                        <div className="flex w-full flex-col gap-2 text-left sm:flex-row sm:items-center sm:justify-between">
                                        <button type="button" onClick={() => toggleStudent(student.id)} className="flex items-center gap-3 text-left">
                                            <span className="grid h-9 w-9 place-items-center rounded-lg bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                                                <GraduationCap className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />
                                            </span>
                                            <div>
                                                <div className="font-bold text-slate-950">{student.name}</div>
                                                <div className="text-sm text-slate-500">{student.admission_no}</div>
                                            </div>
                                        </button>
                                            <span className="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={(event) => {
                                                        event.stopPropagation();
                                                        setSelectedStudent({ ...student, classArm: arm.name });
                                                    }}
                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-2.5 py-1.5 text-xs font-bold text-[var(--brand-primary)] hover:brightness-95"
                                                >
                                                    <Eye className="h-3.5 w-3.5" aria-hidden="true" />
                                                    View details
                                                </button>
                                                <StatusBadge ready={student.ready} />
                                                <ChevronDown
                                                    onClick={() => toggleStudent(student.id)}
                                                    className={[
                                                        'h-4 w-4 cursor-pointer text-slate-400 transition',
                                                        isCollapsed ? '-rotate-90' : 'rotate-0',
                                                    ].join(' ')}
                                                    aria-hidden="true"
                                                    strokeWidth={2.25}
                                                />
                                            </span>
                                        </div>

                                        {!isCollapsed && (
                                            <div className="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                                {student.subjects.map((subject) => (
                                                    <div
                                                        key={subject.id}
                                                        className={[
                                                            'rounded-lg border px-3 py-3',
                                                            subject.ready ? 'border-emerald-100 bg-emerald-50/60' : 'border-red-100 bg-red-50',
                                                        ].join(' ')}
                                                    >
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div>
                                                                <div className="font-bold text-slate-950">{subject.name}</div>
                                                                <div className="mt-0.5 text-xs font-semibold text-slate-500">
                                                                    {subject.assessment_flow || 'No assessment flow'}
                                                                </div>
                                                            </div>
                                                            <StatusBadge ready={subject.ready} />
                                                        </div>
                                                        {!subject.ready && (
                                                            <p className="mt-2 text-sm font-semibold text-red-700">{subject.issue}</p>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}

                            {arm.students.length === 0 && (
                                <div className="p-4 text-sm font-medium text-slate-500">
                                    No active students are currently enrolled in this class arm.
                                </div>
                            )}
                        </div>
                    </section>
                ))}

                {filteredArms.length === 0 && (
                    <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-sm font-semibold text-slate-500">
                        {studentSearch ? 'No students match your search.' : 'No class-arm subject records are available yet.'}
                    </div>
                )}
            </div>

            <div className={[
                'mt-4 rounded-xl border px-4 py-4 text-sm font-bold shadow-sm',
                summary.ready ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800',
            ].join(' ')}>
                {summary.ready
                    ? 'Final status: green. Every student report card is ready.'
                    : `Final status: red. ${summary.issue_count} issue(s) must be fixed before the school report cards are ready.`}
            </div>

            <Modal show={selectedStudent !== null} closeable={false} maxWidth="2xl">
                {selectedStudent && (
                    <div>
                        <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                            <div>
                                <h2 className="text-lg font-black text-slate-950">{selectedStudent.name}</h2>
                                <p className="mt-1 text-sm text-slate-500">{selectedStudent.admission_no} • {selectedStudent.classArm}</p>
                            </div>
                            <button type="button" onClick={() => setSelectedStudent(null)} className="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" title="Close student details">
                                <X className="h-4 w-4" aria-hidden="true" />
                            </button>
                        </div>

                        <div className="max-h-[72vh] space-y-5 overflow-y-auto p-5">
                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="rounded-lg bg-slate-50 p-3"><div className="text-xs font-bold uppercase text-slate-500">Gender</div><div className="mt-1 font-bold capitalize text-slate-900">{selectedStudent.gender || 'Not set'}</div></div>
                                <div className="rounded-lg bg-slate-50 p-3"><div className="text-xs font-bold uppercase text-slate-500">Date of birth</div><div className="mt-1 font-bold text-slate-900">{selectedStudent.date_of_birth || 'Not set'}</div></div>
                                <div className="rounded-lg bg-slate-50 p-3"><div className="text-xs font-bold uppercase text-slate-500">Report status</div><div className="mt-1"><StatusBadge ready={selectedStudent.ready} /></div></div>
                            </div>

                            <section>
                                <h3 className="text-sm font-black text-slate-950">Guardian details</h3>
                                <div className="mt-2 grid gap-2 sm:grid-cols-2">
                                    {selectedStudent.guardians.map((guardian) => (
                                        <div key={guardian.id} className="rounded-lg border border-slate-200 p-3 text-sm">
                                            <div className="font-bold text-slate-950">{guardian.name}</div>
                                            <div className="capitalize text-slate-500">{guardian.relationship}</div>
                                            <div className="mt-2 text-slate-700">{guardian.email || 'No email'}</div>
                                            <div className="text-slate-700">{guardian.phone || 'No phone'}</div>
                                        </div>
                                    ))}
                                    {selectedStudent.guardians.length === 0 && <p className="text-sm text-slate-500">No guardian is linked to this student.</p>}
                                </div>
                            </section>

                            <section>
                                <h3 className="text-sm font-black text-slate-950">Subject readiness</h3>
                                <div className="mt-2 overflow-x-auto rounded-lg border border-slate-200">
                                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead className="bg-slate-50 text-left text-xs font-bold uppercase text-slate-500"><tr><th className="px-3 py-2.5">Subject</th><th className="px-3 py-2.5">Term</th><th className="px-3 py-2.5">Score</th><th className="px-3 py-2.5">Status</th></tr></thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {selectedStudent.subjects.map((subject) => (
                                                <tr key={subject.id}>
                                                    <td className="px-3 py-3 font-bold text-slate-900">{subject.name}</td>
                                                    <td className="px-3 py-3 text-slate-600">{subject.term || 'Not set'}</td>
                                                    <td className="px-3 py-3 font-semibold text-slate-700">{subject.total_score ? `${subject.score}/${subject.total_score}` : 'No flow'}</td>
                                                    <td className="px-3 py-3"><StatusBadge ready={subject.ready} />{subject.issue && <div className="mt-1 text-xs font-semibold text-red-700">{subject.issue}</div>}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    </div>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
