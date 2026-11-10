import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import DataTable from '@/Components/DataTable';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';

function SelectInput({ id, value, onChange, children }) {
    return (
        <select
            id={id}
            value={value}
            onChange={onChange}
            className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
        >
            {children}
        </select>
    );
}

export default function Index({ students, classArms, sessions }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.student_setup === 'readwrite';
    const [showCreateModal, setShowCreateModal] = useState(false);
    const form = useForm({
        admission_no: '',
        first_name: '',
        last_name: '',
        gender: '',
        date_of_birth: '',
        class_arm_id: '',
        academic_session_id: '',
        term_id: '',
        parent_first_name: '',
        parent_last_name: '',
        parent_email: '',
        parent_phone: '',
        parent_relationship: '',
    });
    const importForm = useForm({ student_file: null });
    const activeSession = sessions.find((session) => String(session.id) === String(form.data.academic_session_id));

    const submit = (event) => {
        event.preventDefault();
        form.post(route('school.students.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowCreateModal(false);
            },
        });
    };

    const importStudents = (event) => {
        event.preventDefault();
        importForm.post(route('school.students.import'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => importForm.reset(),
        });
    };

    return (
        <AuthenticatedLayout title="Student setup" subtitle="Register students and connect them to class arms and guardians.">
            <Head title="Student setup" />

            <div className="space-y-5">
                {canWrite && (
                <section className="overflow-hidden rounded-xl border border-[var(--brand-primary-border)] bg-white shadow-sm ring-1 ring-[var(--brand-primary-border)]">
                    <div className="border-b border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-4 py-3">
                        <h2 className="text-sm font-bold text-slate-950">Import students by CSV</h2>
                        <p className="mt-1 text-sm text-slate-600">
                            Headers: admission_no, first_name, last_name, gender, date_of_birth, class_arm_id, academic_session_id, term_id, parent_first_name, parent_last_name, parent_email, parent_phone, parent_relationship.
                        </p>
                    </div>
                    <div className="grid gap-4 lg:grid-cols-[1fr_360px] lg:items-end">
                        <div className="p-4 text-sm text-slate-600">
                            Use the class arm, session, and term IDs from academic setup records. Parent fields can be left blank when guardian details are not ready yet.
                        </div>
                        <form onSubmit={importStudents} className="grid gap-3 p-4 sm:grid-cols-[1fr_auto] sm:items-end">
                            <div>
                                <InputLabel htmlFor="student_file" value="CSV file" />
                                <input
                                    id="student_file"
                                    type="file"
                                    accept=".csv,text/csv"
                                    className="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700"
                                    onChange={(event) => importForm.setData('student_file', event.target.files[0])}
                                />
                                <InputError message={importForm.errors.student_file} className="mt-1" />
                            </div>
                            <PrimaryButton disabled={importForm.processing}>Import</PrimaryButton>
                        </form>
                    </div>
                </section>
                )}

                {canWrite && (
                <div>
                    <button
                        type="button"
                        onClick={() => setShowCreateModal(true)}
                        className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95"
                    >
                        <Plus className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                        Add student
                    </button>
                </div>
                )}

                <Modal show={showCreateModal} closeable={false} maxWidth="2xl">
                    <form onSubmit={submit}>
                        <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                            <div>
                                <h2 className="text-base font-bold text-slate-950">Add student</h2>
                                <p className="mt-1 text-sm text-slate-500">Register a student and optional guardian details.</p>
                            </div>
                            <button type="button" onClick={() => setShowCreateModal(false)} className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-900" aria-label="Close">
                                <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                            </button>
                        </div>
                        <div className="grid max-h-[75vh] gap-5 overflow-y-auto p-5">
                        <div>
                            <h2 className="text-sm font-bold text-slate-950">Student details</h2>
                            <div className="mt-3 grid gap-4 md:grid-cols-3">
                                <div>
                                    <InputLabel htmlFor="admission_no" value="Admission no" />
                                    <TextInput id="admission_no" value={form.data.admission_no} className="mt-1 block w-full" onChange={(event) => form.setData('admission_no', event.target.value)} />
                                    <InputError message={form.errors.admission_no} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="first_name" value="First name" />
                                    <TextInput id="first_name" value={form.data.first_name} className="mt-1 block w-full" onChange={(event) => form.setData('first_name', event.target.value)} />
                                    <InputError message={form.errors.first_name} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="last_name" value="Last name" />
                                    <TextInput id="last_name" value={form.data.last_name} className="mt-1 block w-full" onChange={(event) => form.setData('last_name', event.target.value)} />
                                    <InputError message={form.errors.last_name} className="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-4">
                            <div>
                                <InputLabel htmlFor="gender" value="Gender" />
                                <SelectInput id="gender" value={form.data.gender} onChange={(event) => form.setData('gender', event.target.value)}>
                                    <option value="">Select</option>
                                    <option value="female">Female</option>
                                    <option value="male">Male</option>
                                </SelectInput>
                            </div>
                            <div>
                                <InputLabel htmlFor="date_of_birth" value="Date of birth" />
                                <TextInput id="date_of_birth" type="date" value={form.data.date_of_birth} className="mt-1 block w-full" onChange={(event) => form.setData('date_of_birth', event.target.value)} />
                            </div>
                            <div>
                                <InputLabel htmlFor="class_arm_id" value="Class arm" />
                                <SelectInput id="class_arm_id" value={form.data.class_arm_id} onChange={(event) => form.setData('class_arm_id', event.target.value)}>
                                    <option value="">Select class arm</option>
                                    {classArms.map((arm) => (
                                        <option key={arm.id} value={arm.id}>{arm.name}</option>
                                    ))}
                                </SelectInput>
                                <InputError message={form.errors.class_arm_id} className="mt-1" />
                            </div>
                            <div>
                                <InputLabel htmlFor="academic_session_id" value="Session" />
                                <SelectInput
                                    id="academic_session_id"
                                    value={form.data.academic_session_id}
                                    onChange={(event) => {
                                        form.setData('academic_session_id', event.target.value);
                                        form.setData('term_id', '');
                                    }}
                                >
                                    <option value="">Select session</option>
                                    {sessions.map((session) => (
                                        <option key={session.id} value={session.id}>{session.name}</option>
                                    ))}
                                </SelectInput>
                                <InputError message={form.errors.academic_session_id} className="mt-1" />
                            </div>
                        </div>

                        <div className="max-w-sm">
                            <InputLabel htmlFor="term_id" value="Term" />
                            <SelectInput id="term_id" value={form.data.term_id} onChange={(event) => form.setData('term_id', event.target.value)}>
                                <option value="">No term selected</option>
                                {(activeSession?.terms ?? []).map((term) => (
                                    <option key={term.id} value={term.id}>{term.name}</option>
                                ))}
                            </SelectInput>
                        </div>

                        <div className="border-t border-slate-200 pt-4">
                            <h2 className="text-sm font-bold text-slate-950">Parent or guardian</h2>
                            <div className="mt-3 grid gap-4 md:grid-cols-3">
                                <div>
                                    <InputLabel htmlFor="parent_first_name" value="First name" />
                                    <TextInput id="parent_first_name" value={form.data.parent_first_name} className="mt-1 block w-full" onChange={(event) => form.setData('parent_first_name', event.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="parent_last_name" value="Last name" />
                                    <TextInput id="parent_last_name" value={form.data.parent_last_name} className="mt-1 block w-full" onChange={(event) => form.setData('parent_last_name', event.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="parent_relationship" value="Relationship" />
                                    <TextInput id="parent_relationship" value={form.data.parent_relationship} className="mt-1 block w-full" onChange={(event) => form.setData('parent_relationship', event.target.value)} placeholder="Mother, father, guardian" />
                                </div>
                            </div>
                            <div className="mt-3 grid gap-4 md:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="parent_email" value="Email" />
                                    <TextInput id="parent_email" type="email" value={form.data.parent_email} className="mt-1 block w-full" onChange={(event) => form.setData('parent_email', event.target.value)} />
                                    <InputError message={form.errors.parent_email} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="parent_phone" value="Phone" />
                                    <TextInput id="parent_phone" value={form.data.parent_phone} className="mt-1 block w-full" onChange={(event) => form.setData('parent_phone', event.target.value)} />
                                </div>
                            </div>
                        </div>

                        </div>
                        <div className="flex justify-end border-t border-slate-200 px-5 py-4">
                            <PrimaryButton disabled={form.processing}>Save student</PrimaryButton>
                        </div>
                    </form>
                </Modal>

                <DataTable
                    columns={[
                        {
                            key: 'student',
                            label: 'Student',
                            className: 'text-slate-950',
                            render: (student) => (
                                <>
                                    <div className="font-semibold">{student.first_name} {student.last_name}</div>
                                    <div className="text-xs text-slate-500">{student.admission_no}</div>
                                </>
                            ),
                            sortValue: (student) => `${student.first_name} ${student.last_name}`,
                            searchValue: (student) => `${student.first_name} ${student.last_name} ${student.admission_no}`,
                        },
                        { key: 'class_arm', label: 'Class arm', className: 'text-slate-600', render: (student) => student.enrollments?.[0]?.class_arm?.name ?? 'Not placed', sortValue: (student) => student.enrollments?.[0]?.class_arm?.name ?? '' },
                        { key: 'guardian', label: 'Guardian', className: 'text-slate-600', render: (student) => student.parents?.[0] ? `${student.parents[0].first_name} ${student.parents[0].last_name}` : 'No guardian', sortValue: (student) => student.parents?.[0] ? `${student.parents[0].first_name} ${student.parents[0].last_name}` : '' },
                        { key: 'status', label: 'Status', className: 'capitalize text-slate-600' },
                    ]}
                    rows={students}
                    emptyLabel="No students yet."
                    searchPlaceholder="Search students"
                />
            </div>
        </AuthenticatedLayout>
    );
}
