import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BookOpen,
    CheckCircle2,
    Circle,
    ClipboardList,
    GraduationCap,
    Layers3,
    School,
    Settings,
    ShieldCheck,
    Sparkles,
    UsersRound,
} from 'lucide-react';

function StatCard({ icon: Icon, label, value, helper, active }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm ring-1 ring-slate-100">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <div className="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">{label}</div>
                    <div className="mt-1 text-2xl font-black text-slate-950">{value}</div>
                </div>
                <span
                    className="grid h-8 w-8 place-items-center rounded-md"
                    style={{
                        backgroundColor: active ? 'var(--brand-primary-soft)' : '#f8fafc',
                        color: active ? 'var(--brand-primary)' : '#94a3b8',
                    }}
                >
                    <Icon className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                </span>
            </div>
            <div className="mt-2 flex items-center gap-2 text-xs text-slate-500">
                {active ? (
                    <CheckCircle2 className="h-4 w-4 text-emerald-600" aria-hidden="true" strokeWidth={2.25} />
                ) : (
                    <Circle className="h-4 w-4 text-slate-300" aria-hidden="true" strokeWidth={2.25} />
                )}
                <span>{helper}</span>
            </div>
        </div>
    );
}

function QuickAction({ href, icon: Icon, title, description, primary = false }) {
    return (
        <Link
            href={href}
            className={[
                'group flex items-center gap-3 rounded-lg border p-3 shadow-sm transition',
                primary
                    ? 'border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] hover:brightness-95'
                    : 'border-slate-200 bg-white hover:border-[var(--brand-primary-border)] hover:bg-[var(--brand-primary-soft)]',
            ].join(' ')}
        >
            <span
                className="grid h-9 w-9 shrink-0 place-items-center rounded-md text-white"
                style={{ backgroundColor: primary ? 'var(--brand-primary)' : 'var(--brand-secondary)' }}
            >
                <Icon className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-bold text-slate-950">{title}</span>
                <span className="mt-0.5 block text-xs leading-5 text-slate-500">{description}</span>
            </span>
            <ArrowRight className="h-4 w-4 shrink-0 text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-[var(--brand-primary)]" aria-hidden="true" strokeWidth={2.25} />
        </Link>
    );
}

export default function Dashboard({ school, setup = {} }) {
    const primaryDomain = school?.domains?.find((domain) => domain.is_primary);
    const setupItems = [
        { label: 'Class groups', value: setup.classGroups ?? 0, icon: Layers3, helper: 'School levels' },
        { label: 'Classes', value: setup.classes ?? 0, icon: BookOpen, helper: 'Class list' },
        { label: 'Class arms', value: setup.classArms ?? 0, icon: School, helper: 'Teaching arms' },
        { label: 'Academic sessions', value: setup.sessions ?? 0, icon: ClipboardList, helper: 'School years' },
        { label: 'School subjects', value: setup.subjects ?? 0, icon: Sparkles, helper: 'Approved subjects' },
        { label: 'Assessments', value: setup.assessments ?? 0, icon: ClipboardList, helper: 'Score structure' },
        { label: 'Staff', value: setup.staff ?? 0, icon: UsersRound, helper: 'Teachers and staff' },
        { label: 'Students', value: setup.students ?? 0, icon: GraduationCap, helper: 'Learner records' },
    ];
    const completedSetupItems = setupItems.filter((item) => item.value > 0).length;
    const setupPercent = Math.round((completedSetupItems / setupItems.length) * 100);
    const academicComplete = ['classGroups', 'classes', 'classArms', 'sessions', 'subjects']
        .every((key) => (setup[key] ?? 0) > 0);
    const assessmentComplete = (setup.assessments ?? 0) > 0;
    const nextStep = academicComplete && !assessmentComplete
        ? {
            title: 'Create assessment flows',
            description: 'Set the score structure for class-arm subjects before score entry starts.',
            href: route('school.assessments.index'),
            label: 'Open assessment setup',
        }
        : academicComplete
        ? {
            title: 'Add teachers and students',
            description: 'Your academic structure has started. Continue by registering staff and student records.',
            href: route('school.staff-setup.index'),
            label: 'Open staff setup',
        }
        : {
            title: 'Complete academic setup',
            description: 'Create class arms, sessions, terms, and school subjects before registration gets busy.',
            href: route('school.academic-setup.index'),
            label: 'Open academic setup',
        };

    return (
        <AuthenticatedLayout
            title={school?.name ?? 'School dashboard'}
            subtitle="Structure, people, and launch readiness."
            actions={
                <Link
                    href={nextStep.href}
                    className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-3 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95"
                >
                    {nextStep.label}
                    <ArrowRight className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                </Link>
            }
        >
            <Head title="Dashboard" />

            <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
                <section className="overflow-hidden rounded-xl border border-[var(--brand-primary-border)] bg-white shadow-sm ring-1 ring-slate-100">
                    <div className="relative p-4">
                        <div
                            className="pointer-events-none absolute inset-y-0 right-0 hidden w-1/2 sm:block"
                            style={{
                                background: 'linear-gradient(135deg, transparent 0%, var(--brand-primary-soft) 45%, rgba(255,255,255,0) 100%)',
                            }}
                        />
                        <div className="relative grid gap-4 lg:grid-cols-[minmax(0,1fr)_160px] lg:items-center">
                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-[var(--brand-primary)]">
                                    <ShieldCheck className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={2.25} />
                                    Approved tenant
                                </div>
                                <h2 className="mt-3 text-2xl font-black tracking-tight text-slate-950">
                                    {school?.name}
                                </h2>
                                <div className="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                        <span className="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Code</span>
                                        <span className="block font-bold text-slate-900">{school?.code}</span>
                                    </div>
                                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                        <span className="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Status</span>
                                        <span className="block font-bold capitalize text-slate-900">{school?.status}</span>
                                    </div>
                                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                        <span className="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Domain</span>
                                        <span className="block truncate font-bold text-slate-900">{primaryDomain?.domain ?? 'Domain pending'}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-white/90 p-3 shadow-sm">
                                <div className="mx-auto grid h-28 w-28 place-items-center rounded-full" style={{ background: `conic-gradient(var(--brand-primary) ${setupPercent}%, #e2e8f0 0)` }}>
                                    <div className="grid h-20 w-20 place-items-center rounded-full bg-white text-center shadow-inner">
                                        <div>
                                            <div className="text-2xl font-black text-slate-950">{setupPercent}%</div>
                                            <div className="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Ready</div>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 text-center text-xs font-semibold text-slate-600">
                                    {completedSetupItems} of {setupItems.length} areas started
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <aside className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-100">
                    <div className="flex items-center gap-3">
                        <span className="grid h-9 w-9 place-items-center rounded-md bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                            <Settings className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                        </span>
                        <div>
                            <h2 className="text-sm font-bold text-slate-950">Next step</h2>
                            <p className="text-sm text-slate-500">{nextStep.title}</p>
                        </div>
                    </div>
                    <p className="mt-3 text-sm leading-6 text-slate-600">{nextStep.description}</p>
                    <Link
                        href={nextStep.href}
                        className="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:brightness-95"
                    >
                        {nextStep.label}
                        <ArrowRight className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                    </Link>
                </aside>
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                <section>
                    <div className="mb-3 flex items-end justify-between gap-4">
                        <div>
                            <h2 className="text-sm font-bold text-slate-950">Setup progress</h2>
                            <p className="mt-1 text-sm text-slate-500">Foundations needed before score entry and results.</p>
                        </div>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        {setupItems.map((item) => (
                            <StatCard
                                key={item.label}
                                icon={item.icon}
                                label={item.label}
                                value={item.value}
                                helper={item.value > 0 ? `${item.helper} started` : `${item.helper} pending`}
                                active={item.value > 0}
                            />
                        ))}
                    </div>
                </section>

                <section className="space-y-2.5">
                    <div>
                        <h2 className="text-sm font-bold text-slate-950">Quick actions</h2>
                        <p className="mt-1 text-sm text-slate-500">Jump into the setup areas you will use most.</p>
                    </div>
                    <QuickAction
                        href={route('school.academic-setup.index')}
                        icon={BookOpen}
                        title="Academic setup"
                        description="Groups, classes, arms, sessions, terms, subjects."
                        primary
                    />
                    <QuickAction
                        href={route('school.assessments.index')}
                        icon={ClipboardList}
                        title="Assessment setup"
                        description="Default CA, exam, and score structures."
                    />
                    <QuickAction
                        href={route('school.staff-setup.index')}
                        icon={UsersRound}
                        title="Staff setup"
                        description="Register teachers and non-teaching staff."
                    />
                    <QuickAction
                        href={route('school.student-setup.index')}
                        icon={GraduationCap}
                        title="Student setup"
                        description="Add students and guardian information."
                    />
                    <QuickAction
                        href={route('school.settings.edit')}
                        icon={Settings}
                        title="Brand settings"
                        description="Logo, colors, and school portal typography."
                    />
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
