import SearchableSelect from '@/Components/SearchableSelect';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, ChevronLeft, ChevronRight, FileText, Printer } from 'lucide-react';
import { useState } from 'react';

function studentName(student) {
    return [student?.first_name, student?.last_name].filter(Boolean).join(' ');
}

function armLabel(arm) {
    return [arm?.school_class?.class_group?.name, arm?.school_class?.name, arm?.name].filter(Boolean).join(' • ');
}

function positionLabel(position) {
    if (!position) {
        return '—';
    }

    const suffix = position % 10 === 1 && position % 100 !== 11
        ? 'st'
        : position % 10 === 2 && position % 100 !== 12
            ? 'nd'
            : position % 10 === 3 && position % 100 !== 13
                ? 'rd'
                : 'th';

    return `${position}${suffix}`;
}

function decisionLabel(decision) {
    return {
        pending: 'Pending decision',
        promoted: 'Promoted',
        repeated: 'Repeat class',
        graduated: 'Graduated',
        withdrawn: 'Withdrawn',
    }[decision] ?? decision;
}

function formatScore(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const number = Number(value ?? 0);

    return Number.isInteger(number) ? number : number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
}

function parsedRatingScaleEntry(value) {
    const prefixedRating = String(value ?? '').match(/^\s*([A-Za-z0-9]+)\s*[-–:]\s*(.+)$/);

    if (prefixedRating) {
        return { code: prefixedRating[1].toUpperCase(), description: prefixedRating[2].trim() };
    }

    return null;
}

function buildRatingScale(ratings) {
    const options = ratings.flatMap((rating) => rating.trait_definition?.rating_options ?? []);
    const codedEntries = options.map(parsedRatingScaleEntry).filter(Boolean);
    const entriesByCode = new Map(codedEntries.map((entry) => [entry.code, entry]));

    options.filter((option) => !parsedRatingScaleEntry(option)).forEach((option) => {
        const alreadyDefined = [...entriesByCode.values()].some(
            (entry) => entry.description.toLowerCase() === String(option).toLowerCase(),
        );

        if (!alreadyDefined) {
            const nextCode = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('').find((code) => !entriesByCode.has(code));

            if (nextCode) {
                entriesByCode.set(nextCode, { code: nextCode, description: String(option) });
            }
        }
    });

    return [...entriesByCode.values()].sort((left, right) => left.code.localeCompare(right.code));
}

function ratingCode(value, ratingScale) {
    const parsedEntry = parsedRatingScaleEntry(value);

    if (parsedEntry) {
        return parsedEntry.code;
    }

    const matchingEntry = ratingScale.find(
        (entry) => entry.description.toLowerCase() === String(value ?? '').toLowerCase(),
    );

    return matchingEntry?.code ?? String(value ?? '—');
}

export default function Preview({ run, cards, mode, allPrepared, templates, selectedTemplate, schoolProfile }) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const card = cards[currentIndex];
    const snapshot = card?.calculated_snapshot ?? {};
    const summary = snapshot.summary ?? {};
    const subjects = snapshot.subjects ?? [];
    const assessmentColumns = subjects.reduce((columns, subject) => {
        (subject.assessment_items ?? []).forEach((item) => {
            const key = `${item.name.toLowerCase()}-${item.raw_max_score}-${item.weight}`;

            if (!columns.some((column) => column.key === key)) {
                columns.push({ key, name: item.name, raw_max_score: item.raw_max_score, weight: item.weight });
            }
        });

        return columns;
    }, []);
    const cumulativeTerms = subjects.flatMap((subject) => subject.term_results ?? []).filter((term, index, terms) => terms.findIndex((candidate) => Number(candidate.term_id) === Number(term.term_id)) === index);
    const gradingBands = selectedTemplate.grading_scheme?.bands ?? [];
    const ratingScale = buildRatingScale(card?.ratings ?? []);
    const layout = {
        show_position: true,
        show_class_statistics: true,
        show_attendance: true,
        show_promotion_decision: true,
        ...(selectedTemplate.layout ?? {}),
    };

    const changeTemplate = (templateId) => {
        router.get(route('school.report-cards.preview', run.id), {
            mode,
            card: mode === 'student' ? card?.id : undefined,
            template: templateId,
        }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            title="Report-card preview"
            subtitle={`${armLabel(run.class_arm)} • ${run.academic_session?.name} • ${run.term?.name}`}
            actions={<div className="flex flex-wrap items-center justify-end gap-2 print:hidden"><a href={route('school.report-cards.pdf', { reportCardRun: run.id, card: card?.id, template: selectedTemplate.id })} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95"><FileText className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />Preview PDF</a><button type="button" onClick={() => window.print()} className="inline-flex items-center gap-2 rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-2 text-sm font-bold text-[var(--brand-primary)] shadow-sm transition hover:brightness-95"><Printer className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />Print page</button><Link href={route('school.report-cards.show', run.id)} className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50"><ArrowLeft className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />Back to preparation</Link></div>}
        >
            <Head title={`${studentName(card?.student)} report card`} />
            <style>{`@media print { body * { visibility: hidden !important; } #report-card-preview, #report-card-preview * { visibility: visible !important; } #report-card-preview { position: absolute; inset: 0; width: 100%; box-shadow: none !important; border: 0 !important; } }`}</style>

            <div className="mx-auto max-w-7xl">
                {mode === 'all' && !allPrepared && <div className="mb-4 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 print:hidden"><AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" strokeWidth={2.25} /><div><span className="font-bold">Reviewing an incomplete class run.</span> Prepared cards display normally; incomplete cards are marked Draft so you can identify what still needs attention.</div></div>}
                <div className="mb-4 grid gap-3 rounded-lg border border-slate-200 bg-white p-3 shadow-sm print:hidden md:grid-cols-[minmax(15rem,1fr)_auto] md:items-end">
                    <div><div className="mb-1 text-xs font-bold uppercase tracking-wide text-slate-500">Preview template</div><SearchableSelect value={selectedTemplate.id} options={templates.map((template) => ({ id: template.id, name: `${template.name}${template.id === run.report_card_template_id ? ' • Default' : ''}` }))} searchPlaceholder="Search report-card templates" placeholder="No compatible templates" onChange={changeTemplate} /></div>
                    {mode === 'all' && <div className="flex items-center justify-between gap-3 md:justify-end"><button type="button" onClick={() => setCurrentIndex((index) => Math.max(0, index - 1))} disabled={currentIndex === 0} className="grid h-10 w-10 place-items-center rounded-md border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Previous student"><ChevronLeft className="h-5 w-5" aria-hidden="true" /></button><div className="min-w-36 text-center"><div className="text-sm font-bold text-slate-900">{studentName(card?.student)}</div><div className="text-xs font-medium text-slate-500">{currentIndex + 1} of {cards.length}</div></div><button type="button" onClick={() => setCurrentIndex((index) => Math.min(cards.length - 1, index + 1))} disabled={currentIndex === cards.length - 1} className="grid h-10 w-10 place-items-center rounded-md border border-slate-200 text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Next student"><ChevronRight className="h-5 w-5" aria-hidden="true" /></button></div>}
                </div>

                {card && <article id="report-card-preview" className="relative mx-auto max-w-6xl overflow-hidden border border-slate-400 bg-white shadow-sm" style={{ fontFamily: 'var(--school-font, inherit)' }}>
                    {!['prepared', 'approved'].includes(card.status) && <div className="pointer-events-none absolute inset-0 z-10 grid place-items-center overflow-hidden"><div className="-rotate-12 text-8xl font-semibold uppercase text-slate-200/70">Draft</div></div>}
                    <header className="border-b border-slate-400 px-7 py-5 text-center">
                        <div className="flex items-center justify-center gap-4">{schoolProfile.logo_url ? <img src={schoolProfile.logo_url} alt={`${schoolProfile.name} logo`} className="h-16 w-16 shrink-0 bg-white object-contain" /> : <div className="grid h-16 w-16 shrink-0 place-items-center rounded-sm text-xl font-semibold text-white" style={{ backgroundColor: 'var(--brand-primary)' }}>{schoolProfile.name.charAt(0)}</div>}<div><h1 className="text-xl font-semibold uppercase text-[var(--brand-primary)]">{schoolProfile.name}</h1><p className="mt-1 text-[11px] font-normal text-slate-500">{[schoolProfile.address, schoolProfile.phone, schoolProfile.email].filter(Boolean).join(' • ')}</p></div></div>
                        <div className="mt-4 text-sm font-semibold uppercase text-slate-800">{run.term?.name} report sheet</div>
                        <div className="mt-1 text-[11px] font-medium text-slate-500">{selectedTemplate.name}</div>
                    </header>

                    <div className="grid gap-x-8 gap-y-1.5 border-b border-slate-400 bg-slate-50 px-7 py-3 text-xs sm:grid-cols-2"><div><span className="font-medium text-slate-500">Student:</span> <span className="font-semibold text-slate-950">{studentName(card.student)}</span></div><div><span className="font-medium text-slate-500">Admission no:</span> <span className="font-semibold text-slate-950">{card.student?.admission_no}</span></div><div><span className="font-medium text-slate-500">Class:</span> <span className="font-semibold text-slate-950">{armLabel(run.class_arm)}</span></div><div><span className="font-medium text-slate-500">Period:</span> <span className="font-semibold text-slate-950">{run.term?.name}, {run.academic_session?.name}</span></div></div>

                    <div className="p-7">
                        <section className="mb-5 grid grid-cols-2 gap-px overflow-hidden border border-slate-300 bg-slate-300 sm:grid-cols-5"><div className="bg-white px-3 py-2.5 text-center"><div className="text-[10px] font-medium uppercase text-slate-500">Total</div><div className="mt-1 text-base font-semibold text-slate-950">{formatScore(summary.total)}/{formatScore(summary.maximum_score)}</div></div><div className="bg-white px-3 py-2.5 text-center"><div className="text-[10px] font-medium uppercase text-slate-500">Percentage</div><div className="mt-1 text-base font-semibold text-slate-950">{formatScore(summary.average)}%</div></div>{layout.show_class_statistics && <div className="bg-white px-3 py-2.5 text-center"><div className="text-[10px] font-medium uppercase text-slate-500">Class average</div><div className="mt-1 text-base font-semibold text-slate-950">{formatScore(summary.class_average)}%</div></div>}{layout.show_position && <div className="bg-white px-3 py-2.5 text-center"><div className="text-[10px] font-medium uppercase text-slate-500">Position</div><div className="mt-1 text-base font-semibold text-slate-950">{positionLabel(summary.position)}</div></div>}{layout.show_promotion_decision && <div className="bg-white px-3 py-2.5 text-center"><div className="text-[10px] font-medium uppercase text-slate-500">Decision</div><div className="mt-1 text-sm font-semibold text-slate-950">{decisionLabel(card.promotion_decision)}</div></div>}</section>

                        <section><h2 className="mb-3 text-xs font-semibold uppercase text-slate-800">Academic performance</h2><div className="overflow-x-auto border border-slate-400"><table className="min-w-full divide-y divide-slate-300 text-xs"><thead className="bg-slate-100 text-left font-semibold uppercase text-slate-700"><tr><th className="whitespace-nowrap px-3 py-2.5">Subject</th>{assessmentColumns.map((column) => <th key={column.key} className="whitespace-nowrap px-2 py-2.5 text-center"><span className="block">{column.name}</span><span className="block text-[10px] font-semibold opacity-80">/{formatScore(column.raw_max_score)}{Number(column.raw_max_score) !== Number(column.weight) ? ` → ${formatScore(column.weight)}%` : ''}</span></th>)}<th className="whitespace-nowrap px-2 py-2.5 text-center">Total</th><th className="whitespace-nowrap px-2 py-2.5 text-center">%</th>{cumulativeTerms.map((term) => <th key={term.term_id} className="whitespace-nowrap px-2 py-2.5 text-center">{term.term_name} %</th>)}{cumulativeTerms.length > 0 && <th className="whitespace-nowrap px-2 py-2.5 text-center">Cumulative %</th>}{layout.show_class_statistics && <><th className="whitespace-nowrap px-2 py-2.5 text-center">Class avg. %</th><th className="whitespace-nowrap px-2 py-2.5 text-center">Class highest %</th><th className="whitespace-nowrap px-2 py-2.5 text-center">Class lowest %</th></>}{layout.show_position && <th className="whitespace-nowrap px-2 py-2.5 text-center">Position</th>}<th className="px-2 py-2.5 text-center">Grade</th><th className="min-w-28 px-3 py-2.5">Remark</th></tr></thead><tbody className="divide-y divide-slate-200">{subjects.map((subject) => <tr key={subject.class_term_subject_id}><td className="whitespace-nowrap px-3 py-2.5 font-semibold text-slate-900">{subject.name}</td>{assessmentColumns.map((column) => { const item = (subject.assessment_items ?? []).find((assessmentItem) => `${assessmentItem.name.toLowerCase()}-${assessmentItem.raw_max_score}-${assessmentItem.weight}` === column.key); return <td key={column.key} className="px-2 py-2.5 text-center text-slate-700">{item ? formatScore(item.raw_score) : '—'}</td>; })}<td className="px-2 py-2.5 text-center font-bold text-slate-900">{formatScore(subject.score)}/{formatScore(subject.maximum_score)}</td><td className="px-2 py-2.5 text-center text-slate-700">{formatScore(subject.percentage)}%</td>{cumulativeTerms.map((term) => <td key={term.term_id} className="px-2 py-2.5 text-center text-slate-700">{formatScore((subject.term_results ?? []).find((result) => Number(result.term_id) === Number(term.term_id))?.percentage)}%</td>)}{cumulativeTerms.length > 0 && <td className="px-2 py-2.5 text-center font-semibold text-slate-900">{formatScore(subject.cumulative_percentage)}%</td>}{layout.show_class_statistics && <><td className="px-2 py-2.5 text-center text-slate-700">{formatScore(subject.class_statistics?.class_average)}%</td><td className="px-2 py-2.5 text-center text-slate-700">{formatScore(subject.class_statistics?.highest)}%</td><td className="px-2 py-2.5 text-center text-slate-700">{formatScore(subject.class_statistics?.lowest)}%</td></>}{layout.show_position && <td className="px-2 py-2.5 text-center text-slate-700">{positionLabel(subject.position)}</td>}<td className="px-2 py-2.5 text-center font-semibold" style={{ color: 'var(--brand-primary)' }}>{subject.grade}</td><td className="px-3 py-2.5 text-slate-600">{subject.remark}</td></tr>)}</tbody></table></div></section>

                        <div className="mt-5 grid gap-5 md:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]"><section><h2 className="mb-3 text-xs font-semibold uppercase text-slate-800">Personal development</h2><div className="grid overflow-hidden border border-slate-300 text-sm sm:grid-cols-[minmax(0,1fr)_15rem]"><div>{card.ratings?.length ? card.ratings.map((rating) => <div key={rating.id} className="grid grid-cols-[1fr_3rem] border-b border-slate-200 last:border-0"><span className="px-3 py-2 font-medium text-slate-700">{rating.trait_definition?.name}</span><span className="border-l border-slate-200 px-3 py-2 text-center font-semibold" style={{ color: 'var(--brand-primary)' }}>{ratingCode(rating.rating, ratingScale)}</span></div>) : <div className="p-3 text-slate-500">No ratings entered.</div>}</div>{ratingScale.length > 0 && <div className="border-t border-slate-300 bg-slate-50 sm:border-l sm:border-t-0"><div className="border-b border-slate-300 px-3 py-2 text-[10px] font-semibold uppercase text-slate-600">Rating scale</div>{ratingScale.map((entry) => <div key={entry.code} className="grid grid-cols-[2.5rem_1fr] border-b border-slate-200 text-xs last:border-0"><strong className="border-r border-slate-200 px-3 py-2 text-center font-semibold text-slate-900">{entry.code}</strong><span className="px-3 py-2 text-slate-600">{entry.description}</span></div>)}</div>}</div></section>{layout.show_attendance && <section><h2 className="mb-3 text-xs font-semibold uppercase text-slate-800">Attendance</h2><div className="grid grid-cols-2 gap-px overflow-hidden border border-slate-300 bg-slate-300 text-sm"><div className="bg-white p-3"><span className="text-slate-500">School days:</span> <strong>{card.school_days_opened ?? '—'}</strong></div><div className="bg-white p-3"><span className="text-slate-500">Present:</span> <strong>{card.days_present ?? '—'}</strong></div><div className="bg-white p-3"><span className="text-slate-500">Absent:</span> <strong>{card.days_absent ?? '—'}</strong></div><div className="bg-white p-3"><span className="text-slate-500">Times late:</span> <strong>{card.times_late ?? '—'}</strong></div></div></section>}</div>

                        <section className="mt-5 grid gap-4 md:grid-cols-2"><div className="border border-slate-300 p-4"><h2 className="text-xs font-semibold uppercase tracking-wide text-slate-500">Class teacher comment</h2><p className="mt-2 min-h-12 text-sm leading-6 text-slate-800">{card.class_teacher_comment || '—'}</p></div><div className="border border-slate-300 p-4"><h2 className="text-xs font-semibold uppercase tracking-wide text-slate-500">Principal comment</h2><p className="mt-2 min-h-12 text-sm leading-6 text-slate-800">{card.principal_comment || '—'}</p></div></section>
                        {Object.keys(card.signature_snapshot ?? {}).length > 0 && <section className="mt-5 grid gap-6 border-t border-slate-300 pt-5 sm:grid-cols-2">{[['class_teacher', 'Class teacher'], ['principal', 'Principal']].map(([type, label]) => { const signer = card.signature_snapshot?.[type]; return <div key={type} className="text-center">{signer && card.signature_urls?.[type] ? <><img src={card.signature_urls[type]} alt={`${label} signature`} className="mx-auto h-14 max-w-48 object-contain" /><div className="mx-auto mt-1 w-48 border-t border-slate-500 pt-1"><div className="text-xs font-bold text-slate-900">{signer.name}</div><div className="text-[11px] text-slate-500">{signer.title}</div></div></> : <div className="mx-auto w-48 border-t border-slate-300 pt-1 text-xs text-slate-400">{label} signature unavailable</div>}</div>; })}</section>}
                        {gradingBands.length > 0 && <section className="mt-5 border border-slate-300 p-3"><h2 className="text-xs font-semibold uppercase text-slate-500">Grading key</h2><div className="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-700">{gradingBands.map((band) => <span key={band.id}><strong>{band.grade}</strong>: {formatScore(band.minimum_score)}–{formatScore(band.maximum_score)}{band.remark ? ` (${band.remark})` : ''}</span>)}</div></section>}
                        <footer className="mt-6 flex flex-col gap-2 border-t border-slate-300 pt-4 text-xs text-slate-500 sm:flex-row sm:justify-between"><span>School code: {schoolProfile.code}</span><span>Next term starts: {card.next_term_starts_on || 'To be announced'}</span></footer>
                    </div>
                </article>}

                {mode === 'all' && <div className="mt-4 flex justify-center gap-1.5 print:hidden">{cards.map((item, index) => <button key={item.id} type="button" onClick={() => setCurrentIndex(index)} className={['h-2.5 rounded-full transition', index === currentIndex ? 'w-7 bg-[var(--brand-primary)]' : 'w-2.5 bg-slate-300 hover:bg-slate-400'].join(' ')} aria-label={`Show ${studentName(item.student)}`} />)}</div>}
            </div>
        </AuthenticatedLayout>
    );
}
