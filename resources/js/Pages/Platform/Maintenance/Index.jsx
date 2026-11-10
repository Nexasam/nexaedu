import Modal from '@/Components/Modal';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Database, FileX2, GitPullRequestArrow, RotateCcw, ServerCog, Terminal, X } from 'lucide-react';
import { useState } from 'react';

const operations = [
    {
        id: 'pull',
        title: 'Pull from main',
        command: 'git pull --ff-only origin main',
        description: 'Fetch and fast-forward this server to the latest committed code on main.',
        confirmation: 'This will update the application files on this server from origin/main. Local server changes or a non-fast-forward history will stop the pull.',
        icon: GitPullRequestArrow,
        tone: 'blue',
    },
    {
        id: 'optimize_clear',
        title: 'Clear application caches',
        command: 'php artisan optimize:clear',
        description: 'Clear configuration, route, view, event, and application caches.',
        confirmation: 'This will clear Laravel caches. The next few requests may take slightly longer while caches are rebuilt.',
        icon: RotateCcw,
        tone: 'emerald',
    },
    {
        id: 'migrate',
        title: 'Run database migrations',
        command: 'php artisan migrate --force',
        description: 'Apply pending production database migrations.',
        confirmation: 'This will modify the production database by running every pending migration. Confirm that the latest code has been pulled first.',
        icon: Database,
        tone: 'amber',
    },
    {
        id: 'clear_logs',
        title: 'Clear Laravel logs',
        command: 'truncate storage/logs/*.log',
        description: 'Empty Laravel log files while keeping the files and permissions intact.',
        confirmation: 'This permanently removes the current Laravel log history. Download or review important errors before continuing.',
        icon: FileX2,
        tone: 'red',
    },
];

const toneStyles = {
    blue: 'border-blue-200 bg-blue-50 text-blue-700',
    emerald: 'border-emerald-200 bg-emerald-50 text-emerald-700',
    amber: 'border-amber-200 bg-amber-50 text-amber-700',
    red: 'border-red-200 bg-red-50 text-red-700',
};

export default function Index({ server, consoleResult }) {
    const [selectedOperation, setSelectedOperation] = useState(null);
    const [processing, setProcessing] = useState(false);

    const runOperation = () => {
        setProcessing(true);
        router.post(route('platform.maintenance.store'), {
            operation: selectedOperation.id,
        }, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setSelectedOperation(null);
            },
        });
    };

    return (
        <AuthenticatedLayout
            title="Platform maintenance"
            subtitle="Run approved deployment and recovery operations without opening the server terminal."
        >
            <Head title="Platform maintenance" />

            <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                {[
                    ['Environment', server.environment],
                    ['Branch', server.branch],
                    ['Commit', server.commit],
                    ['Laravel', server.laravel_version],
                    ['PHP', server.php_version],
                ].map(([label, value]) => (
                    <div key={label} className="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div className="text-xs font-bold uppercase text-slate-400">{label}</div>
                        <div className="mt-1 truncate text-sm font-bold text-slate-900" title={value}>{value}</div>
                    </div>
                ))}
            </section>

            <section className="mt-5 grid gap-4 md:grid-cols-2">
                {operations.map((operation) => {
                    const Icon = operation.icon;

                    return (
                        <article key={operation.id} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div className="flex items-start gap-4">
                                <span className={`grid h-10 w-10 shrink-0 place-items-center rounded-lg border ${toneStyles[operation.tone]}`}>
                                    <Icon className="h-5 w-5" aria-hidden="true" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <h2 className="text-sm font-bold text-slate-950">{operation.title}</h2>
                                    <p className="mt-1 text-sm leading-6 text-slate-500">{operation.description}</p>
                                    <code className="mt-3 block overflow-x-auto rounded-md bg-slate-950 px-3 py-2 text-xs text-slate-200">{operation.command}</code>
                                </div>
                            </div>
                            <div className="mt-4 flex justify-end">
                                <button type="button" onClick={() => setSelectedOperation(operation)} className={`inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-bold transition hover:brightness-95 ${toneStyles[operation.tone]}`}>
                                    <Icon className="h-4 w-4" aria-hidden="true" />
                                    Run
                                </button>
                            </div>
                        </article>
                    );
                })}
            </section>

            <section className="mt-5 overflow-hidden rounded-lg border border-slate-800 bg-slate-950 shadow-sm">
                <div className="flex items-center justify-between border-b border-slate-800 px-4 py-3">
                    <div className="flex items-center gap-2 text-sm font-bold text-white">
                        <Terminal className="h-4 w-4 text-blue-400" aria-hidden="true" />
                        Command output
                    </div>
                    {consoleResult?.ran_at && <time className="text-xs text-slate-400">{new Date(consoleResult.ran_at).toLocaleString()}</time>}
                </div>
                <pre className={`min-h-48 max-h-96 overflow-auto whitespace-pre-wrap p-4 font-mono text-xs leading-6 ${consoleResult?.success === false ? 'text-red-300' : 'text-emerald-300'}`}>
                    {consoleResult?.output ?? 'No maintenance command has been run in this session.'}
                </pre>
            </section>

            <Modal show={Boolean(selectedOperation)} closeable={false} maxWidth="lg">
                <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div className="flex items-center gap-3">
                        <span className="grid h-9 w-9 place-items-center rounded-lg bg-amber-50 text-amber-700">
                            <ServerCog className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div>
                            <h2 className="text-base font-bold text-slate-950">Confirm maintenance operation</h2>
                            <p className="mt-0.5 text-sm text-slate-500">{selectedOperation?.title}</p>
                        </div>
                    </div>
                    <button type="button" onClick={() => setSelectedOperation(null)} disabled={processing} className="grid h-8 w-8 place-items-center rounded-md text-slate-400 hover:bg-slate-100 disabled:opacity-50" aria-label="Close">
                        <X className="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
                <div className="p-5">
                    <p className="text-sm leading-6 text-slate-600">{selectedOperation?.confirmation}</p>
                    <code className="mt-4 block overflow-x-auto rounded-md bg-slate-950 px-3 py-2 text-xs text-slate-200">{selectedOperation?.command}</code>
                </div>
                <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" onClick={() => setSelectedOperation(null)} disabled={processing} className="rounded-md border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 disabled:opacity-50">Cancel</button>
                    <button type="button" onClick={runOperation} disabled={processing} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">
                        {processing ? 'Running...' : 'Confirm and run'}
                    </button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
