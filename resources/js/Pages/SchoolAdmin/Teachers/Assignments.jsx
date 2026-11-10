import DataTable from '@/Components/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, UserMinus, X } from 'lucide-react';
import { useState } from 'react';

function SelectInput({ id, value, onChange, children }) {
    return (
        <select id={id} value={value} onChange={onChange} className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
            {children}
        </select>
    );
}

function teacherName(teacher) {
    return `${teacher.staff_profile?.first_name ?? ''} ${teacher.staff_profile?.last_name ?? ''}`.trim() || teacher.staff_no || 'Teacher';
}

export default function Assignments({ teachers, classArms, sessions, schoolSubjects, classTermSubjects, classArmAssignments }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.teacher_assignments === 'readwrite';
    const [unassignConfirmation, setUnassignConfirmation] = useState(null);
    const currentSession = sessions.find((session) => session.is_current) ?? sessions[0];
    const currentTerm = currentSession?.terms?.find((term) => term.is_current) ?? currentSession?.terms?.[0];
    const armForm = useForm({
        teacher_profile_id: teachers[0]?.id ?? '',
        academic_session_id: currentSession?.id ?? '',
        term_id: currentTerm?.id ?? '',
        class_arm_ids: [],
        is_class_teacher: false,
    });
    const subjectForm = useForm({
        teacher_profile_id: teachers[0]?.id ?? '',
        academic_session_id: currentSession?.id ?? '',
        term_id: currentTerm?.id ?? '',
        class_arm_id: classArms[0]?.id ?? '',
        school_subject_ids: [],
        assign_all_subjects: false,
    });

    const armSession = sessions.find((session) => String(session.id) === String(armForm.data.academic_session_id));
    const subjectSession = sessions.find((session) => String(session.id) === String(subjectForm.data.academic_session_id));

    const toggle = (form, key, id) => {
        const ids = form.data[key].map(String);
        form.setData(key, ids.includes(String(id)) ? form.data[key].filter((item) => String(item) !== String(id)) : [...form.data[key], id]);
    };

    const submitArms = (event) => {
        event.preventDefault();
        armForm.post(route('school.teachers.class-arms.store'), { preserveScroll: true });
    };

    const submitSubjects = (event) => {
        event.preventDefault();
        subjectForm.post(route('school.teachers.subjects.store'), { preserveScroll: true });
    };

    const confirmUnassign = () => {
        if (!unassignConfirmation) {
            return;
        }

        router.delete(route(unassignConfirmation.routeName, unassignConfirmation.id), {
            preserveScroll: true,
            onSuccess: () => setUnassignConfirmation(null),
        });
    };

    return (
        <AuthenticatedLayout title="Teacher assignments" subtitle="Assign teachers to class arms and subject teaching responsibilities.">
            <Head title="Teacher assignments" />

            <Modal show={Boolean(unassignConfirmation)} closeable={false} maxWidth="md">
                <div className="p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex gap-3">
                            <span className="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-red-50 text-red-700"><AlertTriangle className="h-5 w-5" aria-hidden="true" /></span>
                            <div><h2 className="text-base font-bold text-slate-950">{unassignConfirmation?.title}</h2><p className="mt-1 text-sm leading-6 text-slate-600">{unassignConfirmation?.description}</p></div>
                        </div>
                        <button type="button" onClick={() => setUnassignConfirmation(null)} className="grid h-8 w-8 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close"><X className="h-4 w-4" aria-hidden="true" /></button>
                    </div>
                    <div className="mt-5 flex justify-end gap-2">
                        <SecondaryButton type="button" onClick={() => setUnassignConfirmation(null)} className="normal-case tracking-normal">Cancel</SecondaryButton>
                        <button type="button" onClick={confirmUnassign} className="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-bold text-white hover:bg-red-700"><UserMinus className="h-4 w-4" aria-hidden="true" />Unassign</button>
                    </div>
                </div>
            </Modal>

            {canWrite && (
            <div className="grid gap-4 xl:grid-cols-2">
                <form onSubmit={submitArms} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 className="text-sm font-bold text-slate-950">Assign class arms</h2>
                    <div className="mt-3 grid gap-3 md:grid-cols-3">
                        <div>
                            <InputLabel htmlFor="arm_teacher" value="Teacher" />
                            <SelectInput id="arm_teacher" value={armForm.data.teacher_profile_id} onChange={(event) => armForm.setData('teacher_profile_id', event.target.value)}>
                                {teachers.map((teacher) => <option key={teacher.id} value={teacher.id}>{teacherName(teacher)}</option>)}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="arm_session" value="Session" />
                            <SelectInput id="arm_session" value={armForm.data.academic_session_id} onChange={(event) => armForm.setData('academic_session_id', event.target.value)}>
                                {sessions.map((session) => <option key={session.id} value={session.id}>{session.name}</option>)}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="arm_term" value="Term" />
                            <SelectInput id="arm_term" value={armForm.data.term_id} onChange={(event) => armForm.setData('term_id', event.target.value)}>
                                <option value="">All terms</option>
                                {(armSession?.terms ?? []).map((term) => <option key={term.id} value={term.id}>{term.name}</option>)}
                            </SelectInput>
                        </div>
                    </div>
                    <div className="mt-3 grid max-h-52 gap-2 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2">
                        {classArms.map((arm) => (
                            <label key={arm.id} className="flex items-center gap-2 rounded-md bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" checked={armForm.data.class_arm_ids.map(String).includes(String(arm.id))} onChange={() => toggle(armForm, 'class_arm_ids', arm.id)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                                {arm.name}
                            </label>
                        ))}
                    </div>
                    <InputError message={armForm.errors.class_arm_ids} className="mt-1" />
                    <label className="mt-3 flex items-center gap-2 rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" checked={armForm.data.is_class_teacher} onChange={(event) => armForm.setData('is_class_teacher', event.target.checked)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        Set as class teacher and report-card signatory for the selected arms
                    </label>
                    <div className="mt-3 flex justify-end"><PrimaryButton disabled={armForm.processing}>Save arms</PrimaryButton></div>
                </form>

                <form onSubmit={submitSubjects} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 className="text-sm font-bold text-slate-950">Assign subjects</h2>
                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="subject_teacher" value="Teacher" />
                            <SelectInput id="subject_teacher" value={subjectForm.data.teacher_profile_id} onChange={(event) => subjectForm.setData('teacher_profile_id', event.target.value)}>
                                {teachers.map((teacher) => <option key={teacher.id} value={teacher.id}>{teacherName(teacher)}</option>)}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="subject_arm" value="Class arm" />
                            <SelectInput id="subject_arm" value={subjectForm.data.class_arm_id} onChange={(event) => subjectForm.setData('class_arm_id', event.target.value)}>
                                {classArms.map((arm) => <option key={arm.id} value={arm.id}>{arm.name}</option>)}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="subject_session" value="Session" />
                            <SelectInput id="subject_session" value={subjectForm.data.academic_session_id} onChange={(event) => subjectForm.setData('academic_session_id', event.target.value)}>
                                {sessions.map((session) => <option key={session.id} value={session.id}>{session.name}</option>)}
                            </SelectInput>
                        </div>
                        <div>
                            <InputLabel htmlFor="subject_term" value="Term" />
                            <SelectInput id="subject_term" value={subjectForm.data.term_id} onChange={(event) => subjectForm.setData('term_id', event.target.value)}>
                                {(subjectSession?.terms ?? []).map((term) => <option key={term.id} value={term.id}>{term.name}</option>)}
                            </SelectInput>
                        </div>
                    </div>
                    <label className="mt-3 flex items-center gap-2 rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" checked={subjectForm.data.assign_all_subjects} onChange={(event) => subjectForm.setData('assign_all_subjects', event.target.checked)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        Assign all active subjects of this arm to this teacher
                    </label>
                    {!subjectForm.data.assign_all_subjects && (
                        <div className="mt-3 grid max-h-48 gap-2 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2">
                            {schoolSubjects.map((schoolSubject) => (
                                <label key={schoolSubject.id} className="flex items-center gap-2 rounded-md bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700">
                                    <input type="checkbox" checked={subjectForm.data.school_subject_ids.map(String).includes(String(schoolSubject.id))} onChange={() => toggle(subjectForm, 'school_subject_ids', schoolSubject.id)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                                    {schoolSubject.custom_name || schoolSubject.subject?.name}
                                </label>
                            ))}
                        </div>
                    )}
                    <div className="mt-3 flex justify-end"><PrimaryButton disabled={subjectForm.processing}>Save subjects</PrimaryButton></div>
                </form>
            </div>
            )}

            <div className="mt-4 grid gap-4 xl:grid-cols-2">
                <DataTable
                    columns={[
                        { key: 'teacher', label: 'Teacher', render: (item) => teacherName(item.teacher_profile), sortValue: (item) => teacherName(item.teacher_profile) },
                        { key: 'class_arm', label: 'Class arm', render: (item) => item.class_arm?.name },
                        { key: 'term', label: 'Term', render: (item) => item.term?.name ?? 'All terms' },
                        ...(canWrite ? [{
                            key: 'actions', label: 'Actions', sortable: false, headerClassName: 'text-right', className: 'text-right', render: (item) => (
                                <button type="button" onClick={() => setUnassignConfirmation({ routeName: 'school.teachers.class-arms.destroy', id: item.id, title: 'Unassign class arm?', description: `${teacherName(item.teacher_profile)} will no longer be assigned to ${item.class_arm?.name ?? 'this class arm'} for the selected session and term.` })} className="grid h-8 w-8 place-items-center rounded-md border border-red-200 bg-red-50 text-red-700 hover:bg-red-100" title="Unassign class arm"><UserMinus className="h-4 w-4" aria-hidden="true" /></button>
                            ),
                        }] : []),
                    ]}
                    rows={classArmAssignments}
                    emptyLabel="No class arm assignments yet."
                    searchPlaceholder="Search class arm assignments"
                />
                <DataTable
                    columns={[
                        { key: 'subject', label: 'Subject', render: (item) => item.school_subject?.custom_name || item.school_subject?.subject?.name },
                        { key: 'class_arm', label: 'Class arm', render: (item) => item.class_arm?.name },
                        { key: 'teachers', label: 'Teachers', searchValue: (item) => item.teacher_assignments?.map((assignment) => teacherName(assignment.teacher_profile)).join(' '), render: (item) => item.teacher_assignments?.length ? (
                            <div className="flex flex-wrap gap-2">
                                {item.teacher_assignments.map((assignment) => (
                                    <span key={assignment.id} className="inline-flex items-center gap-1.5 rounded-full bg-[var(--brand-primary-soft)] px-2.5 py-1 text-xs font-bold text-[var(--brand-primary)] ring-1 ring-[var(--brand-primary-border)]">
                                        {teacherName(assignment.teacher_profile)}
                                        {canWrite && <button type="button" onClick={() => setUnassignConfirmation({ routeName: 'school.teachers.subjects.destroy', id: assignment.id, title: 'Unassign subject?', description: `${teacherName(assignment.teacher_profile)} will no longer teach ${item.school_subject?.custom_name || item.school_subject?.subject?.name || 'this subject'} in ${item.class_arm?.name ?? 'this class arm'}.` })} className="grid h-5 w-5 place-items-center rounded-full hover:bg-white/80 hover:text-red-700" title="Unassign subject"><X className="h-3 w-3" aria-hidden="true" /></button>}
                                    </span>
                                ))}
                            </div>
                        ) : 'Unassigned' },
                    ]}
                    rows={classTermSubjects}
                    emptyLabel="No subject assignments yet."
                    searchPlaceholder="Search subject assignments"
                />
            </div>
        </AuthenticatedLayout>
    );
}
