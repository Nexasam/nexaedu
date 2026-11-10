import DataTable from '@/Components/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableMultiSelect from '@/Components/SearchableMultiSelect';
import SearchableSelect from '@/Components/SearchableSelect';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

function subjectLabel(item) {
    return [
        item.class_arm?.name,
        item.school_subject?.custom_name || item.school_subject?.subject?.name,
        item.term?.name,
        item.academic_session?.name,
    ].filter(Boolean).join(' • ');
}

function armLabel(arm) {
    return [
        arm.school_class?.class_group?.name,
        arm.school_class?.name,
        arm.name,
    ].filter(Boolean).join(' • ');
}

export default function Index({ classTermSubjects, classArms, classGroups, assessmentGroups }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.assessments === 'readwrite';
    const subjectOptions = classTermSubjects.map((item) => ({ id: item.id, name: subjectLabel(item) }));
    const armOptions = classArms.map((arm) => ({ id: arm.id, name: armLabel(arm) }));
    const groupOptions = classGroups.map((group) => ({ id: group.id, name: group.name }));
    const form = useForm({
        target_scope: 'selected',
        class_term_subject_ids: [],
        class_arm_id: '',
        class_group_id: '',
        name: 'Default assessment flow',
        items: [
            { name: 'CA 1', max_score: 20, raw_max_score: 20, weight: 20 },
            { name: 'CA 2', max_score: 30, raw_max_score: 30, weight: 30 },
            { name: 'Exam', max_score: 50, raw_max_score: 50, weight: 50 },
        ],
    });
    const [showFlowModal, setShowFlowModal] = useState(false);
    const [confirmedOverwrite, setConfirmedOverwrite] = useState(false);
    const [confirmation, setConfirmation] = useState(null);
    const total = form.data.items.reduce((sum, item) => sum + Number(item.weight || item.max_score || 0), 0);
    const targetClassTermSubjectIds = useMemo(() => {
        if (form.data.target_scope === 'all') {
            return classTermSubjects.map((item) => Number(item.id));
        }

        if (form.data.target_scope === 'arm') {
            return classTermSubjects
                .filter((item) => Number(item.class_arm_id) === Number(form.data.class_arm_id))
                .map((item) => Number(item.id));
        }

        if (form.data.target_scope === 'group') {
            return classTermSubjects
                .filter((item) => Number(item.class_arm?.school_class?.class_group?.id) === Number(form.data.class_group_id))
                .map((item) => Number(item.id));
        }

        return form.data.class_term_subject_ids.map(Number);
    }, [classTermSubjects, form.data.class_arm_id, form.data.class_group_id, form.data.class_term_subject_ids, form.data.target_scope]);
    const existingTargetFlows = useMemo(() => assessmentGroups.filter((group) => targetClassTermSubjectIds.includes(Number(group.class_term_subject_id))), [assessmentGroups, targetClassTermSubjectIds]);
    const shouldWarnOverwrite = existingTargetFlows.length > 0;

    useEffect(() => {
        setConfirmedOverwrite(false);
    }, [targetClassTermSubjectIds.join(',')]);

    const setItem = (index, key, value) => {
        form.setData('items', form.data.items.map((item, itemIndex) => (
            itemIndex === index ? { ...item, [key]: value } : item
        )));
    };

    const setRawMaximum = (index, value) => {
        form.setData('items', form.data.items.map((item, itemIndex) => (
            itemIndex === index ? { ...item, raw_max_score: value, max_score: value } : item
        )));
    };

    const addItem = () => {
        setConfirmation({
            title: 'Add assessment item?',
            description: 'This adds another score column to the assessment flow, such as CA, assignment, practical, or exam.',
            actionLabel: 'Add item',
            onConfirm: () => form.setData('items', [...form.data.items, { name: '', max_score: 0, raw_max_score: 0, weight: 0 }]),
        });
    };

    const removeItem = (index) => {
        setConfirmation({
            title: 'Remove assessment item?',
            description: 'This removes the item from this flow. If the item already has recorded scores, those scores may no longer be used in this flow.',
            actionLabel: 'Remove item',
            danger: true,
            onConfirm: () => form.setData('items', form.data.items.filter((_, itemIndex) => itemIndex !== index)),
        });
    };

    const saveFlow = () => {
        if (shouldWarnOverwrite && !confirmedOverwrite) {
            return;
        }

        form.post(route('school.assessments.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('name', 'items');
                setConfirmedOverwrite(false);
                setShowFlowModal(false);
            },
        });
    };

    const submit = (event) => {
        event.preventDefault();

        setConfirmation({
            title: 'Save assessment flow?',
            description: 'This will save the flow name and score breakdown for the selected target. If existing flows are selected, they will be overwritten after your overwrite confirmation.',
            actionLabel: 'Save flow',
            onConfirm: saveFlow,
        });
    };

    const openCreateFlow = () => {
        form.setData({
            target_scope: 'selected',
            class_term_subject_ids: [],
            class_arm_id: '',
            class_group_id: '',
            name: 'Default assessment flow',
            items: [
                { name: 'CA 1', max_score: 20, raw_max_score: 20, weight: 20 },
                { name: 'CA 2', max_score: 30, raw_max_score: 30, weight: 30 },
                { name: 'Exam', max_score: 50, raw_max_score: 50, weight: 50 },
            ],
        });
        setConfirmedOverwrite(false);
        setShowFlowModal(true);
    };

    const loadFlowForEdit = (group) => {
        form.setData({
            ...form.data,
            target_scope: 'selected',
            class_term_subject_ids: [group.class_term_subject_id],
            class_arm_id: '',
            class_group_id: '',
            name: group.name,
            items: group.items.map((item) => ({
                name: item.name,
                max_score: item.max_score,
                raw_max_score: item.raw_max_score ?? item.max_score,
                weight: item.weight ?? item.max_score,
            })),
        });
        setConfirmedOverwrite(false);
        setShowFlowModal(true);
    };

    const editFlow = (group) => {
        setConfirmation({
            title: 'Edit assessment flow?',
            description: 'This loads the selected assessment flow into the form. Any unsaved changes currently in the form will be replaced.',
            actionLabel: 'Load flow',
            onConfirm: () => loadFlowForEdit(group),
        });
    };

    const confirmAction = () => {
        confirmation?.onConfirm();
        setConfirmation(null);
    };

    return (
        <AuthenticatedLayout
            title="Assessment setup"
            subtitle="Create score structures for class-arm subjects. Teachers with permission can create subject-specific overrides."
            actions={canWrite && (
                <button
                    type="button"
                    onClick={openCreateFlow}
                    className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95"
                >
                    <Plus className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                    New assessment flow
                </button>
            )}
        >
            <Head title="Assessment setup" />

            <Modal show={Boolean(confirmation)} closeable={false} maxWidth="md">
                <div className="p-5">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex gap-3">
                            <span className={[
                                'grid h-10 w-10 shrink-0 place-items-center rounded-lg',
                                confirmation?.danger ? 'bg-red-50 text-red-700' : 'bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]',
                            ].join(' ')}>
                                <AlertTriangle className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />
                            </span>
                            <div>
                                <h2 className="text-base font-bold text-slate-950">{confirmation?.title}</h2>
                                <p className="mt-1 text-sm leading-6 text-slate-600">{confirmation?.description}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            onClick={() => setConfirmation(null)}
                            className="grid h-8 w-8 place-items-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Close confirmation"
                        >
                            <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                        </button>
                    </div>
                    <div className="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setConfirmation(null)}
                            className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"
                        >
                            Cancel
                        </button>
                        <PrimaryButton type="button" onClick={confirmAction}>
                            {confirmation?.actionLabel}
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            {canWrite && (
                <Modal show={showFlowModal} closeable={false} maxWidth="2xl">
                    <form onSubmit={submit} className="max-h-[88vh] overflow-y-auto">
                        <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                            <div>
                                <h2 className="text-base font-bold text-slate-950">Assessment flow</h2>
                                <p className="mt-1 text-sm text-slate-500">Choose the target subjects and define the score breakdown.</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setShowFlowModal(false)}
                                className="grid h-8 w-8 place-items-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                aria-label="Close modal"
                            >
                                <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                            </button>
                        </div>

                        <div className="px-5 py-4">
                            <div className="grid gap-4 xl:grid-cols-[220px_minmax(0,1fr)]">
                                <div className="xl:col-span-1">
                                    <InputLabel htmlFor="target_scope" value="Apply to" />
                                    <select
                                        id="target_scope"
                                        value={form.data.target_scope}
                                        onChange={(event) => form.setData('target_scope', event.target.value)}
                                        className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                                    >
                                        <option value="selected">Selected subjects</option>
                                        <option value="arm">All subjects in one arm</option>
                                        <option value="group">All subjects in one class group</option>
                                        <option value="all">All subjects in all arms</option>
                                    </select>
                                </div>
                                {form.data.target_scope !== 'all' && (
                                    <div className="min-w-0">
                                        {form.data.target_scope === 'selected' && (
                                            <>
                                                <InputLabel htmlFor="class_term_subject_ids" value="Class arm subjects" />
                                                <SearchableMultiSelect
                                                    id="class_term_subject_ids"
                                                    selectedIds={form.data.class_term_subject_ids}
                                                    options={subjectOptions}
                                                    searchPlaceholder="Search and select subjects"
                                                    placeholder="No matching subjects."
                                                    onChange={(ids) => form.setData('class_term_subject_ids', ids)}
                                                />
                                            </>
                                        )}
                                        {form.data.target_scope === 'arm' && (
                                            <>
                                                <InputLabel htmlFor="class_arm_id" value="Class arm" />
                                                <SearchableSelect
                                                    id="class_arm_id"
                                                    value={form.data.class_arm_id}
                                                    options={armOptions}
                                                    searchPlaceholder="Search class arms"
                                                    placeholder="No matching class arms."
                                                    onChange={(id) => form.setData('class_arm_id', id)}
                                                />
                                            </>
                                        )}
                                        {form.data.target_scope === 'group' && (
                                            <>
                                                <InputLabel htmlFor="class_group_id" value="Class group" />
                                                <SearchableSelect
                                                    id="class_group_id"
                                                    value={form.data.class_group_id}
                                                    options={groupOptions}
                                                    searchPlaceholder="Search class groups"
                                                    placeholder="No matching class groups."
                                                    onChange={(id) => form.setData('class_group_id', id)}
                                                />
                                            </>
                                        )}
                                        <InputError message={form.errors.class_term_subject_ids || form.errors.class_arm_id || form.errors.class_group_id} className="mt-1" />
                                    </div>
                                )}
                            </div>

                            {form.data.target_scope === 'all' && (
                                <div className="mt-4 rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-3 text-sm font-semibold text-[var(--brand-primary)]">
                                    This will apply to every class-arm subject already set up for this school.
                                </div>
                            )}

                            {shouldWarnOverwrite && (
                                <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                    <div className="flex gap-2">
                                        <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} />
                                        <div>
                                            <div className="font-bold">Existing assessment flow will be overwritten</div>
                                            <div className="mt-1">
                                                {existingTargetFlows.length} selected subject(s) already have a flow. Saving will replace the flow name and scores for those subject(s), not create duplicates.
                                            </div>
                                            <label className="mt-3 flex items-center gap-2 font-semibold">
                                                <input
                                                    type="checkbox"
                                                    checked={confirmedOverwrite}
                                                    onChange={(event) => setConfirmedOverwrite(event.target.checked)}
                                                    className="rounded border-amber-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                                                />
                                                I understand and want to overwrite existing flow(s).
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            )}

                            <div className="mt-4 max-w-md">
                                <InputLabel htmlFor="name" value="Flow name" />
                                <TextInput id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="mt-1 block w-full" />
                                <InputError message={form.errors.name} className="mt-1" />
                            </div>

                            <div className="mt-4 grid gap-2">
                                {form.data.items.map((item, index) => (
                                    <div key={index} className="grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[minmax(0,1fr)_130px_130px_auto] sm:items-end">
                                        <div>
                                            <InputLabel htmlFor={`item_name_${index}`} value="Assessment item" />
                                            <TextInput id={`item_name_${index}`} value={item.name} onChange={(event) => setItem(index, 'name', event.target.value)} className="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor={`item_raw_score_${index}`} value="Raw maximum" />
                                            <TextInput id={`item_raw_score_${index}`} type="number" min="1" value={item.raw_max_score} onChange={(event) => setRawMaximum(index, event.target.value)} className="mt-1 block w-full" />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor={`item_weight_${index}`} value="Report weight" />
                                            <TextInput id={`item_weight_${index}`} type="number" min="1" value={item.weight} onChange={(event) => {
                                                setItem(index, 'weight', event.target.value);
                                            }} className="mt-1 block w-full" />
                                        </div>
                                        <button type="button" onClick={() => removeItem(index)} className="inline-flex items-center justify-center gap-2 rounded-md border border-red-100 bg-red-50 px-3 py-2 text-sm font-bold text-red-600">
                                            <Trash2 className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                            Remove
                                        </button>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="text-sm font-bold text-slate-600">Total score: {total}</div>
                                <div className="flex gap-2">
                                    <button type="button" onClick={addItem} className="inline-flex items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700">
                                        <Plus className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                        Add item
                                    </button>
                                    <PrimaryButton disabled={form.processing || (shouldWarnOverwrite && !confirmedOverwrite)}>Save assessment flow</PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </form>
                </Modal>
            )}

            <DataTable
                columns={[
                    { key: 'name', label: 'Flow', className: 'font-semibold text-slate-950' },
                    { key: 'subject', label: 'Subject', render: (group) => subjectLabel(group.class_term_subject), sortValue: (group) => subjectLabel(group.class_term_subject) },
                    { key: 'items', label: 'Items', render: (group) => group.items?.map((item) => `${item.name} ${item.max_score}`).join(', ') },
                    { key: 'total_score', label: 'Total', className: 'text-slate-600' },
                    {
                        key: 'actions',
                        label: 'Actions',
                        sortable: false,
                        render: (group) => (
                            <button
                                type="button"
                                onClick={() => editFlow(group)}
                                className="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-50"
                            >
                                <Pencil className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={2.25} />
                                Edit
                            </button>
                        ),
                    },
                ]}
                rows={assessmentGroups}
                emptyLabel="No assessment flows yet."
                searchPlaceholder="Search assessment flows"
            />
        </AuthenticatedLayout>
    );
}
