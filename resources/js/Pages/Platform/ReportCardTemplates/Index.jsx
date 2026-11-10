import DataTable from '@/Components/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableMultiSelect from '@/Components/SearchableMultiSelect';
import SearchableSelect from '@/Components/SearchableSelect';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';

const defaultBands = [
    { minimum_score: 70, maximum_score: 100, grade: 'A', remark: 'Excellent' },
    { minimum_score: 60, maximum_score: 69.99, grade: 'B', remark: 'Very Good' },
    { minimum_score: 50, maximum_score: 59.99, grade: 'C', remark: 'Good' },
    { minimum_score: 45, maximum_score: 49.99, grade: 'D', remark: 'Fair' },
    { minimum_score: 40, maximum_score: 44.99, grade: 'E', remark: 'Pass' },
    { minimum_score: 0, maximum_score: 39.99, grade: 'F', remark: 'Needs Improvement' },
];
const defaultTraits = [
    { name: 'Character', rating_options: ['Excellent', 'Very Good', 'Good', 'Fair', 'Needs Improvement'] },
    { name: 'Punctuality', rating_options: ['Excellent', 'Very Good', 'Good', 'Fair', 'Needs Improvement'] },
];

function Toggle({ checked, label, onChange }) {
    return <label className="flex cursor-pointer items-center justify-between gap-4 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700"><span>{label}</span><button type="button" onClick={() => onChange(!checked)} className={['relative h-6 w-11 rounded-full transition', checked ? 'bg-emerald-600' : 'bg-slate-300'].join(' ')} aria-pressed={checked}><span className={['absolute top-1 h-4 w-4 rounded-full bg-white transition', checked ? 'left-6' : 'left-1'].join(' ')} /></button></label>;
}

export default function Index({ templates, educationLevels, schools, accesses }) {
    const [showEditor, setShowEditor] = useState(false);
    const [showGrant, setShowGrant] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [grantTemplate, setGrantTemplate] = useState(null);
    const form = useForm({
        name: '', education_levels: [], term_result_mode: 'independent', is_active: true,
        layout: { show_position: true, show_class_statistics: true, show_attendance: true, show_promotion_decision: true },
        grading_bands: defaultBands,
        traits: defaultTraits,
    });
    const grantForm = useForm({ school_id: '', payment_reference: '', amount: '', payment_note: '' });

    const openCreate = () => {
        setEditingId(null);
        form.setData({ name: '', education_levels: [], term_result_mode: 'independent', is_active: true, layout: { show_position: true, show_class_statistics: true, show_attendance: true, show_promotion_decision: true }, grading_bands: defaultBands, traits: defaultTraits });
        form.clearErrors();
        setShowEditor(true);
    };
    const openEdit = (template) => {
        setEditingId(template.id);
        form.setData({ name: template.name, education_levels: template.education_levels ?? [template.education_level], term_result_mode: template.term_result_mode, is_active: template.is_active, layout: { show_position: true, show_class_statistics: true, show_attendance: true, show_promotion_decision: true, ...(template.layout ?? {}) }, grading_bands: template.grading_bands, traits: template.traits });
        form.clearErrors();
        setShowEditor(true);
    };
    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setShowEditor(false) };
        editingId ? form.put(route('platform.report-card-templates.update', editingId), options) : form.post(route('platform.report-card-templates.store'), options);
    };
    const updateBand = (index, key, value) => form.setData('grading_bands', form.data.grading_bands.map((band, bandIndex) => bandIndex === index ? { ...band, [key]: value } : band));
    const updateTrait = (index, key, value) => form.setData('traits', form.data.traits.map((trait, traitIndex) => traitIndex === index ? { ...trait, [key]: value } : trait));
    const openGrant = (template) => {
        setGrantTemplate(template);
        grantForm.reset();
        grantForm.clearErrors();
        setShowGrant(true);
    };
    const submitGrant = (event) => {
        event.preventDefault();
        grantForm.post(route('platform.report-card-templates.accesses.store', grantTemplate.id), { preserveScroll: true, onSuccess: () => setShowGrant(false) });
    };

    return (
        <AuthenticatedLayout title="Report-card templates" subtitle="Configure the approved report formats available to tenant schools." actions={<button type="button" onClick={openCreate} className="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-700"><Plus className="h-4 w-4" />New template</button>}>
            <Head title="Platform report-card templates" />
            <DataTable columns={[
                { key: 'name', label: 'Template', className: 'font-bold text-slate-950' },
                { key: 'education_levels', label: 'Education levels', render: (template) => (template.education_levels ?? [template.education_level]).map((level) => level.charAt(0).toUpperCase() + level.slice(1)).join(', ') },
                { key: 'term_result_mode', label: 'Term mode', className: 'capitalize' },
                { key: 'traits', label: 'Traits', render: (template) => template.traits.length },
                { key: 'grading_bands', label: 'Grades', render: (template) => template.grading_bands.length },
                { key: 'accesses_count', label: 'Schools with access' },
                { key: 'status', label: 'Status', render: (template) => <span className={template.is_active ? 'rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700' : 'rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600'}>{template.is_active ? 'Active' : 'Inactive'}</span> },
                { key: 'actions', label: '', sortable: false, render: (template) => <div className="flex justify-end gap-2"><button type="button" onClick={() => openGrant(template)} className="grid h-8 w-8 place-items-center rounded-md border border-emerald-100 bg-emerald-50 text-emerald-700 hover:bg-emerald-100" aria-label={`Grant ${template.name} to school`} title="Record offline payment and grant access"><KeyRound className="h-4 w-4" /></button><button type="button" onClick={() => openEdit(template)} className="grid h-8 w-8 place-items-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50" aria-label={`Edit ${template.name}`}><Pencil className="h-4 w-4" /></button><button type="button" onClick={() => router.delete(route('platform.report-card-templates.destroy', template.id), { preserveScroll: true })} className="grid h-8 w-8 place-items-center rounded-md border border-red-100 text-red-600 hover:bg-red-50" aria-label={`Delete ${template.name}`}><Trash2 className="h-4 w-4" /></button></div> },
            ]} rows={templates} emptyLabel="No platform report-card templates yet." searchPlaceholder="Search templates" />

            <section className="mt-6"><div className="mb-3"><h2 className="text-sm font-bold text-slate-950">Active school access</h2><p className="mt-1 text-sm text-slate-500">Offline payments recorded and templates currently available to schools.</p></div><DataTable columns={[{ key: 'school', label: 'School', render: (access) => access.school?.name, className: 'font-semibold text-slate-950' }, { key: 'template', label: 'Template', render: (access) => access.platform_template?.name }, { key: 'payment_reference', label: 'Payment reference', render: (access) => access.payment_reference || 'Not provided' }, { key: 'amount', label: 'Amount', render: (access) => access.amount ? `NGN ${Number(access.amount).toLocaleString()}` : 'Not recorded' }, { key: 'granted_at', label: 'Granted', render: (access) => access.granted_at ? new Date(access.granted_at).toLocaleDateString() : '—' }, { key: 'actions', label: '', sortable: false, render: (access) => <button type="button" onClick={() => router.delete(route('platform.report-card-template-accesses.destroy', access.id), { preserveScroll: true })} className="rounded-md border border-red-100 px-2.5 py-1.5 text-xs font-bold text-red-600 hover:bg-red-50">Revoke</button> }]} rows={accesses} emptyLabel="No school has been granted a paid template yet." searchPlaceholder="Search school access" /></section>

            <Modal show={showEditor} closeable={false} maxWidth="4xl">
                <form onSubmit={submit} className="max-h-[92vh] overflow-y-auto">
                    <div className="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4"><div><h2 className="text-base font-bold text-slate-950">{editingId ? 'Edit report-card template' : 'New report-card template'}</h2><p className="mt-1 text-sm text-slate-500">This configuration controls what schools can select and what appears during report preparation.</p></div><button type="button" onClick={() => setShowEditor(false)} className="grid h-8 w-8 place-items-center rounded-md text-slate-400 hover:bg-slate-100" aria-label="Close"><X className="h-4 w-4" /></button></div>
                    <div className="grid gap-6 p-5">
                        <section className="grid gap-4 sm:grid-cols-2"><div><InputLabel value="Template name" /><input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-sm" /><InputError message={form.errors.name} className="mt-1" /></div><div><InputLabel value="Education levels" /><SearchableMultiSelect id="education-levels" selectedIds={form.data.education_levels} options={educationLevels} searchPlaceholder="Search and select levels" onChange={(ids) => form.setData('education_levels', ids)} /><InputError message={form.errors.education_levels} className="mt-1" /></div><div><InputLabel value="Term result mode" /><SearchableSelect value={form.data.term_result_mode} options={[{ id: 'independent', name: 'Independent terms' }, { id: 'cumulative', name: 'Cumulative session' }]} searchPlaceholder="Search modes" placeholder="Select mode" onChange={(id) => form.setData('term_result_mode', id)} /></div><Toggle checked={form.data.is_active} label="Available to schools" onChange={(value) => form.setData('is_active', value)} /></section>

                        <section><h3 className="text-sm font-bold text-slate-950">Visible report sections</h3><div className="mt-3 grid gap-2 sm:grid-cols-2">{Object.entries({ show_position: 'Student position', show_class_statistics: 'Class average, highest and lowest', show_attendance: 'Attendance summary', show_promotion_decision: 'Promotion decision' }).map(([key, label]) => <Toggle key={key} checked={form.data.layout[key]} label={label} onChange={(value) => form.setData('layout', { ...form.data.layout, [key]: value })} />)}</div></section>

                        <section><div className="flex items-center justify-between"><div><h3 className="text-sm font-bold text-slate-950">Grading bands</h3><p className="mt-1 text-xs text-slate-500">Configure percentage ranges, grades, and remarks.</p></div><button type="button" onClick={() => form.setData('grading_bands', [...form.data.grading_bands, { minimum_score: 0, maximum_score: 0, grade: '', remark: '' }])} className="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700"><Plus className="h-3.5 w-3.5" />Add band</button></div><div className="mt-3 space-y-2">{form.data.grading_bands.map((band, index) => <div key={index} className="grid gap-2 rounded-md border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[1fr_1fr_1fr_2fr_auto]"><input type="number" step="0.01" value={band.minimum_score} onChange={(event) => updateBand(index, 'minimum_score', event.target.value)} placeholder="Minimum" className="rounded-md border-slate-300 text-sm" /><input type="number" step="0.01" value={band.maximum_score} onChange={(event) => updateBand(index, 'maximum_score', event.target.value)} placeholder="Maximum" className="rounded-md border-slate-300 text-sm" /><input value={band.grade} onChange={(event) => updateBand(index, 'grade', event.target.value)} placeholder="Grade" className="rounded-md border-slate-300 text-sm" /><input value={band.remark ?? ''} onChange={(event) => updateBand(index, 'remark', event.target.value)} placeholder="Remark" className="rounded-md border-slate-300 text-sm" /><button type="button" onClick={() => form.setData('grading_bands', form.data.grading_bands.filter((_, bandIndex) => bandIndex !== index))} className="grid h-9 w-9 place-items-center rounded-md text-red-600 hover:bg-red-50" aria-label="Remove grade band"><Trash2 className="h-4 w-4" /></button></div>)}</div><InputError message={form.errors.grading_bands} className="mt-2" /></section>

                        <section><div className="flex items-center justify-between"><div><h3 className="text-sm font-bold text-slate-950">Personal-development traits</h3><p className="mt-1 text-xs text-slate-500">Each trait has its own selectable rating options.</p></div><button type="button" onClick={() => form.setData('traits', [...form.data.traits, { name: '', rating_options: ['Excellent', 'Good'] }])} className="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700"><Plus className="h-3.5 w-3.5" />Add trait</button></div><div className="mt-3 space-y-2">{form.data.traits.map((trait, index) => <div key={index} className="grid gap-2 rounded-md border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[1fr_2fr_auto]"><input value={trait.name} onChange={(event) => updateTrait(index, 'name', event.target.value)} placeholder="Trait name" className="rounded-md border-slate-300 text-sm" /><input value={trait.rating_options.join(', ')} onChange={(event) => updateTrait(index, 'rating_options', event.target.value.split(',').map((option) => option.trim()))} placeholder="Excellent, Very Good, Good" className="rounded-md border-slate-300 text-sm" /><button type="button" onClick={() => form.setData('traits', form.data.traits.filter((_, traitIndex) => traitIndex !== index))} className="grid h-9 w-9 place-items-center rounded-md text-red-600 hover:bg-red-50" aria-label="Remove trait"><Trash2 className="h-4 w-4" /></button></div>)}</div><InputError message={form.errors.traits} className="mt-2" /></section>
                    </div>
                    <div className="sticky bottom-0 flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4"><button type="button" onClick={() => setShowEditor(false)} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button><PrimaryButton disabled={form.processing}>Save template</PrimaryButton></div>
                </form>
            </Modal>

            <Modal show={showGrant} closeable={false} maxWidth="lg">
                <form onSubmit={submitGrant}>
                    <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4"><div><h2 className="text-base font-bold text-slate-950">Grant paid template</h2><p className="mt-1 text-sm text-slate-500">Record the offline payment and make {grantTemplate?.name} available to a school.</p></div><button type="button" onClick={() => setShowGrant(false)} className="grid h-8 w-8 place-items-center rounded-md text-slate-400 hover:bg-slate-100" aria-label="Close"><X className="h-4 w-4" /></button></div>
                    <div className="grid gap-4 p-5"><div><InputLabel value="School" /><SearchableSelect value={grantForm.data.school_id} options={schools.map((school) => ({ id: school.id, name: `${school.name} • ${school.code}` }))} searchPlaceholder="Search approved schools" placeholder="Select school" onChange={(id) => grantForm.setData('school_id', id)} /><InputError message={grantForm.errors.school_id} className="mt-1" /></div><div className="grid gap-4 sm:grid-cols-2"><div><InputLabel value="Offline payment reference" /><input value={grantForm.data.payment_reference} onChange={(event) => grantForm.setData('payment_reference', event.target.value)} placeholder="Receipt or transfer reference" className="mt-1 block w-full rounded-md border-slate-300 text-sm" /></div><div><InputLabel value="Amount paid (NGN)" /><input type="number" min="0" step="0.01" value={grantForm.data.amount} onChange={(event) => grantForm.setData('amount', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-sm" /></div></div><div><InputLabel value="Payment note" /><textarea value={grantForm.data.payment_note} onChange={(event) => grantForm.setData('payment_note', event.target.value)} rows="3" placeholder="How and when payment was confirmed" className="mt-1 block w-full rounded-md border-slate-300 text-sm" /></div></div>
                    <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4"><button type="button" onClick={() => setShowGrant(false)} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">Cancel</button><PrimaryButton disabled={grantForm.processing}>Confirm payment and grant</PrimaryButton></div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
