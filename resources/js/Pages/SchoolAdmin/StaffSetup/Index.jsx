import DataTable from '@/Components/DataTable';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { PenLine, Plus, Send, SlidersHorizontal, X } from 'lucide-react';
import { useState } from 'react';

function SelectInput({ id, value, onChange, children }) {
    return (
        <select
            id={id}
            value={value}
            onChange={onChange}
            className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
        >
            {children}
        </select>
    );
}

function getSchoolUser(member) {
    return member.user?.school_users?.[0] ?? null;
}

function OverrideModal({ member, modules, accessLevels, onClose }) {
    const schoolUser = getSchoolUser(member);
    const overrideMap = Object.fromEntries((schoolUser?.permission_overrides ?? []).map((override) => [override.module, override]));
    const form = useForm({
        reason: '',
        overrides: modules.map((module) => ({
            module: module.key,
            enabled: Boolean(overrideMap[module.key]),
            access_level: overrideMap[module.key]?.access_level ?? 'readonly',
        })),
    });

    if (!schoolUser) {
        return null;
    }

    const setOverride = (moduleKey, key, value) => {
        form.setData('overrides', form.data.overrides.map((override) => (
            override.module === moduleKey ? { ...override, [key]: value } : override
        )));
    };

    const submit = (event) => {
        event.preventDefault();
        form.put(route('school.school-users.permission-overrides.update', schoolUser.id), {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <Modal show={Boolean(member)} closeable={false} maxWidth="2xl">
            <form onSubmit={submit}>
                <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h2 className="text-base font-bold text-slate-950">Special access for {member.first_name} {member.last_name}</h2>
                        <p className="mt-1 text-sm text-slate-500">Overrides apply only to this staff account.</p>
                    </div>
                    <button type="button" onClick={onClose} className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-900" aria-label="Close">
                        <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                    </button>
                </div>

                <div className="grid max-h-[75vh] gap-3 overflow-y-auto p-5">
                    {modules.map((module) => {
                        const override = form.data.overrides.find((item) => item.module === module.key);

                        return (
                            <div key={module.key} className="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-[1fr_140px_160px] sm:items-center">
                                <div>
                                    <div className="text-sm font-bold text-slate-950">{module.label}</div>
                                    <div className="mt-1 text-xs text-slate-500">{module.description}</div>
                                </div>
                                <label className="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={override.enabled}
                                        onChange={(event) => setOverride(module.key, 'enabled', event.target.checked)}
                                        className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                                    />
                                    Override
                                </label>
                                <select
                                    value={override.access_level}
                                    onChange={(event) => setOverride(module.key, 'access_level', event.target.value)}
                                    disabled={!override.enabled}
                                    className="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)] disabled:opacity-50"
                                >
                                    {accessLevels.map((level) => (
                                        <option key={level} value={level}>{level}</option>
                                    ))}
                                </select>
                            </div>
                        );
                    })}

                    <div>
                        <InputLabel htmlFor="override_reason" value="Reason" />
                        <textarea id="override_reason" value={form.data.reason} onChange={(event) => form.setData('reason', event.target.value)} className="mt-1 block w-full rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-950 shadow-sm transition focus:border-[var(--brand-primary)] focus:bg-white focus:ring-[var(--brand-primary)]" rows="2" />
                    </div>
                </div>

                <div className="flex justify-end border-t border-slate-200 px-5 py-4">
                    <PrimaryButton disabled={form.processing}>Save overrides</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}

export default function Index({ roles, permissionModules, accessLevels, staff }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.staff_setup === 'readwrite';
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [overrideMember, setOverrideMember] = useState(null);
    const [signatureMember, setSignatureMember] = useState(null);
    const form = useForm({
        staff_no: '',
        first_name: '',
        last_name: '',
        middle_name: '',
        email: '',
        phone: '',
        gender: '',
        role: roles.find((role) => role.slug === 'teacher')?.slug ?? roles[0]?.slug ?? '',
        qualification: '',
        specialization: '',
        department: '',
        job_title: '',
        address: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        employment_date: '',
        employment_type: '',
        send_invite: true,
    });
    const importForm = useForm({ staff_file: null });
    const signatureForm = useForm({ signature: null });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('school.staff.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setShowCreateModal(false);
            },
        });
    };

    const importStaff = (event) => {
        event.preventDefault();
        importForm.post(route('school.staff.import'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => importForm.reset(),
        });
    };

    const sendInvite = (member) => {
        router.post(route('school.staff.invite', member.id), {}, {
            preserveScroll: true,
        });
    };

    const uploadSignature = (event) => {
        event.preventDefault();
        signatureForm.post(route('school.staff.signature', signatureMember.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                signatureForm.reset();
                setSignatureMember(null);
            },
        });
    };

    return (
        <AuthenticatedLayout title="Staff setup" subtitle="Create staff records, invite users, and manage role access.">
            <Head title="Staff setup" />

            <div className="space-y-5">
                {canWrite && (
                    <section className="overflow-hidden rounded-xl border border-[var(--brand-primary-border)] bg-white shadow-sm ring-1 ring-[var(--brand-primary-border)]">
                        <div className="border-b border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-4 py-3">
                            <h2 className="text-sm font-bold text-slate-950">Import staff by CSV</h2>
                            <p className="mt-1 text-sm text-slate-600">
                                Headers: staff_no, first_name, last_name, middle_name, email, phone, gender, role, qualification, specialization, department, job_title, employment_date, employment_type.
                            </p>
                        </div>
                        <div className="grid gap-4 lg:grid-cols-[1fr_360px] lg:items-end">
                            <div className="p-4 text-sm text-slate-600">
                                Use configured role slugs like <span className="font-semibold text-slate-800">teacher</span>, <span className="font-semibold text-slate-800">admin</span>, or <span className="font-semibold text-slate-800">bursar</span>.
                            </div>
                            <form onSubmit={importStaff} className="grid gap-3 p-4 sm:grid-cols-[1fr_auto] sm:items-end">
                                <div>
                                    <InputLabel htmlFor="staff_file" value="CSV file" />
                                    <input
                                        id="staff_file"
                                        type="file"
                                        accept=".csv,text/csv"
                                        className="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700"
                                        onChange={(event) => importForm.setData('staff_file', event.target.files[0])}
                                    />
                                    <InputError message={importForm.errors.staff_file} className="mt-1" />
                                </div>
                                <PrimaryButton disabled={importForm.processing}>Import</PrimaryButton>
                            </form>
                        </div>
                    </section>
                )}

                {canWrite && (
                <div>
                    <button
                        type="button"
                        onClick={() => setShowCreateModal(true)}
                        className="inline-flex items-center gap-2 rounded-md bg-[var(--brand-primary)] px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:brightness-95"
                    >
                        <Plus className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                        Add staff
                    </button>
                </div>
                )}

                <Modal show={showCreateModal} closeable={false} maxWidth="2xl">
                    <form onSubmit={submit}>
                        <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                            <div>
                                <h2 className="text-base font-bold text-slate-950">Add staff</h2>
                                <p className="mt-1 text-sm text-slate-500">Create a staff profile and invite portal access.</p>
                            </div>
                            <button type="button" onClick={() => setShowCreateModal(false)} className="grid h-9 w-9 place-items-center rounded-md border border-slate-200 bg-white text-slate-500 hover:text-slate-900" aria-label="Close">
                                <X className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                            </button>
                        </div>

                        <div className="grid max-h-[75vh] gap-5 overflow-y-auto p-5">
                            <div>
                                <h3 className="text-sm font-bold text-slate-950">Personal details</h3>
                                <div className="mt-3 grid gap-4 md:grid-cols-4">
                                    <div>
                                        <InputLabel htmlFor="staff_no" value="Staff no" />
                                        <TextInput id="staff_no" value={form.data.staff_no} className="mt-1 block w-full" onChange={(event) => form.setData('staff_no', event.target.value)} />
                                        <InputError message={form.errors.staff_no} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="first_name" value="First name" />
                                        <TextInput id="first_name" value={form.data.first_name} className="mt-1 block w-full" onChange={(event) => form.setData('first_name', event.target.value)} />
                                        <InputError message={form.errors.first_name} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="last_name" value="Last name" />
                                        <TextInput id="last_name" value={form.data.last_name} className="mt-1 block w-full" onChange={(event) => form.setData('last_name', event.target.value)} />
                                        <InputError message={form.errors.last_name} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="middle_name" value="Middle name" />
                                        <TextInput id="middle_name" value={form.data.middle_name} className="mt-1 block w-full" onChange={(event) => form.setData('middle_name', event.target.value)} />
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-4">
                                <div>
                                    <InputLabel htmlFor="email" value="Email" />
                                    <TextInput id="email" type="email" value={form.data.email} className="mt-1 block w-full" onChange={(event) => form.setData('email', event.target.value)} />
                                    <InputError message={form.errors.email} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="phone" value="Phone" />
                                    <TextInput id="phone" value={form.data.phone} className="mt-1 block w-full" onChange={(event) => form.setData('phone', event.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="gender" value="Gender" />
                                    <SelectInput id="gender" value={form.data.gender} onChange={(event) => form.setData('gender', event.target.value)}>
                                        <option value="">Select</option>
                                        <option value="female">Female</option>
                                        <option value="male">Male</option>
                                    </SelectInput>
                                </div>
                                <div>
                                    <InputLabel htmlFor="role" value="Role" />
                                    <SelectInput id="role" value={form.data.role} onChange={(event) => form.setData('role', event.target.value)}>
                                        {roles.map((role) => (
                                            <option key={role.id} value={role.slug}>{role.name}</option>
                                        ))}
                                    </SelectInput>
                                    <InputError message={form.errors.role} className="mt-1" />
                                </div>
                            </div>

                            <div>
                                <h3 className="text-sm font-bold text-slate-950">Professional details</h3>
                                <div className="mt-3 grid gap-4 md:grid-cols-4">
                                    <div>
                                        <InputLabel htmlFor="qualification" value="Qualification" />
                                        <TextInput id="qualification" value={form.data.qualification} className="mt-1 block w-full" onChange={(event) => form.setData('qualification', event.target.value)} placeholder="B.Ed, NCE, B.Sc" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="specialization" value="Specialization" />
                                        <TextInput id="specialization" value={form.data.specialization} className="mt-1 block w-full" onChange={(event) => form.setData('specialization', event.target.value)} placeholder="Mathematics" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="department" value="Department" />
                                        <TextInput id="department" value={form.data.department} className="mt-1 block w-full" onChange={(event) => form.setData('department', event.target.value)} placeholder="Science" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="job_title" value="Job title" />
                                        <TextInput id="job_title" value={form.data.job_title} className="mt-1 block w-full" onChange={(event) => form.setData('job_title', event.target.value)} placeholder="Class teacher" />
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-3">
                                <div>
                                    <InputLabel htmlFor="employment_date" value="Employment date" />
                                    <TextInput id="employment_date" type="date" value={form.data.employment_date} className="mt-1 block w-full" onChange={(event) => form.setData('employment_date', event.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="employment_type" value="Employment type" />
                                    <SelectInput id="employment_type" value={form.data.employment_type} onChange={(event) => form.setData('employment_type', event.target.value)}>
                                        <option value="">Select type</option>
                                        <option value="full_time">Full time</option>
                                        <option value="part_time">Part time</option>
                                        <option value="contract">Contract</option>
                                    </SelectInput>
                                </div>
                                <label className="flex items-end gap-3 rounded-lg border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] p-3 text-sm font-semibold text-slate-700">
                                    <input type="checkbox" checked={form.data.send_invite} onChange={(event) => form.setData('send_invite', event.target.checked)} className="rounded border-slate-300 text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]" />
                                    Send portal invite
                                </label>
                            </div>

                            <div>
                                <InputLabel htmlFor="address" value="Address" />
                                <textarea id="address" value={form.data.address} onChange={(event) => form.setData('address', event.target.value)} className="mt-1 block w-full rounded-lg border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-950 shadow-sm transition focus:border-[var(--brand-primary)] focus:bg-white focus:ring-[var(--brand-primary)]" rows="2" />
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="emergency_contact_name" value="Emergency contact name" />
                                    <TextInput id="emergency_contact_name" value={form.data.emergency_contact_name} className="mt-1 block w-full" onChange={(event) => form.setData('emergency_contact_name', event.target.value)} />
                                </div>
                                <div>
                                    <InputLabel htmlFor="emergency_contact_phone" value="Emergency contact phone" />
                                    <TextInput id="emergency_contact_phone" value={form.data.emergency_contact_phone} className="mt-1 block w-full" onChange={(event) => form.setData('emergency_contact_phone', event.target.value)} />
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end border-t border-slate-200 px-5 py-4">
                            <PrimaryButton disabled={form.processing}>Save staff</PrimaryButton>
                        </div>
                    </form>
                </Modal>

                <DataTable
                    columns={[
                        {
                            key: 'staff',
                            label: 'Staff',
                            className: 'text-slate-950',
                            render: (member) => (
                                <>
                                    <div className="font-semibold">{member.first_name} {member.last_name}</div>
                                    <div className="text-xs text-slate-500">{member.staff_no || 'No staff no'}{member.qualification ? ` • ${member.qualification}` : ''}</div>
                                </>
                            ),
                            sortValue: (member) => `${member.first_name} ${member.last_name}`,
                            searchValue: (member) => `${member.first_name} ${member.last_name} ${member.staff_no ?? ''} ${member.qualification ?? ''} ${member.department ?? ''}`,
                        },
                        { key: 'role', label: 'Role', className: 'capitalize text-slate-600' },
                        { key: 'department', label: 'Department', className: 'text-slate-600', render: (member) => member.department || member.job_title || 'Not set' },
                        { key: 'contact', label: 'Contact', className: 'text-slate-600', render: (member) => member.email || member.phone || 'No contact', sortValue: (member) => member.email || member.phone || '' },
                        { key: 'signature', label: 'Signature', className: 'text-slate-600', render: (member) => member.signature_path ? 'Uploaded' : 'Not uploaded', sortValue: (member) => member.signature_path ? 1 : 0 },
                        {
                            key: 'access',
                            label: 'Portal access',
                            className: 'text-slate-600',
                            render: (member) => {
                                const schoolUser = getSchoolUser(member);

                                return schoolUser ? `${schoolUser.status} • ${schoolUser.school_role?.name ?? member.role}` : 'No portal account';
                            },
                            sortValue: (member) => getSchoolUser(member)?.status ?? '',
                        },
                        ...(canWrite ? [{
                            key: 'actions',
                            label: 'Actions',
                            sortable: false,
                            headerClassName: 'text-right',
                            className: 'text-right',
                            render: (member) => (
                                <div className="inline-flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            signatureForm.clearErrors();
                                            signatureForm.reset();
                                            setSignatureMember(member);
                                        }}
                                        className="grid h-8 w-8 place-items-center rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] text-[var(--brand-primary)] transition hover:brightness-95"
                                        aria-label={`Upload signature for ${member.first_name} ${member.last_name}`}
                                        title="Upload signature"
                                    >
                                        <PenLine className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setOverrideMember(member)}
                                        disabled={!getSchoolUser(member)}
                                        className="grid h-8 w-8 place-items-center rounded-md border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                                        aria-label="Special access"
                                    >
                                        <SlidersHorizontal className="h-4 w-4" aria-hidden="true" strokeWidth={2.25} />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => sendInvite(member)}
                                        disabled={!member.email}
                                        className="inline-flex items-center gap-2 rounded-md border border-[var(--brand-primary-border)] bg-[var(--brand-primary-soft)] px-3 py-2 text-xs font-bold text-[var(--brand-primary)] transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <Send className="h-3.5 w-3.5" aria-hidden="true" strokeWidth={2.25} />
                                        Invite
                                    </button>
                                </div>
                            ),
                        }] : []),
                    ]}
                    rows={staff}
                    emptyLabel="No staff records yet."
                    searchPlaceholder="Search staff"
                />

                {overrideMember && (
                    <OverrideModal
                        member={overrideMember}
                        modules={permissionModules}
                        accessLevels={accessLevels}
                        onClose={() => setOverrideMember(null)}
                    />
                )}

                <Modal show={Boolean(signatureMember)} closeable={false} maxWidth="md">
                    <form onSubmit={uploadSignature}>
                        <div className="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
                            <div><h2 className="text-base font-bold text-slate-950">Upload staff signature</h2><p className="mt-1 text-sm text-slate-500">{signatureMember?.first_name} {signatureMember?.last_name}</p></div>
                            <button type="button" onClick={() => setSignatureMember(null)} className="grid h-8 w-8 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close signature upload"><X className="h-4 w-4" aria-hidden="true" /></button>
                        </div>
                        <div className="grid gap-3 p-5">
                            <InputLabel htmlFor="staff_signature" value="Signature image" />
                            <input id="staff_signature" type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => signatureForm.setData('signature', event.target.files[0])} className="block w-full rounded-md border border-slate-300 bg-white text-sm file:mr-3 file:border-0 file:bg-[var(--brand-primary-soft)] file:px-3 file:py-2 file:font-bold file:text-[var(--brand-primary)]" />
                            <InputError message={signatureForm.errors.signature} />
                            <p className="text-xs leading-5 text-slate-500">Use a clear signature on a plain or transparent background. Maximum size: 1 MB.</p>
                        </div>
                        <div className="flex justify-end border-t border-slate-200 px-5 py-4"><PrimaryButton disabled={!signatureForm.data.signature || signatureForm.processing}>Upload signature</PrimaryButton></div>
                    </form>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
