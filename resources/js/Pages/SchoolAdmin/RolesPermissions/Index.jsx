import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { ChevronDown, ShieldCheck } from 'lucide-react';
import { useState } from 'react';

function RolePermissionCard({ role, modules, accessLevels, canWrite }) {
    const [isOpen, setIsOpen] = useState(false);
    const permissionMap = Object.fromEntries((role.permissions ?? []).map((permission) => [permission.module, permission.access_level]));
    const form = useForm({
        permissions: modules.map((module) => ({
            module: module.key,
            access_level: permissionMap[module.key] ?? 'none',
        })),
    });

    const setAccessLevel = (moduleKey, accessLevel) => {
        form.setData('permissions', form.data.permissions.map((permission) => (
            permission.module === moduleKey ? { ...permission, access_level: accessLevel } : permission
        )));
    };

    const submit = (event) => {
        event.preventDefault();
        form.put(route('school.roles.permissions.update', role.id), {
            preserveScroll: true,
        });
    };

    return (
        <form onSubmit={submit} className="rounded-lg border border-slate-200 bg-white shadow-sm">
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
            >
                <div className="min-w-0">
                    <h3 className="text-sm font-bold text-slate-950">{role.name}</h3>
                    <p className="mt-0.5 truncate text-xs text-slate-500">{role.description}</p>
                </div>
                <ChevronDown className={['h-4 w-4 shrink-0 text-slate-400 transition', isOpen ? 'rotate-180' : ''].join(' ')} aria-hidden="true" strokeWidth={2.25} />
            </button>

            {isOpen && (
                <div className="border-t border-slate-200 p-3">
                    <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                        {modules.map((module) => {
                            const selected = form.data.permissions.find((permission) => permission.module === module.key)?.access_level ?? 'none';

                            return (
                                <label key={module.key} className="rounded-md border border-slate-200 bg-slate-50 p-2.5">
                                    <span className="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{module.group}</span>
                                    <span className="mt-1 block text-sm font-bold text-slate-950">{module.label}</span>
                            <select
                                value={selected}
                                onChange={(event) => setAccessLevel(module.key, event.target.value)}
                                disabled={!canWrite}
                                className="mt-2 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                            >
                                        {accessLevels.map((level) => (
                                            <option key={level} value={level}>{level}</option>
                                        ))}
                                    </select>
                                </label>
                            );
                        })}
                    </div>
                    {canWrite && (
                    <div className="mt-3 flex justify-end">
                        <PrimaryButton disabled={form.processing} className="px-3 py-2 text-xs">Save access</PrimaryButton>
                    </div>
                    )}
                </div>
            )}
        </form>
    );
}

export default function Index({ roles, permissionModules, accessLevels }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.role_permissions === 'readwrite';
    return (
        <AuthenticatedLayout title="Roles and permissions" subtitle="Set readonly or readwrite access for each functional area.">
            <Head title="Roles and permissions" />

            <div className="space-y-3">
                <section className="rounded-lg border border-[var(--brand-primary-border)] bg-white p-3 shadow-sm ring-1 ring-[var(--brand-primary-border)]">
                    <div className="flex items-center gap-3">
                        <span className="grid h-9 w-9 place-items-center rounded-md bg-[var(--brand-primary-soft)] text-[var(--brand-primary)]">
                            <ShieldCheck className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                        </span>
                        <div>
                            <h2 className="text-sm font-bold text-slate-950">Role access matrix</h2>
                            <p className="mt-1 text-sm text-slate-500">Permissions saved here apply to everyone assigned to that role. Staff-specific overrides stay on Staff Setup.</p>
                        </div>
                    </div>
                </section>

                <div className="grid gap-3">
                    {roles.map((role) => (
                        <RolePermissionCard key={role.id} role={role} modules={permissionModules} accessLevels={accessLevels} canWrite={canWrite} />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
