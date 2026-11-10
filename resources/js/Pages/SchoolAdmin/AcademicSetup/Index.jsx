import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import DataTable from '@/Components/DataTable';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableMultiSelect from '@/Components/SearchableMultiSelect';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { Check, Clock3, Copy, LockKeyhole, Pencil, Plus, Trash2, UnlockKeyhole, X } from 'lucide-react';
import { useState } from 'react';

function Panel({ title, description, children }) {
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
            <div className="border-b border-slate-200 bg-slate-50/70 px-4 py-3">
                <h2 className="text-sm font-bold text-slate-950">{title}</h2>
                {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
            </div>
            <div className="p-4">{children}</div>
        </section>
    );
}

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

function TemplateAction({ title, description, buttonLabel, processing, onSubmit }) {
    return (
        <form onSubmit={onSubmit} className="mb-4 rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] p-3">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div className="text-sm font-bold text-slate-950">{title}</div>
                    <div className="mt-1 text-sm text-slate-600">{description}</div>
                </div>
                <PrimaryButton disabled={processing}>{buttonLabel}</PrimaryButton>
            </div>
        </form>
    );
}

function AddButton({ children, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-[var(--brand-primary)] focus:ring-offset-2"
        >
            <Plus className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
            {children}
        </button>
    );
}

function RowActions({ onEdit, onDelete }) {
    return (
        <div className="inline-flex items-center gap-2">
            <button
                type="button"
                onClick={onEdit}
                className="grid h-8 w-8 place-items-center rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] text-[var(--brand-primary)] transition hover:brightness-95"
                aria-label="Edit"
            >
                <Pencil className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
            </button>
            <button
                type="button"
                onClick={onDelete}
                className="grid h-8 w-8 place-items-center rounded-md border border-red-100 bg-red-50 text-red-600 transition hover:bg-red-100"
                aria-label="Delete"
            >
                <Trash2 className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
            </button>
        </div>
    );
}

function AddAnotherCheckbox({ checked, onChange }) {
    return (
        <label className="inline-flex cursor-pointer items-center gap-3 text-sm font-semibold text-slate-700">
            <span
                className={[
                    'grid h-5 w-5 place-items-center rounded-md border transition',
                    checked ? 'border-[var(--brand-primary)] bg-[var(--brand-primary)] text-white' : 'border-slate-300 bg-white text-transparent',
                ].join(' ')}
            >
                <Check className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={3} />
            </span>
            <input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} className="sr-only" />
            Add another after saving
        </label>
    );
}

function CreationModal({ show, title, description, processing, submitDisabled = false, addAnother, onAddAnotherChange, onClose, onSubmit, children }) {
    return (
        <Modal show={show} closeable={false} maxWidth="xl">
            <form onSubmit={onSubmit}>
                <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 className="text-base font-bold text-slate-950">{title}</h2>
                        {description && <p className="mt-1 text-sm text-slate-500">{description}</p>}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 transition hover:text-slate-900"
                        aria-label="Close"
                    >
                        <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                    </button>
                </div>

                <div className="grid gap-4 p-5">{children}</div>

                <div className="flex flex-col gap-3 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <AddAnotherCheckbox checked={addAnother} onChange={onAddAnotherChange} />
                    <PrimaryButton disabled={processing || submitDisabled}>Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}

export default function Index({ classGroups, classes, classArms, sessions, schoolSubjects, subjects }) {
    const [activeStep, setActiveStep] = useState('class-groups');
    const [creationModal, setCreationModal] = useState(null);
    const [editModal, setEditModal] = useState(null);
    const [deleteModal, setDeleteModal] = useState(null);
    const [duplicateSession, setDuplicateSession] = useState(null);
    const [scoreLockSession, setScoreLockSession] = useState(null);
    const [addAnother, setAddAnother] = useState(false);
    const [editData, setEditData] = useState({});
    const classGroupForm = useForm({ name: '', sort_order: 0 });
    const classForm = useForm({ class_group_id: '', name: '', sort_order: 0 });
    const classArmForm = useForm({ school_class_id: '', name: '', label: '' });
    const sessionForm = useForm({ name: '', starts_on: '', ends_on: '', is_current: false });
    const termForm = useForm({ academic_session_id: '', name: '', starts_on: '', ends_on: '', is_current: false });
    const defaultGroupsForm = useForm({});
    const defaultClassesForm = useForm({});
    const subjectBulkForm = useForm({ subject_ids: [] });
    const duplicateForm = useForm({ name: '', starts_on: '', ends_on: '', is_current: false, copy_enrollments: true, copy_teacher_assignments: true, copy_assessment_flows: true });
    const scoreLockForm = useForm({ score_entries_locked: false, score_lock_at: '', score_unlock_at: '' });

    const steps = [
        { key: 'class-groups', label: 'Class groups', count: classGroups.length },
        { key: 'classes', label: 'Classes', count: classes.length },
        { key: 'class-arms', label: 'Class arms', count: classArms.length },
        { key: 'sessions', label: 'Sessions', count: sessions.length },
        { key: 'terms', label: 'Terms', count: sessions.reduce((total, session) => total + (session.terms?.length ?? 0), 0) },
        { key: 'subjects', label: 'Subjects', count: schoolSubjects.length },
    ];

    const submit = (event, form, routeName) => {
        event.preventDefault();
        form.post(route(routeName), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };
    const submitCreation = (event, form, routeName) => {
        event.preventDefault();
        form.post(route(routeName), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();

                if (! addAnother) {
                    setCreationModal(null);
                }
            },
        });
    };
    const closeCreationModal = () => {
        setCreationModal(null);
        setAddAnother(false);
    };
    const openDuplicateSession = (session) => {
        duplicateForm.setData({ name: '', starts_on: '', ends_on: '', is_current: false, copy_enrollments: true, copy_teacher_assignments: true, copy_assessment_flows: true });
        duplicateForm.clearErrors();
        setDuplicateSession(session);
    };
    const submitDuplicateSession = (event) => {
        event.preventDefault();
        duplicateForm.post(route('school.academic-sessions.duplicate', duplicateSession.id), {
            preserveScroll: true,
            onSuccess: () => setDuplicateSession(null),
        });
    };
    const openScoreLock = (session) => {
        scoreLockForm.setData({
            score_entries_locked: Boolean(session.score_entries_locked),
            score_lock_at: session.score_lock_at?.slice(0, 16) ?? '',
            score_unlock_at: session.score_unlock_at?.slice(0, 16) ?? '',
        });
        scoreLockForm.clearErrors();
        setScoreLockSession(session);
    };
    const submitScoreLock = (event) => {
        event.preventDefault();
        scoreLockForm.put(route('school.academic-sessions.score-lock.update', scoreLockSession.id), {
            preserveScroll: true,
            onSuccess: () => setScoreLockSession(null),
        });
    };
    const submitBulkSubjects = (event) => {
        event.preventDefault();
        subjectBulkForm.post(route('school.school-subjects.bulk.store'), {
            preserveScroll: true,
            onSuccess: () => {
                subjectBulkForm.reset();

                if (! addAnother) {
                    setCreationModal(null);
                }
            },
        });
    };
    const openEdit = (type, item, data) => {
        setEditData(data);
        setEditModal({ type, item });
    };
    const submitEdit = (event, routeName) => {
        event.preventDefault();
        router.put(route(routeName, editModal.item.id), editData, {
            preserveScroll: true,
            onSuccess: () => setEditModal(null),
        });
    };
    const confirmDelete = (event) => {
        event.preventDefault();
        router.delete(route(deleteModal.routeName, deleteModal.item.id), {
            preserveScroll: true,
            onSuccess: () => setDeleteModal(null),
        });
    };

    return (
        <AuthenticatedLayout
            title="Academic setup"
            subtitle="Build the school structure one step at a time."
        >
            <Head title="Academic setup" />

            <div className="space-y-5">
                <section className="rounded-xl border border-slate-200 bg-white/95 p-3 shadow-sm ring-1 ring-slate-100">
                    <div className="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
                        {steps.map((step, index) => {
                            const isActive = activeStep === step.key;

                            return (
                                <button
                                    key={step.key}
                                    type="button"
                                    onClick={() => setActiveStep(step.key)}
                                    className={[
                                        'flex min-h-16 items-center justify-between rounded-lg border px-3 py-2 text-left transition',
                                        isActive
                                            ? 'border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] text-[var(--brand-primary)] shadow-sm'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50',
                                    ].join(' ')}
                                >
                                    <span>
                                        <span className="block text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
                                            Step {index + 1}
                                        </span>
                                        <span className="mt-1 block text-sm font-bold">{step.label}</span>
                                    </span>
                                    <span className={[
                                        'grid h-7 w-7 place-items-center rounded-full text-xs font-bold',
                                        isActive ? 'bg-[var(--brand-primary)] text-white' : 'bg-slate-100 text-slate-500',
                                    ].join(' ')}>
                                        {step.count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                </section>

                {activeStep === 'class-groups' && (
                    <Panel title="Class groups" description="Create broad school levels like Creche, Nursery, Primary, and Secondary.">
                        <TemplateAction
                            title="Use Nigerian school groups"
                            description="Adds Creche, Nursery, Primary, and Secondary. Existing groups are skipped."
                            buttonLabel="Add defaults"
                            processing={defaultGroupsForm.processing}
                            onSubmit={(event) => submit(event, defaultGroupsForm, 'school.class-groups.defaults.store')}
                        />

                        <AddButton onClick={() => setCreationModal('class-group')}>Add class group</AddButton>

                        <DataTable
                            columns={[
                                { key: 'name', label: 'Name', className: 'font-semibold text-slate-900' },
                                { key: 'sort_order', label: 'Order', className: 'text-slate-500' },
                                { key: 'classes_count', label: 'Classes', className: 'text-slate-500' },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    sortable: false,
                                    headerClassName: 'text-right',
                                    className: 'text-right',
                                    render: (group) => (
                                        <RowActions
                                            onEdit={() => openEdit('class-group', group, { name: group.name, sort_order: group.sort_order })}
                                            onDelete={() => setDeleteModal({ title: 'Delete class group', routeName: 'school.class-groups.destroy', item: group })}
                                        />
                                    ),
                                },
                            ]}
                            rows={classGroups}
                            emptyLabel="No class groups yet."
                            searchPlaceholder="Search class groups"
                        />
                    </Panel>
                )}

                {activeStep === 'classes' && (
                    <Panel title="Classes" description="Create classes inside a class group, such as Primary 1 or Nursery 2.">
                        <TemplateAction
                            title="Use Nigerian class list"
                            description="Adds Creche 1-2, Nursery 1-3, Primary 1-6, JSS 1-3, and SSS 1-3. Missing groups are created automatically."
                            buttonLabel="Add defaults"
                            processing={defaultClassesForm.processing}
                            onSubmit={(event) => submit(event, defaultClassesForm, 'school.classes.defaults.store')}
                        />

                        <AddButton onClick={() => setCreationModal('class')}>Add class</AddButton>

                        <DataTable
                            columns={[
                                { key: 'name', label: 'Class', className: 'font-semibold text-slate-900' },
                                { key: 'group', label: 'Group', className: 'text-slate-500', render: (item) => item.class_group?.name, sortValue: (item) => item.class_group?.name },
                                { key: 'arms', label: 'Arms', className: 'text-slate-500', render: (item) => item.arms?.length ?? 0, sortValue: (item) => item.arms?.length ?? 0 },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    sortable: false,
                                    headerClassName: 'text-right',
                                    className: 'text-right',
                                    render: (item) => (
                                        <RowActions
                                            onEdit={() => openEdit('class', item, { name: item.name, class_group_id: item.class_group_id, sort_order: item.sort_order })}
                                            onDelete={() => setDeleteModal({ title: 'Delete class', routeName: 'school.classes.destroy', item })}
                                        />
                                    ),
                                },
                            ]}
                            rows={classes}
                            emptyLabel="No classes yet."
                            searchPlaceholder="Search classes"
                        />
                    </Panel>
                )}

                {activeStep === 'class-arms' && (
                    <Panel title="Class arms" description="Split classes into arms, for example Primary 1A and Primary 1B.">
                        <AddButton onClick={() => setCreationModal('class-arm')}>Add class arm</AddButton>

                        <DataTable
                            columns={[
                                { key: 'name', label: 'Class arm', className: 'font-semibold text-slate-900' },
                                { key: 'class', label: 'Class', className: 'text-slate-500', render: (arm) => arm.school_class?.name, sortValue: (arm) => arm.school_class?.name },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    sortable: false,
                                    headerClassName: 'text-right',
                                    className: 'text-right',
                                    render: (arm) => (
                                        <RowActions
                                            onEdit={() => openEdit('class-arm', arm, { name: arm.name, school_class_id: arm.school_class_id, label: arm.label })}
                                            onDelete={() => setDeleteModal({ title: 'Delete class arm', routeName: 'school.class-arms.destroy', item: arm })}
                                        />
                                    ),
                                },
                            ]}
                            rows={classArms}
                            emptyLabel="No class arms yet."
                            searchPlaceholder="Search class arms"
                        />
                    </Panel>
                )}

                {activeStep === 'sessions' && (
                    <Panel title="Academic sessions" description="Create school years such as 2026/2027 and mark the current one.">
                        <AddButton onClick={() => setCreationModal('session')}>Add session</AddButton>

                        <DataTable
                            columns={[
                                { key: 'name', label: 'Session', className: 'font-semibold text-slate-900' },
                                { key: 'is_current', label: 'Current', className: 'text-slate-500', render: (session) => session.is_current ? 'Yes' : 'No' },
                                { key: 'terms', label: 'Terms', className: 'text-slate-500', render: (session) => session.terms?.length ?? 0, sortValue: (session) => session.terms?.length ?? 0 },
                                { key: 'score_lock', label: 'Scores', sortable: false, render: (session) => <span className={session.is_score_locked ? 'font-bold text-red-600' : 'font-bold text-emerald-700'}>{session.is_score_locked ? 'Locked' : 'Open'}</span> },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    sortable: false,
                                    headerClassName: 'text-right',
                                    className: 'text-right',
                                    render: (session) => (
                                        <div className="inline-flex items-center gap-2">
                                            <button type="button" onClick={() => openDuplicateSession(session)} className="grid h-8 w-8 place-items-center rounded-md border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" title="Duplicate session"><Copy className="h-4 w-4" aria-hidden="true" /></button>
                                            <button type="button" onClick={() => openScoreLock(session)} className="grid h-8 w-8 place-items-center rounded-md border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" title="Manage score lock">{session.is_score_locked ? <LockKeyhole className="h-4 w-4 text-red-600" aria-hidden="true" /> : <UnlockKeyhole className="h-4 w-4 text-emerald-700" aria-hidden="true" />}</button>
                                            <RowActions
                                                onEdit={() => openEdit('session', session, { name: session.name, starts_on: session.starts_on, ends_on: session.ends_on, is_current: session.is_current })}
                                                onDelete={() => setDeleteModal({ title: 'Delete academic session', routeName: 'school.academic-sessions.destroy', item: session })}
                                            />
                                        </div>
                                    ),
                                },
                            ]}
                            rows={sessions}
                            emptyLabel="No sessions yet."
                            searchPlaceholder="Search sessions"
                        />
                    </Panel>
                )}

                {activeStep === 'terms' && (
                    <Panel title="Terms" description="Create First, Second, and Third Term under an academic session.">
                        <AddButton onClick={() => setCreationModal('term')}>Add term</AddButton>

                        <DataTable
                            columns={[
                                { key: 'name', label: 'Term', className: 'font-semibold text-slate-900' },
                                { key: 'session', label: 'Session', className: 'text-slate-500', render: (term) => term.sessionName, sortValue: (term) => term.sessionName },
                                { key: 'is_current', label: 'Current', className: 'text-slate-500', render: (term) => term.is_current ? 'Yes' : 'No' },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    sortable: false,
                                    headerClassName: 'text-right',
                                    className: 'text-right',
                                    render: (term) => (
                                        <RowActions
                                            onEdit={() => openEdit('term', term, { name: term.name, academic_session_id: term.academic_session_id, starts_on: term.starts_on, ends_on: term.ends_on, is_current: term.is_current })}
                                            onDelete={() => setDeleteModal({ title: 'Delete term', routeName: 'school.terms.destroy', item: term })}
                                        />
                                    ),
                                },
                            ]}
                            rows={sessions.flatMap((session) => (session.terms ?? []).map((term) => ({ ...term, sessionName: session.name, academic_session_id: session.id })))}
                            emptyLabel="No terms yet."
                            searchPlaceholder="Search terms"
                        />
                    </Panel>
                )}

                {activeStep === 'subjects' && (
                    <Panel title="School subjects" description="Attach platform-approved global subjects to this school.">
                        <AddButton onClick={() => setCreationModal('subjects')}>Add subjects</AddButton>

                        <DataTable
                            columns={[
                                { key: 'display_name', label: 'School subject', className: 'font-semibold text-slate-900', render: (schoolSubject) => schoolSubject.custom_name || schoolSubject.subject?.name, sortValue: (schoolSubject) => schoolSubject.custom_name || schoolSubject.subject?.name },
                                { key: 'global_subject', label: 'Global subject', className: 'text-slate-500', render: (schoolSubject) => schoolSubject.subject?.name, sortValue: (schoolSubject) => schoolSubject.subject?.name },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    sortable: false,
                                    headerClassName: 'text-right',
                                    className: 'text-right',
                                    render: (schoolSubject) => (
                                        <RowActions
                                            onEdit={() => openEdit('subject', schoolSubject, { subject_id: schoolSubject.subject_id, custom_name: schoolSubject.custom_name || '' })}
                                            onDelete={() => setDeleteModal({ title: 'Delete school subject', routeName: 'school.school-subjects.destroy', item: { ...schoolSubject, name: schoolSubject.subject?.name } })}
                                        />
                                    ),
                                },
                            ]}
                            rows={schoolSubjects}
                            emptyLabel="No school subjects yet."
                            searchPlaceholder="Search subjects"
                        />
                    </Panel>
                )}

                <CreationModal
                    show={creationModal === 'class-group'}
                    title="Add class group"
                    description="Create a broad school level such as Primary or Secondary."
                    processing={classGroupForm.processing}
                    addAnother={addAnother}
                    onAddAnotherChange={setAddAnother}
                    onClose={closeCreationModal}
                    onSubmit={(event) => submitCreation(event, classGroupForm, 'school.class-groups.store')}
                >
                    <div>
                        <InputLabel htmlFor="class_group_name" value="Name" />
                        <TextInput id="class_group_name" value={classGroupForm.data.name} className="mt-1 block w-full" onChange={(event) => classGroupForm.setData('name', event.target.value)} placeholder="Primary" />
                        <InputError message={classGroupForm.errors.name} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="class_group_sort" value="Order" />
                        <TextInput id="class_group_sort" type="number" min="0" value={classGroupForm.data.sort_order} className="mt-1 block w-full" onChange={(event) => classGroupForm.setData('sort_order', event.target.value)} />
                        <InputError message={classGroupForm.errors.sort_order} className="mt-1" />
                    </div>
                </CreationModal>

                <CreationModal
                    show={creationModal === 'class'}
                    title="Add class"
                    description="Create a class inside one of the class groups."
                    processing={classForm.processing}
                    addAnother={addAnother}
                    onAddAnotherChange={setAddAnother}
                    onClose={closeCreationModal}
                    onSubmit={(event) => submitCreation(event, classForm, 'school.classes.store')}
                >
                    <div>
                        <InputLabel htmlFor="class_group_id" value="Group" />
                        <SelectInput id="class_group_id" value={classForm.data.class_group_id} onChange={(event) => classForm.setData('class_group_id', event.target.value)}>
                            <option value="">Select group</option>
                            {classGroups.map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}
                        </SelectInput>
                        <InputError message={classForm.errors.class_group_id} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="class_name" value="Name" />
                        <TextInput id="class_name" value={classForm.data.name} className="mt-1 block w-full" onChange={(event) => classForm.setData('name', event.target.value)} placeholder="Primary 1" />
                        <InputError message={classForm.errors.name} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="class_sort" value="Order" />
                        <TextInput id="class_sort" type="number" min="0" value={classForm.data.sort_order} className="mt-1 block w-full" onChange={(event) => classForm.setData('sort_order', event.target.value)} />
                    </div>
                </CreationModal>

                <CreationModal
                    show={creationModal === 'class-arm'}
                    title="Add class arm"
                    description="Create an arm such as Primary 1A or JSS 2B."
                    processing={classArmForm.processing}
                    addAnother={addAnother}
                    onAddAnotherChange={setAddAnother}
                    onClose={closeCreationModal}
                    onSubmit={(event) => submitCreation(event, classArmForm, 'school.class-arms.store')}
                >
                    <div>
                        <InputLabel htmlFor="school_class_id" value="Class" />
                        <SelectInput id="school_class_id" value={classArmForm.data.school_class_id} onChange={(event) => classArmForm.setData('school_class_id', event.target.value)}>
                            <option value="">Select class</option>
                            {classes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                        </SelectInput>
                        <InputError message={classArmForm.errors.school_class_id} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="arm_name" value="Name" />
                        <TextInput id="arm_name" value={classArmForm.data.name} className="mt-1 block w-full" onChange={(event) => classArmForm.setData('name', event.target.value)} placeholder="Primary 1A" />
                        <InputError message={classArmForm.errors.name} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="arm_label" value="Label" />
                        <TextInput id="arm_label" value={classArmForm.data.label} className="mt-1 block w-full" onChange={(event) => classArmForm.setData('label', event.target.value)} placeholder="A" />
                    </div>
                </CreationModal>

                <CreationModal
                    show={creationModal === 'session'}
                    title="Add academic session"
                    description="Create a school year such as 2026/2027."
                    processing={sessionForm.processing}
                    addAnother={addAnother}
                    onAddAnotherChange={setAddAnother}
                    onClose={closeCreationModal}
                    onSubmit={(event) => submitCreation(event, sessionForm, 'school.academic-sessions.store')}
                >
                    <div>
                        <InputLabel htmlFor="session_name" value="Name" />
                        <TextInput id="session_name" value={sessionForm.data.name} className="mt-1 block w-full" onChange={(event) => sessionForm.setData('name', event.target.value)} placeholder="2026/2027" />
                        <InputError message={sessionForm.errors.name} className="mt-1" />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="session_starts" value="Starts on" />
                            <TextInput id="session_starts" type="date" value={sessionForm.data.starts_on} className="mt-1 block w-full" onChange={(event) => sessionForm.setData('starts_on', event.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="session_ends" value="Ends on" />
                            <TextInput id="session_ends" type="date" value={sessionForm.data.ends_on} className="mt-1 block w-full" onChange={(event) => sessionForm.setData('ends_on', event.target.value)} />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" checked={sessionForm.data.is_current} onChange={(event) => sessionForm.setData('is_current', event.target.checked)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        Current session
                    </label>
                </CreationModal>

                <CreationModal
                    show={creationModal === 'term'}
                    title="Add term"
                    description="Create a term under an academic session."
                    processing={termForm.processing}
                    addAnother={addAnother}
                    onAddAnotherChange={setAddAnother}
                    onClose={closeCreationModal}
                    onSubmit={(event) => submitCreation(event, termForm, 'school.terms.store')}
                >
                    <div>
                        <InputLabel htmlFor="term_session" value="Session" />
                        <SelectInput id="term_session" value={termForm.data.academic_session_id} onChange={(event) => termForm.setData('academic_session_id', event.target.value)}>
                            <option value="">Select session</option>
                            {sessions.map((session) => <option key={session.id} value={session.id}>{session.name}</option>)}
                        </SelectInput>
                        <InputError message={termForm.errors.academic_session_id} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="term_name" value="Name" />
                        <TextInput id="term_name" value={termForm.data.name} className="mt-1 block w-full" onChange={(event) => termForm.setData('name', event.target.value)} placeholder="First Term" />
                        <InputError message={termForm.errors.name} className="mt-1" />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="term_starts_on" value="Starts on" />
                            <TextInput id="term_starts_on" type="date" value={termForm.data.starts_on} onChange={(event) => termForm.setData('starts_on', event.target.value)} />
                        </div>
                        <div>
                            <InputLabel htmlFor="term_ends_on" value="Ends on" />
                            <TextInput id="term_ends_on" type="date" value={termForm.data.ends_on} onChange={(event) => termForm.setData('ends_on', event.target.value)} />
                        </div>
                    </div>
                    <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" checked={termForm.data.is_current} onChange={(event) => termForm.setData('is_current', event.target.checked)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                        Current term
                    </label>
                </CreationModal>

                <CreationModal
                    show={creationModal === 'subjects'}
                    title="Add school subjects"
                    description="Search and select one or more global subjects for this school."
                    processing={subjectBulkForm.processing}
                    submitDisabled={subjectBulkForm.data.subject_ids.length === 0}
                    addAnother={addAnother}
                    onAddAnotherChange={setAddAnother}
                    onClose={closeCreationModal}
                    onSubmit={submitBulkSubjects}
                >
                    <div>
                        <InputLabel htmlFor="subject_ids" value="Global subjects" />
                        <SearchableMultiSelect
                            id="subject_ids"
                            selectedIds={subjectBulkForm.data.subject_ids}
                            options={subjects}
                            placeholder="Search and select one or more subjects."
                            searchPlaceholder="Search global subjects"
                            onChange={(subjectIds) => subjectBulkForm.setData('subject_ids', subjectIds)}
                        />
                        <InputError message={subjectBulkForm.errors.subject_ids} className="mt-1" />
                    </div>
                </CreationModal>

                <Modal show={Boolean(editModal)} closeable={false} maxWidth="xl">
                    {editModal && (
                        <form onSubmit={(event) => submitEdit(event, {
                            'class-group': 'school.class-groups.update',
                            class: 'school.classes.update',
                            'class-arm': 'school.class-arms.update',
                            session: 'school.academic-sessions.update',
                            term: 'school.terms.update',
                            subject: 'school.school-subjects.update',
                        }[editModal.type])}>
                            <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                                <div>
                                    <h2 className="text-base font-bold text-slate-950">Edit {editModal.item.name ?? editModal.item.subject?.name}</h2>
                                    <p className="mt-1 text-sm text-slate-500">Update this setup record.</p>
                                </div>
                                <button type="button" onClick={() => setEditModal(null)} className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-900" aria-label="Close">
                                    <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                </button>
                            </div>
                            <div className="grid gap-4 p-5">
                                {editModal.type === 'class' && (
                                    <div>
                                        <InputLabel htmlFor="edit_class_group_id" value="Group" />
                                        <SelectInput id="edit_class_group_id" value={editData.class_group_id} onChange={(event) => setEditData({ ...editData, class_group_id: event.target.value })}>
                                            {classGroups.map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}
                                        </SelectInput>
                                    </div>
                                )}
                                {editModal.type === 'class-arm' && (
                                    <div>
                                        <InputLabel htmlFor="edit_school_class_id" value="Class" />
                                        <SelectInput id="edit_school_class_id" value={editData.school_class_id} onChange={(event) => setEditData({ ...editData, school_class_id: event.target.value })}>
                                            {classes.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                                        </SelectInput>
                                    </div>
                                )}
                                {editModal.type === 'term' && (
                                    <div>
                                        <InputLabel htmlFor="edit_academic_session_id" value="Session" />
                                        <SelectInput id="edit_academic_session_id" value={editData.academic_session_id} onChange={(event) => setEditData({ ...editData, academic_session_id: event.target.value })}>
                                            {sessions.map((session) => <option key={session.id} value={session.id}>{session.name}</option>)}
                                        </SelectInput>
                                    </div>
                                )}
                                {editModal.type !== 'subject' && (
                                    <div>
                                        <InputLabel htmlFor="edit_name" value="Name" />
                                        <TextInput id="edit_name" value={editData.name ?? ''} className="mt-1 block w-full" onChange={(event) => setEditData({ ...editData, name: event.target.value })} />
                                    </div>
                                )}
                                {['class-group', 'class'].includes(editModal.type) && (
                                    <div>
                                        <InputLabel htmlFor="edit_sort_order" value="Order" />
                                        <TextInput id="edit_sort_order" type="number" min="0" value={editData.sort_order ?? 0} className="mt-1 block w-full" onChange={(event) => setEditData({ ...editData, sort_order: event.target.value })} />
                                    </div>
                                )}
                                {editModal.type === 'class-arm' && (
                                    <div>
                                        <InputLabel htmlFor="edit_label" value="Label" />
                                        <TextInput id="edit_label" value={editData.label ?? ''} className="mt-1 block w-full" onChange={(event) => setEditData({ ...editData, label: event.target.value })} />
                                    </div>
                                )}
                                {['session', 'term'].includes(editModal.type) && (
                                    <>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <InputLabel htmlFor="edit_starts_on" value="Starts on" />
                                                <TextInput id="edit_starts_on" type="date" value={editData.starts_on ?? ''} className="mt-1 block w-full" onChange={(event) => setEditData({ ...editData, starts_on: event.target.value })} />
                                            </div>
                                            <div>
                                                <InputLabel htmlFor="edit_ends_on" value="Ends on" />
                                                <TextInput id="edit_ends_on" type="date" value={editData.ends_on ?? ''} className="mt-1 block w-full" onChange={(event) => setEditData({ ...editData, ends_on: event.target.value })} />
                                            </div>
                                        </div>
                                        <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <input type="checkbox" checked={Boolean(editData.is_current)} onChange={(event) => setEditData({ ...editData, is_current: event.target.checked })} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                                            Current
                                        </label>
                                    </>
                                )}
                                {editModal.type === 'subject' && (
                                    <div>
                                        <InputLabel htmlFor="edit_custom_name" value="School display name" />
                                        <TextInput id="edit_custom_name" value={editData.custom_name ?? ''} className="mt-1 block w-full" onChange={(event) => setEditData({ ...editData, custom_name: event.target.value })} placeholder="Optional" />
                                    </div>
                                )}
                            </div>
                            <div className="flex justify-end border-t border-slate-200 px-5 py-4">
                                <PrimaryButton>Save changes</PrimaryButton>
                            </div>
                        </form>
                    )}
                </Modal>

                <Modal show={Boolean(duplicateSession)} closeable={false} maxWidth="xl">
                    {duplicateSession && (
                        <form onSubmit={submitDuplicateSession}>
                            <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                                <div><h2 className="text-base font-bold text-slate-950">Duplicate {duplicateSession.name}</h2><p className="mt-1 text-sm text-slate-500">Create a new session using this academic structure. Existing scores and completion states will not be copied.</p></div>
                                <button type="button" onClick={() => setDuplicateSession(null)} className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-900" aria-label="Close"><X className="h-4 w-4" aria-hidden="true" /></button>
                            </div>
                            <div className="grid gap-4 p-5">
                                <div><InputLabel htmlFor="duplicate_name" value="New session name" /><TextInput id="duplicate_name" value={duplicateForm.data.name} onChange={(event) => duplicateForm.setData('name', event.target.value)} className="mt-1 block w-full" placeholder="2027/2028" /><InputError message={duplicateForm.errors.name} className="mt-1" /></div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div><InputLabel htmlFor="duplicate_starts_on" value="Starts on" /><TextInput id="duplicate_starts_on" type="date" value={duplicateForm.data.starts_on} onChange={(event) => duplicateForm.setData('starts_on', event.target.value)} className="mt-1 block w-full" /></div>
                                    <div><InputLabel htmlFor="duplicate_ends_on" value="Ends on" /><TextInput id="duplicate_ends_on" type="date" value={duplicateForm.data.ends_on} onChange={(event) => duplicateForm.setData('ends_on', event.target.value)} className="mt-1 block w-full" /><InputError message={duplicateForm.errors.ends_on} className="mt-1" /></div>
                                </div>
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div className="text-sm font-bold text-slate-950">Include in the new session</div>
                                    <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                        {[
                                            ['copy_enrollments', 'Student enrollments'],
                                            ['copy_teacher_assignments', 'Teacher assignments'],
                                            ['copy_assessment_flows', 'Assessment flows'],
                                            ['is_current', 'Make this the current session'],
                                        ].map(([key, label]) => <label key={key} className="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" checked={duplicateForm.data[key]} onChange={(event) => duplicateForm.setData(key, event.target.checked)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />{label}</label>)}
                                    </div>
                                </div>
                            </div>
                            <div className="flex justify-end gap-3 border-t border-slate-200 px-5 py-4"><button type="button" onClick={() => setDuplicateSession(null)} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button><PrimaryButton disabled={duplicateForm.processing}><Copy className="mr-2 h-4 w-4" aria-hidden="true" />Duplicate session</PrimaryButton></div>
                        </form>
                    )}
                </Modal>

                <Modal show={Boolean(scoreLockSession)} closeable={false} maxWidth="xl">
                    {scoreLockSession && (
                        <form onSubmit={submitScoreLock}>
                            <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                                <div><h2 className="text-base font-bold text-slate-950">Score entry access • {scoreLockSession.name}</h2><p className="mt-1 text-sm text-slate-500">Teachers cannot save, import, or complete scores while this session is locked. School super admins retain emergency access.</p></div>
                                <button type="button" onClick={() => setScoreLockSession(null)} className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-900" aria-label="Close"><X className="h-4 w-4" aria-hidden="true" /></button>
                            </div>
                            <div className="grid gap-4 p-5">
                                <label className="flex items-start justify-between gap-4 rounded-lg border border-slate-200 p-4">
                                    <span><span className="block text-sm font-bold text-slate-950">Lock score entry now</span><span className="mt-1 block text-sm text-slate-500">Use scheduling below when the change should happen later.</span></span>
                                    <input type="checkbox" checked={scoreLockForm.data.score_entries_locked} onChange={(event) => scoreLockForm.setData('score_entries_locked', event.target.checked)} className="mt-1 rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                                </label>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div><InputLabel htmlFor="score_lock_at" value="Scheduled lock" /><TextInput id="score_lock_at" type="datetime-local" value={scoreLockForm.data.score_lock_at} onChange={(event) => scoreLockForm.setData('score_lock_at', event.target.value)} className="mt-1 block w-full" /><InputError message={scoreLockForm.errors.score_lock_at} className="mt-1" /></div>
                                    <div><InputLabel htmlFor="score_unlock_at" value="Scheduled unlock" /><TextInput id="score_unlock_at" type="datetime-local" value={scoreLockForm.data.score_unlock_at} onChange={(event) => scoreLockForm.setData('score_unlock_at', event.target.value)} className="mt-1 block w-full" /><InputError message={scoreLockForm.errors.score_unlock_at} className="mt-1" /></div>
                                </div>
                                <button type="button" onClick={() => scoreLockForm.setData({ ...scoreLockForm.data, score_lock_at: '', score_unlock_at: '' })} className="inline-flex w-fit items-center gap-2 text-sm font-bold text-red-600 hover:text-red-700"><Clock3 className="h-4 w-4" aria-hidden="true" />Clear schedule</button>
                            </div>
                            <div className="flex justify-end gap-3 border-t border-slate-200 px-5 py-4"><button type="button" onClick={() => setScoreLockSession(null)} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button><PrimaryButton disabled={scoreLockForm.processing}>Save access</PrimaryButton></div>
                        </form>
                    )}
                </Modal>

                <Modal show={Boolean(deleteModal)} closeable={false} maxWidth="md">
                    {deleteModal && (
                        <form onSubmit={confirmDelete}>
                            <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                                <div>
                                    <h2 className="text-base font-bold text-slate-950">{deleteModal.title}</h2>
                                    <p className="mt-1 text-sm text-slate-500">This action cannot be undone.</p>
                                </div>
                                <button type="button" onClick={() => setDeleteModal(null)} className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-900" aria-label="Close">
                                    <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                </button>
                            </div>
                            <div className="p-5 text-sm text-slate-600">
                                Delete <span className="font-bold text-slate-950">{deleteModal.item.name}</span>?
                            </div>
                            <div className="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
                                <button type="button" onClick={() => setDeleteModal(null)} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancel</button>
                                <button type="submit" className="rounded-md bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-700">Delete</button>
                            </div>
                        </form>
                    )}
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
