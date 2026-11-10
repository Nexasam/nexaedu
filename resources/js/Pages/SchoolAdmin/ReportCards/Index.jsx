import DataTable from '@/Components/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, FileText, Settings2, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

function armLabel(arm) {
    return [arm.school_class?.class_group?.name, arm.school_class?.name, arm.name].filter(Boolean).join(' • ');
}

export default function Index({ templates, platformTemplates, templateSelections, academicSessions, terms, classArms, classGroups, runs }) {
    const { auth } = usePage().props;
    const canPrepare = auth.permissions?.report_card_preparation === 'readwrite';
    const canConfigure = auth.permissions?.report_card_settings === 'readwrite';
    const [showCreate, setShowCreate] = useState(false);
    const [showConfiguration, setShowConfiguration] = useState(false);
    const form = useForm({ report_card_template_id: '', academic_session_id: '', term_id: '', class_arm_id: '' });
    const selectionForm = useForm({ class_group_id: '', platform_report_card_template_id: '' });
    const policyForm = useForm({ term_result_mode: 'independent', term_weights: [1, 1, 1] });
    const sessionTerms = useMemo(() => terms.filter((term) => Number(term.academic_session_id) === Number(form.data.academic_session_id)), [form.data.academic_session_id, terms]);
    const selectedGroup = classGroups.find((group) => Number(group.id) === Number(selectionForm.data.class_group_id));
    const compatibleTemplates = platformTemplates.filter((template) => (template.education_levels ?? [template.education_level]).includes(selectedGroup?.education_level));
    const selectedPlatformTemplate = platformTemplates.find((template) => Number(template.id) === Number(selectionForm.data.platform_report_card_template_id));
    const selectedSelection = templateSelections.find((selection) => Number(selection.class_group_id) === Number(selectionForm.data.class_group_id));
    const selectedSchoolTemplate = templates.find((template) => Number(template.id) === Number(selectedSelection?.report_card_template_id));

    useEffect(() => {
        if (selectedSchoolTemplate) {
            policyForm.setData({
                term_result_mode: selectedSchoolTemplate.term_result_mode ?? 'independent',
                term_weights: selectedSchoolTemplate.term_weights?.length ? selectedSchoolTemplate.term_weights : [1, 1, 1],
            });
        }
    }, [selectedSchoolTemplate?.id]);

    const submit = (event) => {
        event.preventDefault();
        form.post(route('school.report-cards.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowCreate(false);
            },
        });
    };

    const selectArm = (armId) => {
        const arm = classArms.find((item) => Number(item.id) === Number(armId));
        const selection = templateSelections.find((item) => Number(item.class_group_id) === Number(arm?.school_class?.class_group_id));
        form.setData((data) => ({ ...data, class_arm_id: armId, report_card_template_id: selection?.report_card_template_id ?? '' }));
    };

    const selectGroup = (groupId) => {
        const selection = templateSelections.find((item) => Number(item.class_group_id) === Number(groupId));
        selectionForm.setData({
            class_group_id: groupId,
            platform_report_card_template_id: selection?.platform_report_card_template_id ?? '',
        });
    };

    const submitSelection = (event) => {
        event.preventDefault();
        selectionForm.put(route('school.report-card-template-selection.update'), { preserveScroll: true });
    };

    const submitPolicy = () => {
        policyForm.put(route('school.report-card-templates.policy.update', selectedSchoolTemplate.id), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            title="Report cards"
            subtitle="Select platform-approved templates and generate controlled class report runs."
            actions={(canPrepare || canConfigure) && <div className="flex gap-2">{canConfigure && <button type="button" onClick={() => setShowConfiguration(true)} className="inline-flex items-center gap-2 rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-2 text-sm font-bold text-[var(--brand-primary)] transition hover:brightness-95"><Settings2 className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />Template selection</button>}{canPrepare && templates.length > 0 && <button type="button" onClick={() => setShowCreate(true)} className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95"><FileText className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />Generate report cards</button>}</div>}
        >
            <Head title="Report cards" />

            {templates.length === 0 && <div className="mb-5 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><AlertTriangle className="mt-0.5 h-5 w-5 shrink-0 text-amber-700" aria-hidden="true" strokeWidth={2.25} /><div><div className="font-bold">Select a report-card template.</div><p className="mt-1 leading-6">Choose a platform-approved template for each class group before generating report cards.</p></div></div>}

            <div className="mb-5 grid gap-3 sm:grid-cols-3">
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div className="text-xs font-bold uppercase tracking-wide text-slate-500">Configured groups</div><div className="mt-2 text-2xl font-bold text-slate-950">{templateSelections.length}/{classGroups.length}</div></div>
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div className="text-xs font-bold uppercase tracking-wide text-slate-500">Class runs</div><div className="mt-2 text-2xl font-bold text-slate-950">{runs.length}</div></div>
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div className="text-xs font-bold uppercase tracking-wide text-slate-500">Approved</div><div className="mt-2 text-2xl font-bold text-slate-950">{runs.filter((run) => run.status === 'approved').length}</div></div>
            </div>

            <DataTable columns={[
                { key: 'template', label: 'Template', render: (run) => run.template?.name || 'Report template', className: 'font-semibold text-slate-950' },
                { key: 'session', label: 'Session', render: (run) => run.academic_session?.name },
                { key: 'term', label: 'Term', render: (run) => run.term?.name },
                { key: 'arm', label: 'Class arm', render: (run) => armLabel(run.class_arm), sortValue: (run) => armLabel(run.class_arm) },
                { key: 'cards_count', label: 'Students', className: 'text-slate-600' },
                { key: 'status', label: 'Status', render: (run) => <span className={run.status === 'approved' ? 'rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700' : 'rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700'}>{run.status}</span> },
                { key: 'actions', label: '', sortable: false, render: (run) => <Link href={route('school.report-cards.show', run.id)} className="inline-flex items-center gap-1.5 rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-1.5 text-xs font-bold text-[var(--brand-primary)] transition hover:brightness-95">Open run<ArrowRight className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={2.25} /></Link> },
            ]} rows={runs} emptyLabel="No report-card runs yet." searchPlaceholder="Search report-card runs" />

            <Modal show={showCreate} closeable={false} maxWidth="xl">
                <form onSubmit={submit}>
                    <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4"><div><h2 className="text-base font-bold text-slate-950">Generate draft report cards</h2><p className="mt-1 text-sm text-slate-500">The selected class arm determines its platform-approved report template.</p></div><button type="button" onClick={() => setShowCreate(false)} className="grid h-8 w-8 place-items-center rounded-md text-slate-400 hover:bg-slate-100" aria-label="Close modal"><X className="h-4 w-4" /></button></div>
                    <div className="grid gap-4 p-5 sm:grid-cols-2">
                        <div className="sm:col-span-2"><InputLabel value="Class arm" /><SearchableSelect value={form.data.class_arm_id} options={classArms.map((arm) => ({ id: arm.id, name: armLabel(arm) }))} searchPlaceholder="Search class arms" placeholder="Select class arm" onChange={selectArm} /><InputError message={form.errors.class_arm_id || form.errors.report_card_template_id || form.errors.report_card_run} className="mt-1" /></div>
                        <div><InputLabel value="Academic session" /><SearchableSelect value={form.data.academic_session_id} options={academicSessions.map((session) => ({ id: session.id, name: session.name }))} searchPlaceholder="Search sessions" placeholder="Select session" onChange={(id) => form.setData((data) => ({ ...data, academic_session_id: id, term_id: '' }))} /><InputError message={form.errors.academic_session_id} className="mt-1" /></div>
                        <div><InputLabel value="Term" /><SearchableSelect value={form.data.term_id} options={sessionTerms.map((term) => ({ id: term.id, name: term.name }))} searchPlaceholder="Search terms" placeholder="Select a session first" onChange={(id) => form.setData('term_id', id)} /><InputError message={form.errors.term_id} className="mt-1" /></div>
                        {form.data.class_arm_id && <div className="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm"><span className="font-semibold text-slate-500">Assigned template:</span> <span className="font-bold text-slate-900">{templates.find((template) => Number(template.id) === Number(form.data.report_card_template_id))?.name ?? 'No template selected for this class group'}</span></div>}
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4"><button type="button" onClick={() => setShowCreate(false)} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button><PrimaryButton disabled={form.processing || !form.data.report_card_template_id}>Generate drafts</PrimaryButton></div>
                </form>
            </Modal>

            <Modal show={showConfiguration} closeable={false} maxWidth="2xl">
                <form onSubmit={submitSelection}>
                    <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4"><div><h2 className="text-base font-bold text-slate-950">Report-card template selection</h2><p className="mt-1 text-sm text-slate-500">Schools can select approved templates; configuration is managed by the platform owner.</p></div><button type="button" onClick={() => setShowConfiguration(false)} className="grid h-8 w-8 place-items-center rounded-md text-slate-400 hover:bg-slate-100" aria-label="Close modal"><X className="h-4 w-4" /></button></div>
                    <div className="grid gap-4 p-5 sm:grid-cols-2">
                        <div><InputLabel value="Class group" /><SearchableSelect value={selectionForm.data.class_group_id} options={classGroups.map((group) => ({ id: group.id, name: `${group.name} • ${group.education_level}` }))} searchPlaceholder="Search class groups" placeholder="Select class group" onChange={selectGroup} /><InputError message={selectionForm.errors.class_group_id} className="mt-1" /></div>
                        <div><InputLabel value="Approved template" /><SearchableSelect value={selectionForm.data.platform_report_card_template_id} options={compatibleTemplates.map((template) => ({ id: template.id, name: template.name }))} searchPlaceholder="Search approved templates" placeholder={selectedGroup ? `Select ${selectedGroup.education_level} template` : 'Select class group first'} onChange={(id) => selectionForm.setData('platform_report_card_template_id', id)} /><InputError message={selectionForm.errors.platform_report_card_template_id || selectionForm.errors.template} className="mt-1" /></div>
                        {selectedPlatformTemplate && <div className="sm:col-span-2 grid gap-3 rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] p-4 text-sm sm:grid-cols-2"><div><div className="font-bold text-slate-950">Grading bands</div><div className="mt-2 text-slate-600">{selectedPlatformTemplate.grading_bands.map((band) => `${band.grade}: ${band.minimum_score}-${band.maximum_score}`).join(' • ')}</div></div><div><div className="font-bold text-slate-950">Personal development</div><div className="mt-2 text-slate-600">{selectedPlatformTemplate.traits.map((trait) => trait.name).join(' • ')}</div></div></div>}
                        {selectedSchoolTemplate && <div className="sm:col-span-2 border-t border-slate-200 pt-4"><div className="flex items-start justify-between gap-4"><div><h3 className="text-sm font-semibold text-slate-950">Term calculation policy</h3><p className="mt-1 text-xs text-slate-500">Choose whether this class group reports each term separately or carries previous terms into the current result.</p></div></div><div className="mt-3 grid gap-3 sm:grid-cols-2"><button type="button" onClick={() => policyForm.setData('term_result_mode', 'independent')} className={['border p-3 text-left text-sm transition', policyForm.data.term_result_mode === 'independent' ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]' : 'border-slate-200 text-slate-700'].join(' ')}><span className="block font-semibold">Independent terms</span><span className="mt-1 block text-xs opacity-75">Every term stands on its own.</span></button><button type="button" onClick={() => policyForm.setData('term_result_mode', 'cumulative')} className={['border p-3 text-left text-sm transition', policyForm.data.term_result_mode === 'cumulative' ? 'border-[var(--brand-primary)] bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]' : 'border-slate-200 text-slate-700'].join(' ')}><span className="block font-semibold">Cumulative session</span><span className="mt-1 block text-xs opacity-75">Current reports include earlier term percentages.</span></button></div>{policyForm.data.term_result_mode === 'cumulative' && <div className="mt-3 grid gap-3 sm:grid-cols-3">{['First term weight', 'Second term weight', 'Third term weight'].map((label, index) => <div key={label}><InputLabel value={label} /><input type="number" min="0.01" max="100" step="0.01" value={policyForm.data.term_weights[index] ?? 1} onChange={(event) => { const weights = [...policyForm.data.term_weights]; weights[index] = event.target.value; policyForm.setData('term_weights', weights); }} className="mt-1 block w-full rounded-md border-slate-300 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /></div>)}</div>}<InputError message={policyForm.errors.term_result_mode || policyForm.errors.term_weights} className="mt-2" /><div className="mt-3 flex justify-end"><button type="button" onClick={submitPolicy} disabled={policyForm.processing} className="rounded-md bg-[var(--brand-primary)] px-3 py-2 text-xs font-semibold text-white disabled:opacity-50">Save calculation policy</button></div></div>}
                    </div>
                    <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4"><button type="button" onClick={() => setShowConfiguration(false)} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Close</button>{canConfigure && <PrimaryButton disabled={selectionForm.processing || !selectedPlatformTemplate}>Apply template</PrimaryButton>}</div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
