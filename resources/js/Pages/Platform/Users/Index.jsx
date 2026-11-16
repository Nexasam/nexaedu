import DataTable from '@/Components/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { Eye, ShieldAlert, UserRoundCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

function StatusBadge({ status }) {
    const styles = status === 'active'
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
        : status === 'ended'
            ? 'bg-slate-100 text-slate-600 ring-slate-500/20'
            : 'bg-amber-50 text-amber-700 ring-amber-600/20';

    return (
        <span className={`inline-flex rounded-full px-2 py-1 text-xs font-bold capitalize ring-1 ring-inset ${styles}`}>
            {status.replace('_', ' ')}
        </span>
    );
}

export default function Index({ users, recentSessions }) {
    const [selectedUser, setSelectedUser] = useState(null);
    const passwordInput = useRef(null);
    const { data, setData, post, processing, errors, clearErrors, reset } = useForm({ password: '' });

    useEffect(() => {
        if (selectedUser) {
            window.setTimeout(() => passwordInput.current?.focus(), 150);
        }
    }, [selectedUser]);

    const closeModal = () => {
        setSelectedUser(null);
        clearErrors();
        reset();
    };

    const startImpersonation = (event) => {
        event.preventDefault();

        post(route('platform.impersonations.store', selectedUser.id), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    };

    return (
        <AuthenticatedLayout
            title="Platform users"
            subtitle="Review school accounts and enter a controlled support session when troubleshooting is required."
        >
            <Head title="Platform users" />

            <div className="grid gap-3 sm:grid-cols-3">
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-bold uppercase text-slate-400">School users</div>
                    <div className="mt-1 text-2xl font-bold text-slate-950">{users.length}</div>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-bold uppercase text-slate-400">School owners</div>
                    <div className="mt-1 text-2xl font-bold text-slate-950">{users.filter((user) => user.is_school_owner).length}</div>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-bold uppercase text-slate-400">Support sessions</div>
                    <div className="mt-1 text-2xl font-bold text-slate-950">{recentSessions.length}</div>
                </div>
            </div>

            <DataTable
                columns={[
                    {
                        key: 'name',
                        label: 'User',
                        className: 'font-semibold text-slate-950',
                        render: (user) => (
                            <div>
                                <div className="flex items-center gap-2">
                                    <span>{user.name}</span>
                                    {user.is_school_owner && (
                                        <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">Owner</span>
                                    )}
                                </div>
                                <div className="mt-0.5 text-xs font-normal text-slate-500">{user.email}</div>
                            </div>
                        ),
                        searchValue: (user) => `${user.name} ${user.email}`,
                    },
                    {
                        key: 'school',
                        label: 'Current school',
                        render: (user) => user.school ? (
                            <div>
                                <div className="font-medium text-slate-800">{user.school.name}</div>
                                <div className="text-xs text-slate-500">Code {user.school.code}</div>
                            </div>
                        ) : <span className="text-slate-400">Not selected</span>,
                        sortValue: (user) => user.school?.name,
                        searchValue: (user) => `${user.school?.name ?? ''} ${user.school?.code ?? ''}`,
                    },
                    { key: 'role', label: 'Role', className: 'capitalize text-slate-600' },
                    {
                        key: 'membership_status',
                        label: 'Membership',
                        render: (user) => <StatusBadge status={user.membership_status} />,
                    },
                    {
                        key: 'actions',
                        label: '',
                        sortable: false,
                        headerClassName: 'text-right',
                        className: 'text-right',
                        render: (user) => (
                            <button
                                type="button"
                                onClick={() => setSelectedUser(user)}
                                disabled={!user.can_impersonate}
                                title={user.can_impersonate ? `View portal as ${user.name}` : 'Only active users in approved schools can be impersonated'}
                                className="inline-flex items-center gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-50 disabled:text-slate-400"
                            >
                                <Eye className="h-4 w-4" aria-hidden="true" strokeWidth={2} />
                                Support view
                            </button>
                        ),
                    },
                ]}
                rows={users}
                emptyLabel="No school users found."
                searchPlaceholder="Search users, schools, roles, or codes"
            />

            <section className="pt-3">
                <div className="flex items-center gap-2">
                    <UserRoundCheck className="h-5 w-5 text-slate-500" aria-hidden="true" strokeWidth={2} />
                    <h2 className="text-lg font-bold text-slate-950">Recent support sessions</h2>
                </div>
                <DataTable
                    columns={[
                        { key: 'target_name', label: 'Viewed as', className: 'font-semibold text-slate-950' },
                        { key: 'school_name', label: 'School', className: 'text-slate-600' },
                        { key: 'impersonator_name', label: 'Platform admin', className: 'text-slate-600' },
                        { key: 'started_at', label: 'Started', className: 'text-slate-600' },
                        { key: 'ended_at', label: 'Ended', className: 'text-slate-600', render: (session) => session.ended_at ?? 'In progress' },
                        { key: 'status', label: 'Status', render: (session) => <StatusBadge status={session.status} /> },
                    ]}
                    rows={recentSessions}
                    emptyLabel="No support sessions have been recorded."
                    searchPlaceholder="Search support history"
                />
            </section>

            <Modal show={Boolean(selectedUser)} closeable={false} maxWidth="lg">
                {selectedUser && (
                    <form onSubmit={startImpersonation}>
                        <div className="border-b border-slate-200 px-5 py-4">
                            <div className="flex items-start gap-3">
                                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700">
                                    <ShieldAlert className="h-5 w-5" aria-hidden="true" strokeWidth={2} />
                                </span>
                                <div>
                                    <h2 className="text-base font-bold text-slate-950">Start a support session</h2>
                                    <p className="mt-1 text-sm leading-5 text-slate-500">
                                        You will enter <strong>{selectedUser.name}</strong>&apos;s account at <strong>{selectedUser.school?.name}</strong>. Any action taken will use this user&apos;s permissions and the session will be audited.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="space-y-2 px-5 py-5">
                            <InputLabel htmlFor="support-password" value="Confirm your platform admin password" />
                            <TextInput
                                id="support-password"
                                ref={passwordInput}
                                type="password"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                className="block w-full"
                                autoComplete="current-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
                            <SecondaryButton type="button" onClick={closeModal} disabled={processing}>Close</SecondaryButton>
                            <PrimaryButton disabled={processing || !data.password}>
                                {processing ? 'Starting...' : 'Confirm and enter portal'}
                            </PrimaryButton>
                        </div>
                    </form>
                )}
            </Modal>
        </AuthenticatedLayout>
    );
}
