import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, GraduationCap } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

export default function Rollover({ sessions, classArms, sourceSessionId, enrollments }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.student_setup === 'readwrite';
    const [sourceId, setSourceId] = useState(sourceSessionId ? String(sourceSessionId) : '');
    const [targetId, setTargetId] = useState('');
    const targetSession = sessions.find((session) => String(session.id) === String(targetId));
    const form = useForm({ source_session_id: sourceSessionId ?? '', target_session_id: '', target_term_id: '', students: [] });
    const groupedEnrollments = useMemo(() => Object.values(enrollments.reduce((groups, enrollment) => {
        const key = enrollment.class_arm_id;
        groups[key] ??= { arm: enrollment.class_arm, enrollments: [] };
        groups[key].enrollments.push(enrollment);
        return groups;
    }, {})), [enrollments]);

    useEffect(() => {
        form.setData('students', enrollments.map((enrollment) => ({
            student_profile_id: enrollment.student_profile_id,
            decision: 'promoted',
            target_class_arm_id: '',
        })));
    }, [enrollments]);

    const loadStudents = () => {
        router.get(route('school.students.rollover.index'), { source_session_id: sourceId }, { preserveState: false });
    };

    const updateStudent = (studentId, key, value) => {
        form.setData('students', form.data.students.map((student) => (
            Number(student.student_profile_id) === Number(studentId) ? { ...student, [key]: value } : student
        )));
    };

    const mapArm = (enrollmentGroup, targetArmId) => {
        const studentIds = enrollmentGroup.enrollments.map((enrollment) => Number(enrollment.student_profile_id));
        form.setData('students', form.data.students.map((student) => (
            studentIds.includes(Number(student.student_profile_id))
                ? { ...student, decision: 'promoted', target_class_arm_id: targetArmId }
                : student
        )));
    };

    const submit = (event) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, source_session_id: sourceId, target_session_id: targetId }))
            .post(route('school.students.rollover.store'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout title="Student rollover" subtitle="Move students into a new session while handling promotions, repeaters, graduates, and withdrawals.">
            <Head title="Student rollover" />

            <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div className="grid gap-4 md:grid-cols-[1fr_auto_1fr_auto] md:items-end">
                    <div><InputLabel value="Source session" /><SearchableSelect value={sourceId} options={sessions} onChange={(id) => setSourceId(String(id))} placeholder="Select source session" searchPlaceholder="Search sessions" /></div>
                    <ArrowRight className="mb-3 hidden h-5 w-5 text-slate-400 md:block" aria-hidden="true" />
                    <div><InputLabel value="Destination session" /><SearchableSelect value={targetId} options={sessions.filter((session) => String(session.id) !== sourceId)} onChange={(id) => { setTargetId(String(id)); form.setData('target_term_id', ''); }} placeholder="Select destination session" searchPlaceholder="Search sessions" /></div>
                    <button type="button" onClick={loadStudents} disabled={!sourceId} className="rounded-md border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 disabled:opacity-50">Load students</button>
                </div>
            </section>

            {enrollments.length > 0 && (
                <form onSubmit={submit} className="space-y-4">
                    <div className="max-w-sm rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <InputLabel value="Destination term" />
                        <SearchableSelect value={form.data.target_term_id} options={targetSession?.terms ?? []} onChange={(id) => form.setData('target_term_id', id)} placeholder="No specific term" searchPlaceholder="Search terms" />
                        <InputError message={form.errors.target_session_id || form.errors.target_term_id || form.errors.students} className="mt-2" />
                    </div>

                    {groupedEnrollments.map((group) => (
                        <section key={group.arm.id} className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div className="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div><h2 className="font-bold text-slate-950">{group.arm.school_class?.class_group?.name} • {group.arm.name}</h2><p className="text-xs text-slate-500">{group.enrollments.length} students</p></div>
                                <div className="w-full sm:w-72"><SearchableSelect value="" options={classArms} getOptionLabel={(arm) => `${arm.school_class?.class_group?.name} • ${arm.name}`} onChange={(id) => mapArm(group, id)} placeholder="Map all to an arm" searchPlaceholder="Search destination arms" /></div>
                            </div>
                            <div className="divide-y divide-slate-100">
                                {group.enrollments.map((enrollment) => {
                                    const row = form.data.students.find((student) => Number(student.student_profile_id) === Number(enrollment.student_profile_id));
                                    return <div key={enrollment.student_profile_id} className="grid gap-3 px-4 py-3 md:grid-cols-[1fr_180px_280px] md:items-center">
                                        <div><div className="font-semibold text-slate-950">{enrollment.student_profile.first_name} {enrollment.student_profile.last_name}</div><div className="text-xs text-slate-500">{enrollment.student_profile.admission_no}</div></div>
                                        <select value={row?.decision ?? 'promoted'} onChange={(event) => updateStudent(enrollment.student_profile_id, 'decision', event.target.value)} className="rounded-md border-slate-300 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"><option value="promoted">Promote</option><option value="repeated">Repeat</option><option value="graduated">Graduate</option><option value="withdrawn">Withdrawn/left</option></select>
                                        {['promoted', 'repeated'].includes(row?.decision) ? <SearchableSelect value={row?.target_class_arm_id ?? ''} options={classArms} getOptionLabel={(arm) => `${arm.school_class?.class_group?.name} • ${arm.name}`} onChange={(id) => updateStudent(enrollment.student_profile_id, 'target_class_arm_id', id)} placeholder="Select destination arm" searchPlaceholder="Search class arms" /> : <span className="text-sm text-slate-400">No new enrolment</span>}
                                    </div>;
                                })}
                            </div>
                        </section>
                    ))}

                    {canWrite && <div className="flex justify-end"><PrimaryButton disabled={form.processing || !targetId}><GraduationCap className="mr-2 h-4 w-4" aria-hidden="true" />Apply rollover decisions</PrimaryButton></div>}
                </form>
            )}
        </AuthenticatedLayout>
    );
}
