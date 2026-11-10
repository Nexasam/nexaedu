import Dropdown from '@/Components/Dropdown';
import { Link, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    BookOpen,
    ChevronRight,
    CheckCircle2,
    ClipboardList,
    GraduationCap,
    LayoutDashboard,
    LogOut,
    Menu,
    School,
    Settings,
    ShieldCheck,
    UserCircle,
    UsersRound,
    Wrench,
    X,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const mainNavigation = [
    { label: 'Dashboard', href: 'dashboard', icon: LayoutDashboard, permission: 'dashboard' },
    { label: 'Academic setup', href: 'school.academic-setup.index', icon: BookOpen, permission: 'academic_setup' },
    {
        label: 'Teachers',
        href: 'school.staff-setup.index',
        icon: UsersRound,
        permission: 'staff_setup',
        children: [
            { label: 'Staff setup', href: 'school.staff-setup.index', permission: 'staff_setup' },
            { label: 'Assignments', href: 'school.teachers.assignments.index', permission: 'teacher_assignments' },
        ],
    },
    {
        label: 'Students',
        href: 'school.student-setup.index',
        icon: GraduationCap,
        permission: 'student_setup',
        children: [
            { label: 'Student setup', href: 'school.student-setup.index', permission: 'student_setup' },
            { label: 'Subjects & teachers', href: 'school.students.subjects.index', permission: 'student_setup' },
        ],
    },
    {
        label: 'Assessments',
        href: 'school.assessments.index',
        icon: ClipboardList,
        permission: 'assessments',
        children: [
            { label: 'Assessment setup', href: 'school.assessments.index', permission: 'assessments' },
            { label: 'Score entry', href: 'school.scores.index', permission: 'score_entry' },
            { label: 'Readiness report', href: 'school.reports.readiness', permission: 'result_readiness' },
            { label: 'Class broadsheet', href: 'school.reports.broadsheet', permission: 'reports' },
            { label: 'Report cards', href: 'school.report-cards.index', permission: 'report_card_viewing' },
        ],
    },
];

const adminNavigation = [
    { label: 'Roles and permissions', href: 'school.roles-permissions.index', icon: ShieldCheck, permission: 'role_permissions' },
    { label: 'Settings', href: 'school.settings.edit', icon: Settings, permission: 'settings' },
];

const platformNavigation = [
    { label: 'Schools', href: 'platform.schools.index', icon: School },
    { label: 'Report templates', href: 'platform.report-card-templates.index', icon: ClipboardList },
    { label: 'Maintenance', href: 'platform.maintenance.index', icon: Wrench },
];

function Toast({ message, type, onClose }) {
    if (!message) {
        return null;
    }

    const Icon = type === 'error' ? AlertCircle : CheckCircle2;
    const styles = type === 'error'
        ? 'border-red-200 bg-white text-red-700'
        : 'border-emerald-200 bg-white text-emerald-700';
    const iconStyles = type === 'error' ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600';

    return (
        <div className={`fixed right-4 top-20 z-50 w-[calc(100vw-2rem)] max-w-sm rounded-xl border p-4 shadow-lg ring-1 ring-slate-900/5 ${styles}`}>
            <div className="flex gap-3">
                <span className={`grid h-9 w-9 shrink-0 place-items-center rounded-lg ${iconStyles}`}>
                    <Icon className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />
                </span>
                <div className="min-w-0 flex-1">
                    <div className="text-sm font-bold text-slate-950">
                        {type === 'error' ? 'Action needed' : 'Saved'}
                    </div>
                    <div className="mt-1 text-sm font-medium leading-5">{message}</div>
                </div>
                <button
                    type="button"
                    onClick={onClose}
                    className="grid h-7 w-7 shrink-0 place-items-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    aria-label="Dismiss notification"
                >
                    <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                </button>
            </div>
        </div>
    );
}

export default function AuthenticatedLayout({ title, subtitle, actions, children }) {
    const { auth, errors = {}, flash = {}, currentSchool, school } = usePage().props;
    const user = auth.user;
    const isPlatformAdmin = user?.global_role === 'platform_admin';
    const permissions = auth.permissions ?? {};
    const brandedSchool = currentSchool ?? school;
    const brandName = brandedSchool?.name ?? (isPlatformAdmin ? 'NexaEdu Platform' : 'School Portal');
    const primaryColor = brandedSchool?.primary_color || '#059669';
    const secondaryColor = brandedSchool?.secondary_color || '#0f172a';
    const brandFont = brandedSchool?.brand_font || 'Inter';
    const [showingMobileSidebar, setShowingMobileSidebar] = useState(false);
    const errorMessage = useMemo(() => Object.values(errors)[0] ?? null, [errors]);
    const [toast, setToast] = useState(null);

    useEffect(() => {
        if (errorMessage) {
            setToast({ message: errorMessage, type: 'error' });

            return;
        }

        if (flash.status) {
            setToast({ message: flash.status, type: 'success' });
        }
    }, [errorMessage, flash.status]);

    useEffect(() => {
        if (!toast) {
            return undefined;
        }

        const timeout = window.setTimeout(() => setToast(null), 4500);

        return () => window.clearTimeout(timeout);
    }, [toast]);

    useEffect(() => {
        const root = document.documentElement;
        const previousBrandPrimary = root.style.getPropertyValue('--brand-primary');
        const previousBrandPrimarySoft = root.style.getPropertyValue('--brand-primary-soft');
        const previousBrandPrimaryBorder = root.style.getPropertyValue('--brand-primary-border');
        const previousBrandSecondary = root.style.getPropertyValue('--brand-secondary');
        const previousFontFamily = root.style.fontFamily;

        root.style.setProperty('--brand-primary', primaryColor);
        root.style.setProperty('--brand-primary-soft', `${primaryColor}14`);
        root.style.setProperty('--brand-primary-border', `${primaryColor}33`);
        root.style.setProperty('--brand-secondary', secondaryColor);
        root.style.fontFamily = `${brandFont}, Inter, ui-sans-serif, system-ui, sans-serif`;

        return () => {
            root.style.setProperty('--brand-primary', previousBrandPrimary);
            root.style.setProperty('--brand-primary-soft', previousBrandPrimarySoft);
            root.style.setProperty('--brand-primary-border', previousBrandPrimaryBorder);
            root.style.setProperty('--brand-secondary', previousBrandSecondary);
            root.style.fontFamily = previousFontFamily;
        };
    }, [brandFont, primaryColor, secondaryColor]);

    const renderLink = (item) => {
        const visibleChildren = item.children?.filter((child) => (
            !child.permission || ['readonly', 'readwrite'].includes(permissions[child.permission])
        ));
        const canSeeItem = !item.permission || ['readonly', 'readwrite'].includes(permissions[item.permission]) || (visibleChildren?.length ?? 0) > 0;

        if (!canSeeItem) {
            return null;
        }
        const isActive = route().current(item.href) || item.children?.some((child) => route().current(child.href));
        const href = visibleChildren?.length && !['readonly', 'readwrite'].includes(permissions[item.permission])
            ? visibleChildren[0].href
            : item.href;
        const Icon = item.icon;

        return (
            <div key={item.label}>
                <Link
                    href={route(href)}
                    className={[
                        'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition',
                        isActive
                            ? 'border bg-white shadow-sm'
                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950',
                    ].join(' ')}
                    style={isActive ? { borderColor: `${primaryColor}33`, color: primaryColor, backgroundColor: `${primaryColor}12` } : undefined}
                >
                    {Icon && (
                        <span className={[
                            'grid h-6 w-6 place-items-center rounded',
                            isActive ? '' : 'bg-slate-100 text-slate-500',
                        ].join(' ')}>
                            <Icon className="h-4 w-4" aria-hidden="true" strokeWidth={2} style={isActive ? { color: primaryColor } : undefined} />
                        </span>
                    )}
                    <span className="font-medium">{item.label}</span>
                </Link>
                {visibleChildren && visibleChildren.length > 0 && isActive && (
                    <div className="ml-9 mt-1 space-y-1">
                        {visibleChildren.map((child) => {
                            const childActive = route().current(child.href);

                            return (
                                <Link
                                    key={child.label}
                                    href={route(child.href)}
                                    className={[
                                        'block rounded-md px-3 py-1.5 text-xs font-semibold transition',
                                        childActive ? 'bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900',
                                    ].join(' ')}
                                >
                                    {child.label}
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        );
    };

    const sidebar = (
        <aside className="flex h-full w-64 shrink-0 flex-col border-r border-slate-200 bg-white">
            <div className="flex h-16 items-center gap-3 border-b border-slate-200 px-5">
                <div className="grid h-9 w-9 place-items-center overflow-hidden rounded-lg text-sm font-black text-white" style={{ backgroundColor: primaryColor }}>
                    {brandedSchool?.logo_url ? <img src={brandedSchool.logo_url} alt={`${brandName} logo`} className="h-full w-full bg-white object-contain p-0.5" /> : <School className="h-5 w-5" aria-hidden="true" strokeWidth={2.25} />}
                </div>
                <div>
                    <div className="text-sm font-bold text-slate-950">{brandName}</div>
                    <div className="text-[11px] font-semibold uppercase tracking-[0.22em]" style={{ color: primaryColor }}>
                        Management
                    </div>
                </div>
            </div>

            <div className="flex-1 overflow-y-auto px-3 py-4">
                <div className="space-y-1">{(isPlatformAdmin ? platformNavigation : mainNavigation).map(renderLink)}</div>

                {!isPlatformAdmin && <div className="mt-6 border-t border-slate-200 pt-4">
                    <div className="px-3 text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">
                        Administration
                    </div>
                    <div className="mt-2 space-y-1">
                        {adminNavigation.map((item) => (
                            <Link
                                key={item.label}
                                href={route(item.href)}
                                className="flex items-center justify-between rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950"
                            >
                                <span className="flex items-center gap-3">
                                    <item.icon className="h-4 w-4 text-slate-500" aria-hidden="true" strokeWidth={2} />
                                    {item.label}
                                </span>
                                <ChevronRight className="h-4 w-4 text-slate-400" aria-hidden="true" strokeWidth={2} />
                            </Link>
                        ))}
                    </div>
                </div>}
            </div>

            {!isPlatformAdmin && <div className="border-t border-slate-200 p-3">
                <div className="rounded-lg p-3 text-white" style={{ backgroundColor: secondaryColor }}>
                    <div className="text-[11px] font-semibold uppercase tracking-[0.18em]" style={{ color: primaryColor }}>
                        Setup stage
                    </div>
                    <div className="mt-1 text-sm font-bold">Academic structure</div>
                    <div className="mt-2 h-1.5 rounded-full bg-white/15">
                        <div className="h-full w-1/4 rounded-full" style={{ backgroundColor: primaryColor }} />
                    </div>
                </div>
            </div>}
        </aside>
    );

    return (
        <div
            className="min-h-screen bg-slate-100 text-slate-900"
            style={{
                '--brand-primary': primaryColor,
                '--brand-primary-soft': `${primaryColor}14`,
                '--brand-primary-border': `${primaryColor}33`,
                '--brand-secondary': secondaryColor,
                fontFamily: `${brandFont}, Inter, ui-sans-serif, system-ui, sans-serif`,
            }}
        >
            <Toast message={toast?.message} type={toast?.type} onClose={() => setToast(null)} />

            <div className="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:flex">{sidebar}</div>

            {showingMobileSidebar && (
                <div className="fixed inset-0 z-40 flex lg:hidden">
                    <button
                        type="button"
                        className="absolute inset-0 bg-slate-950/40"
                        onClick={() => setShowingMobileSidebar(false)}
                    />
                    <div className="relative z-10">{sidebar}</div>
                </div>
            )}

            <div className="lg:pl-64">
                <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
                    <div className="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <div className="flex min-w-0 items-center gap-3">
                            <button
                                type="button"
                                className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 text-slate-700 lg:hidden"
                                onClick={() => setShowingMobileSidebar(true)}
                            >
                                <Menu className="h-5 w-5" aria-hidden="true" strokeWidth={2} />
                            </button>
                            <div className="hidden min-w-0 sm:block">
                                <div className="truncate text-sm font-bold text-slate-950">NexaEdu</div>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Dropdown>
                                <Dropdown.Trigger>
                                    <button className="flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1.5 pl-2 pr-3 text-sm font-medium text-slate-700 shadow-sm">
                                        <span className="grid h-7 w-7 place-items-center rounded-full text-xs font-bold" style={{ backgroundColor: `${primaryColor}1A`, color: primaryColor }}>
                                            {user.name.charAt(0)}
                                        </span>
                                        <span className="hidden sm:block">{user.name}</span>
                                    </button>
                                </Dropdown.Trigger>

                                <Dropdown.Content>
                                    <Dropdown.Link href={route('profile.edit')}>
                                        <span className="inline-flex items-center gap-2">
                                            <UserCircle className="h-4 w-4" aria-hidden="true" strokeWidth={2} />
                                            Profile
                                        </span>
                                    </Dropdown.Link>
                                    <Dropdown.Link href={route('logout')} method="post" as="button">
                                        <span className="inline-flex items-center gap-2">
                                            <LogOut className="h-4 w-4" aria-hidden="true" strokeWidth={2} />
                                            Log Out
                                        </span>
                                    </Dropdown.Link>
                                </Dropdown.Content>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                <main className="relative min-h-[calc(100vh-4rem)] overflow-hidden px-4 py-5 sm:px-6 lg:px-8">
                    <svg
                        className="pointer-events-none absolute right-0 top-0 h-80 w-80 translate-x-16 -translate-y-10 opacity-20"
                        viewBox="0 0 320 320"
                        fill="none"
                        aria-hidden="true"
                        style={{ color: primaryColor }}
                    >
                        <path
                            d="M44 118C84 70 131 47 185 50c38 3 69 18 92 44"
                            stroke="currentColor"
                            strokeWidth="18"
                            strokeLinecap="round"
                        />
                        <path
                            d="M62 180c30-31 68-47 113-47 44 0 78 15 102 45"
                            stroke="currentColor"
                            strokeWidth="14"
                            strokeLinecap="round"
                        />
                        <path
                            d="M92 236c21-18 46-27 76-27 31 0 56 9 75 27"
                            stroke="currentColor"
                            strokeWidth="10"
                            strokeLinecap="round"
                        />
                        <circle cx="160" cy="148" r="22" className="fill-white/70" />
                    </svg>
                    <div className="relative z-10 space-y-5">
                        <div className="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
                            <div className="min-w-0">
                                <h1 className="text-2xl font-bold tracking-tight text-slate-950">
                                    {title}
                                </h1>
                                {subtitle && (
                                    <p className="mt-1 max-w-3xl text-sm text-slate-500">
                                        {subtitle}
                                    </p>
                                )}
                            </div>

                            {actions && (
                                <div className="flex shrink-0 items-center gap-2">
                                    {actions}
                                </div>
                            )}
                        </div>

                        {children}
                    </div>
                </main>
            </div>
        </div>
    );
}
