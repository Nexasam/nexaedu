import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableSelect from '@/Components/SearchableSelect';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, CheckCircle2, Eye, FileText, Presentation, Save, UserRound } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

function studentName(student) {
    return [student?.first_name, student?.last_name].filter(Boolean).join(' ');
}

function armLabel(arm) {
    return [arm?.school_class?.class_group?.name, arm?.school_class?.name, arm?.name].filter(Boolean).join(' • ');
}

function initialData(card, traitDefinitions) {
    return {
        school_days_opened: card.school_days_opened ?? '',
        days_present: card.days_present ?? '',
        days_absent: card.days_absent ?? '',
        times_late: card.times_late ?? '',
        class_teacher_comment: card.class_teacher_comment ?? '',
        principal_comment: card.principal_comment ?? '',
        promotion_decision: card.promotion_decision ?? 'pending',
        next_term_starts_on: card.next_term_starts_on ?? '',
        ratings: traitDefinitions.map((trait) => ({
            trait_definition_id: trait.id,
            rating: card.ratings?.find((item) => Number(item.report_card_trait_definition_id) === Number(trait.id))?.rating ?? '',
        })),
    };
}

function missingPreparationRequirements(data) {
    const missing = [];
    const isBlank = (value) => value === '' || value === null || value === undefined;

    if (isBlank(data.school_days_opened)) {
        missing.push('school days opened');
    }

    if (isBlank(data.days_present)) {
        missing.push('days present');
    }

    if (isBlank(data.days_absent)) {
        missing.push('days absent');
    }

    if (!data.promotion_decision || data.promotion_decision === 'pending') {
        missing.push('promotion decision');
    }

    return missing;
}

export default function Show({ run, cards, traitDefinitions }) {
    const { auth } = usePage().props;
    const canPrepare = auth.permissions?.report_card_preparation === 'readwrite';
    const canApprove = auth.permissions?.report_card_approval === 'readwrite';
    const [selectedCardId, setSelectedCardId] = useState(cards[0]?.id ?? null);
    const selectedCard = useMemo(() => cards.find((card) => Number(card.id) === Number(selectedCardId)) ?? cards[0], [cards, selectedCardId]);
    const form = useForm(initialData(selectedCard ?? {}, traitDefinitions));
    const preparedCount = cards.filter((card) => ['prepared', 'approved'].includes(card.status)).length;
    const allPrepared = cards.length > 0 && preparedCount === cards.length;
    const missingRequirements = missingPreparationRequirements(form.data);

    useEffect(() => {
        if (selectedCard) {
            form.setData(initialData(selectedCard, traitDefinitions));
            form.clearErrors();
        }
    }, [selectedCardId]);

    const submit = (event) => {
        event.preventDefault();

        if (!selectedCard) {
            return;
        }

        form.put(route('school.report-cards.students.update', [run.id, selectedCard.id]), {
            preserveScroll: true,
        });
    };

    const setRating = (traitDefinitionId, rating) => {
        form.setData('ratings', form.data.ratings.map((item) => (
            Number(item.trait_definition_id) === Number(traitDefinitionId) ? { ...item, rating } : item
        )));
    };

    return (
        <AuthenticatedLayout
            title="Prepare report cards"
            subtitle={`${run.template?.name || 'Report card'} • ${run.academic_session?.name} • ${run.term?.name}`}
            actions={<div className="flex flex-wrap items-center justify-end gap-2">
                {selectedCard && <Link href={route('school.report-cards.preview', { reportCardRun: run.id, card: selectedCard.id })} className="inline-flex items-center gap-2 rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-2 text-sm font-bold text-[var(--brand-primary)] transition hover:brightness-95"><Eye className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />Preview student</Link>}
                <Link href={route('school.report-cards.preview', { reportCardRun: run.id, mode: 'all' })} title={allPrepared ? 'Preview all prepared report cards' : `Preview all; ${cards.length - preparedCount} card(s) will show as drafts`} className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95"><Presentation className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />Preview all</Link>
                {canApprove && allPrepared && run.status === 'draft' && <button type="button" onClick={() => router.post(route('school.report-cards.approval.store', run.id), {}, { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white"><CheckCircle2 className="h-4 w-4" />Approve class results</button>}
                {canApprove && run.status === 'approved' && <button type="button" onClick={() => router.delete(route('school.report-cards.approval.destroy', run.id), { preserveScroll: true })} className="inline-flex items-center gap-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">Revoke approval</button>}
                <Link href={route('school.report-cards.index')} className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><ArrowLeft className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />All report runs</Link>
            </div>}
        >
            <Head title={`Prepare ${armLabel(run.class_arm)} report cards`} />

            <section className="mb-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="flex items-start gap-3">
                        <span className="grid h-10 w-10 place-items-center rounded-lg bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]"><FileText className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} /></span>
                        <div>
                            <div className="text-sm font-bold text-slate-950">{armLabel(run.class_arm)}</div>
                            <p className="mt-1 text-sm text-slate-500">Subject scores and class positions are frozen in this draft. Add the attendance and school comments for each student.</p>
                        </div>
                    </div>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white px-5 py-4 shadow-sm md:min-w-44">
                    <div className="text-xs font-bold uppercase tracking-wide text-slate-500">Prepared</div>
                    <div className="mt-1 text-2xl font-bold text-slate-950">{preparedCount} <span className="text-sm font-semibold text-slate-400">of {cards.length}</span></div>
                </div>
            </section>

            {cards.length === 0 ? (
                <section className="rounded-lg border border-slate-200 bg-white p-8 text-center shadow-sm"><UserRound className="mx-auto h-7 w-7 text-slate-300" aria-hidden="true" /><p className="mt-3 text-sm font-semibold text-slate-600">No student cards were created for this report run.</p></section>
            ) : (
                <div className="grid gap-5 lg:grid-cols-[19rem_minmax(0,1fr)]">
                    <aside className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="px-2 pb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Students</div>
                        <div className="space-y-1">
                            {cards.map((card) => {
                                const selected = Number(card.id) === Number(selectedCard?.id);

                                return <button key={card.id} type="button" onClick={() => setSelectedCardId(card.id)} className={['flex w-full items-center justify-between gap-3 rounded-md px-3 py-2.5 text-left transition', selected ? 'bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]' : 'text-slate-700 hover:bg-slate-50'].join(' ')}>
                                    <span className="min-w-0"><span className="block truncate text-sm font-bold">{studentName(card.student)}</span><span className="mt-0.5 block text-xs font-medium text-slate-500">{card.student?.admission_no}</span></span>
                                    {['prepared', 'approved'].includes(card.status) ? <CheckCircle2 className="h-4 w-4 shrink-0 text-emerald-600" aria-label="Prepared" strokeWidth={2.25} /> : <span className="h-2.5 w-2.5 shrink-0 rounded-full bg-amber-400" aria-label="Draft" />}
                                </button>;
                            })}
                        </div>
                    </aside>

                    {selectedCard && <form onSubmit={submit} className="rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div><h2 className="text-base font-bold text-slate-950">{studentName(selectedCard.student)}</h2><p className="mt-1 text-sm text-slate-500">{selectedCard.student?.admission_no} • {['prepared', 'approved'].includes(selectedCard.status) ? 'Prepared card' : 'Draft card'}</p></div>
                            <span className={['prepared', 'approved'].includes(selectedCard.status) ? 'rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700' : 'rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700'}>{selectedCard.status}</span>
                        </div>

                        <div className="grid gap-5 p-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(18rem,.9fr)]">
                            <section>
                                <h3 className="text-sm font-bold text-slate-950">Academic summary</h3>
                                <div className="mt-3 overflow-x-auto rounded-lg border border-slate-200">
                                    <table className="min-w-full divide-y divide-slate-200 text-sm"><thead className="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th className="px-3 py-2.5">Subject</th><th className="px-3 py-2.5">Score</th><th className="px-3 py-2.5">Grade</th><th className="px-3 py-2.5">Remark</th></tr></thead><tbody className="divide-y divide-slate-100">{(selectedCard.calculated_snapshot?.subjects ?? []).map((subject) => <tr key={subject.class_term_subject_id}><td className="px-3 py-2.5 font-semibold text-slate-900">{subject.name}</td><td className="px-3 py-2.5 text-slate-600">{subject.score}/{subject.maximum_score}</td><td className="px-3 py-2.5 font-bold text-[var(--brand-primary)]">{subject.grade}</td><td className="px-3 py-2.5 text-slate-600">{subject.remark}</td></tr>)}</tbody></table>
                                </div>
                                <div className="mt-3 grid grid-cols-3 gap-3"><div className="rounded-lg bg-slate-50 p-3"><div className="text-xs font-bold uppercase tracking-wide text-slate-500">Total</div><div className="mt-1 text-lg font-bold text-slate-950">{selectedCard.calculated_snapshot?.summary?.total}/{selectedCard.calculated_snapshot?.summary?.maximum_score}</div></div><div className="rounded-lg bg-slate-50 p-3"><div className="text-xs font-bold uppercase tracking-wide text-slate-500">Average</div><div className="mt-1 text-lg font-bold text-slate-950">{selectedCard.calculated_snapshot?.summary?.average}%</div></div><div className="rounded-lg bg-slate-50 p-3"><div className="text-xs font-bold uppercase tracking-wide text-slate-500">Position</div><div className="mt-1 text-lg font-bold text-slate-950">{selectedCard.calculated_snapshot?.summary?.position}</div></div></div>
                            </section>

                            <section className="space-y-4"><div><h3 className="text-sm font-bold text-slate-950">Attendance and decision</h3><p className="mt-1 text-sm text-slate-500">Attendance totals and a promotion decision are needed to mark this card prepared.</p></div><div className="grid grid-cols-2 gap-3"><div><InputLabel value="School days opened" /><input type="number" min="0" value={form.data.school_days_opened} onChange={(event) => form.setData('school_days_opened', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /><InputError message={form.errors.school_days_opened} className="mt-1" /></div><div><InputLabel value="Days present" /><input type="number" min="0" value={form.data.days_present} onChange={(event) => form.setData('days_present', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /><InputError message={form.errors.days_present} className="mt-1" /></div><div><InputLabel value="Days absent" /><input type="number" min="0" value={form.data.days_absent} onChange={(event) => form.setData('days_absent', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /><InputError message={form.errors.days_absent} className="mt-1" /></div><div><InputLabel value="Times late" /><input type="number" min="0" value={form.data.times_late} onChange={(event) => form.setData('times_late', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /><InputError message={form.errors.times_late} className="mt-1" /></div></div><div><InputLabel value="Promotion decision" /><SearchableSelect value={form.data.promotion_decision} options={[{ id: 'pending', name: 'Pending decision' }, { id: 'promoted', name: 'Promoted' }, { id: 'repeated', name: 'Repeat class' }, { id: 'graduated', name: 'Graduated' }, { id: 'withdrawn', name: 'Withdrawn' }]} searchPlaceholder="Search decisions" placeholder="Select decision" onChange={(id) => form.setData('promotion_decision', id)} /><InputError message={form.errors.promotion_decision} className="mt-1" /></div><div><InputLabel value="Next term starts" /><input type="date" value={form.data.next_term_starts_on} onChange={(event) => form.setData('next_term_starts_on', event.target.value)} className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /><InputError message={form.errors.next_term_starts_on} className="mt-1" /></div>
                                {traitDefinitions.length > 0 && <div className="border-t border-slate-200 pt-4"><h3 className="text-sm font-bold text-slate-950">Personal development</h3><p className="mt-1 text-sm text-slate-500">Rate the traits configured for this class group.</p><div className="mt-3 grid gap-3 sm:grid-cols-2">{traitDefinitions.map((trait, index) => <div key={trait.id}><InputLabel value={trait.name} /><SearchableSelect value={form.data.ratings[index]?.rating ?? ''} options={(trait.rating_options ?? []).map((option) => ({ id: option, name: option }))} searchPlaceholder={`Search ${trait.name.toLowerCase()} ratings`} placeholder="Select rating" onChange={(rating) => setRating(trait.id, rating)} /><InputError message={form.errors[`ratings.${index}.rating`]} className="mt-1" /></div>)}</div><InputError message={form.errors.ratings} className="mt-2" /></div>}</section>
                        </div>
                        <div className="grid gap-4 border-t border-slate-200 p-5"><div><InputLabel value="Class teacher comment" /><textarea value={form.data.class_teacher_comment} onChange={(event) => form.setData('class_teacher_comment', event.target.value)} rows="3" className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /></div><div><InputLabel value="Principal comment" /><textarea value={form.data.principal_comment} onChange={(event) => form.setData('principal_comment', event.target.value)} rows="3" className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /></div></div>
                        {canPrepare && run.status === 'draft' && <div className="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            {missingRequirements.length > 0 ? <div className="flex items-start gap-2 text-sm text-amber-800"><AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} /><div><span className="font-bold">This card will remain a draft.</span> Still needed: {missingRequirements.join(', ')}.</div></div> : <div className="flex items-center gap-2 text-sm font-semibold text-emerald-700"><CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} />Ready to be marked as prepared when saved.</div>}
                            <PrimaryButton disabled={form.processing}><Save className="mr-2 h-4 w-4" aria-hidden="true" strokeWidth={2.25} />{missingRequirements.length > 0 ? 'Save as draft' : 'Save and mark prepared'}</PrimaryButton>
                        </div>}
                    </form>}
                </div>
            )}
        </AuthenticatedLayout>
    );
}
