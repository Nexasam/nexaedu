import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';

const metrics = [
    ['1,248', 'Student records'],
    ['26', 'Active classes'],
    ['94%', 'Attendance today'],
    ['81%', 'Fees collected'],
];

const modules = [
    'Admissions',
    'Attendance',
    'Billing',
    'Timetable',
    'Parent updates',
    'Reports',
];

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="School Management System" />

            <main className="min-h-screen bg-slate-950 text-white">
                <header className="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
                    <Link href="/" className="flex items-center gap-3">
                        <ApplicationLogo className="h-10 w-10 text-emerald-500" />
                        <div>
                            <div className="text-lg font-bold">NexaEdu</div>
                            <div className="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-300">
                                School management
                            </div>
                        </div>
                    </Link>

                    <nav className="flex items-center gap-2">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-100"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="rounded-md px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white"
                                >
                                    Log in
                                </Link>
                                <Link
                                    href={route('register')}
                                    className="rounded-md bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400"
                                >
                                    Register
                                </Link>
                            </>
                        )}
                    </nav>
                </header>

                <section className="mx-auto grid min-h-[calc(100vh-84px)] w-full max-w-7xl items-center gap-10 px-4 pb-10 pt-8 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
                    <div>
                        <div className="inline-flex rounded-md border border-emerald-400/30 bg-emerald-400/10 px-3 py-2 text-sm font-semibold text-emerald-200">
                            Admin workspace for modern schools
                        </div>
                        <h1 className="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                            School management without the spreadsheet scramble.
                        </h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">
                            Track admissions, attendance, fees, classes, and daily school activity from one focused dashboard.
                        </p>

                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href={auth.user ? route('dashboard') : route('login')}
                                className="rounded-md bg-emerald-500 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-emerald-400"
                            >
                                Open dashboard
                            </Link>
                            {!auth.user && (
                                <Link
                                    href={route('register')}
                                    className="rounded-md border border-white/15 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10"
                                >
                                    Create account
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="rounded-lg border border-white/10 bg-white/[0.06] p-4 shadow-2xl shadow-emerald-950/30">
                        <div className="rounded-md bg-slate-50 p-4 text-slate-950">
                            <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div>
                                    <div className="text-sm font-bold">Today&apos;s school pulse</div>
                                    <div className="text-xs text-slate-500">Live operational summary</div>
                                </div>
                                <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                    Online
                                </span>
                            </div>

                            <div className="mt-4 grid grid-cols-2 gap-3">
                                {metrics.map(([value, label]) => (
                                    <div key={label} className="rounded-md border border-slate-200 bg-white p-4">
                                        <div className="text-2xl font-bold">{value}</div>
                                        <div className="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                            {label}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="mt-4 rounded-md border border-slate-200 bg-white p-4">
                                <div className="text-sm font-bold">Core modules</div>
                                <div className="mt-3 grid grid-cols-2 gap-2">
                                    {modules.map((module) => (
                                        <div key={module} className="rounded bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                                            {module}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}
