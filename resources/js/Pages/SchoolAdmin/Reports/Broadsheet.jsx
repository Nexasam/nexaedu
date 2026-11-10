import SearchableSelect from '@/Components/SearchableSelect';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Download, Sheet } from 'lucide-react';
import { useMemo, useState } from 'react';

function runLabel(run) {
    return [run.class_arm?.school_class?.class_group?.name, run.class_arm?.school_class?.name, run.class_arm?.name, run.term?.name, run.academic_session?.name].filter(Boolean).join(' • ');
}

function ordinal(position) {
    if (!position) return '—';
    const suffix = position % 10 === 1 && position % 100 !== 11 ? 'st' : position % 10 === 2 && position % 100 !== 12 ? 'nd' : position % 10 === 3 && position % 100 !== 13 ? 'rd' : 'th';
    return `${position}${suffix}`;
}

export default function Broadsheet({ runs, selectedRun, subjects, rows }) {
    const [search, setSearch] = useState('');
    const filteredRows = useMemo(() => rows.filter((row) => `${row.student_name} ${row.admission_no}`.toLowerCase().includes(search.toLowerCase())), [rows, search]);
    const isCumulative = selectedRun?.template?.term_result_mode === 'cumulative';

    return (
        <AuthenticatedLayout
            title="Class broadsheet"
            subtitle="Review every student and subject in one controlled result ledger."
            actions={selectedRun && <a href={route('school.reports.broadsheet.export', selectedRun.id)} className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-3 py-2 text-sm font-semibold text-white shadow-sm"><Download className="h-4 w-4" />Download CSV</a>}
        >
            <Head title="Class broadsheet" />
            <div className="mb-4 grid gap-3 border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(18rem,1fr)_minmax(14rem,.5fr)] md:items-end">
                <div><label className="mb-1 block text-xs font-semibold uppercase text-slate-500">Report run</label><SearchableSelect value={selectedRun?.id ?? ''} options={runs.map((run) => ({ id: run.id, name: runLabel(run) }))} searchPlaceholder="Search class, term, or session" placeholder="No report runs available" onChange={(run) => router.get(route('school.reports.broadsheet'), { run }, { preserveState: true })} /></div>
                <div><label htmlFor="student-search" className="mb-1 block text-xs font-semibold uppercase text-slate-500">Find student</label><input id="student-search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Name or admission number" className="h-10 w-full rounded-md border-slate-300 text-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" /></div>
            </div>

            {!selectedRun ? <div className="grid min-h-64 place-items-center border border-dashed border-slate-300 bg-white text-center"><div><Sheet className="mx-auto h-8 w-8 text-slate-400" /><p className="mt-2 text-sm text-slate-500">Generate a report-card run to create its broadsheet.</p></div></div> : <div className="overflow-x-auto border border-slate-300 bg-white shadow-sm"><table className="min-w-max border-collapse text-xs"><thead className="bg-slate-100 text-slate-700"><tr><th className="sticky left-0 z-20 min-w-48 border-b border-r border-slate-300 bg-slate-100 px-3 py-2 text-left font-semibold">Student</th><th className="min-w-24 border-b border-r border-slate-300 px-3 py-2 text-left font-semibold">Admission no.</th>{subjects.map((subject) => <th key={subject.id} className="min-w-28 border-b border-r border-slate-300 px-3 py-2 text-center font-semibold">{subject.name}<span className="mt-0.5 block text-[10px] font-normal text-slate-500">{isCumulative ? 'Cumulative %' : 'Term %'}</span></th>)}<th className="min-w-24 border-b border-r border-slate-300 px-3 py-2 text-center font-semibold">Average</th><th className="min-w-20 border-b border-r border-slate-300 px-3 py-2 text-center font-semibold">Position</th><th className="min-w-24 border-b border-slate-300 px-3 py-2 text-center font-semibold">Status</th></tr></thead><tbody>{filteredRows.map((row) => <tr key={row.student_id} className="odd:bg-white even:bg-slate-50/60"><td className="sticky left-0 z-10 border-b border-r border-slate-200 bg-inherit px-3 py-2.5 font-semibold text-slate-900">{row.student_name}</td><td className="border-b border-r border-slate-200 px-3 py-2.5 text-slate-600">{row.admission_no}</td>{row.subjects.map((subject) => <td key={subject.subject_id} className={['border-b border-r border-slate-200 px-3 py-2.5 text-center', subject.complete ? 'text-slate-700' : 'bg-red-50 text-red-700'].join(' ')}>{subject.complete ? (isCumulative ? subject.cumulative_percentage ?? subject.percentage : subject.percentage) : 'Missing'}</td>)}<td className="border-b border-r border-slate-200 px-3 py-2.5 text-center font-semibold text-slate-900">{isCumulative ? row.cumulative_average ?? row.overall_percentage : row.overall_percentage}%</td><td className="border-b border-r border-slate-200 px-3 py-2.5 text-center font-semibold">{ordinal(row.position)}</td><td className="border-b border-slate-200 px-3 py-2.5 text-center"><span className={row.card_status === 'approved' ? 'text-emerald-700' : row.card_status === 'prepared' ? 'text-blue-700' : 'text-amber-700'}>{row.card_status}</span></td></tr>)}</tbody></table></div>}
        </AuthenticatedLayout>
    );
}
