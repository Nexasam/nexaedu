import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function ColorField({ id, label, value, error, onChange }) {
    return (
        <div>
            <InputLabel htmlFor={id} value={label} />
            <div className="mt-1 flex gap-3">
                <input
                    id={id}
                    type="color"
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    className="h-11 w-14 rounded-md border border-slate-300 bg-white p-1"
                />
                <TextInput value={value} onChange={(event) => onChange(event.target.value)} className="block w-full" />
            </div>
            <InputError message={error} className="mt-1" />
        </div>
    );
}

export default function Edit({ school, fontOptions, staff }) {
    const { auth } = usePage().props;
    const canWrite = auth.permissions?.settings === 'readwrite';
    const form = useForm({
        primary_color: school.primary_color,
        secondary_color: school.secondary_color,
        brand_font: school.brand_font,
        logo: null,
        principal_staff_profile_id: school.principal_staff_profile_id ?? '',
    });
    const [logoPreviewUrl, setLogoPreviewUrl] = useState(school.logo_url);

    useEffect(() => {
        if (!form.data.logo) {
            setLogoPreviewUrl(school.logo_url);

            return undefined;
        }

        const objectUrl = URL.createObjectURL(form.data.logo);
        setLogoPreviewUrl(objectUrl);

        return () => URL.revokeObjectURL(objectUrl);
    }, [form.data.logo, school.logo_url]);

    const submit = (event) => {
        event.preventDefault();
        form.post(route('school.settings.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.setData('logo', null),
        });
    };

    return (
        <AuthenticatedLayout title="School settings" subtitle="Manage branding for this school's portal.">
            <Head title="School settings" />

            <div className="grid gap-5 xl:grid-cols-[1fr_360px]">
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                    <div className="border-b border-slate-200 bg-slate-50/70 px-4 py-3">
                        <h2 className="text-sm font-bold text-slate-950">Brand identity</h2>
                        <p className="mt-1 text-sm text-slate-500">Choose the colors, logo, and font used across the school portal.</p>
                    </div>

                    <form onSubmit={submit} className="grid gap-5 p-4">
                        <fieldset disabled={!canWrite} className="grid gap-5 disabled:opacity-70">
                        <div className="grid gap-4 md:grid-cols-2">
                            <ColorField
                                id="primary_color"
                                label="Primary color"
                                value={form.data.primary_color}
                                error={form.errors.primary_color}
                                onChange={(value) => form.setData('primary_color', value)}
                            />
                            <ColorField
                                id="secondary_color"
                                label="Secondary color"
                                value={form.data.secondary_color}
                                error={form.errors.secondary_color}
                                onChange={(value) => form.setData('secondary_color', value)}
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="brand_font" value="Preferred font" />
                            <select
                                id="brand_font"
                                value={form.data.brand_font}
                                onChange={(event) => form.setData('brand_font', event.target.value)}
                                className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                {fontOptions.map((font) => <option key={font} value={font}>{font}</option>)}
                            </select>
                            <InputError message={form.errors.brand_font} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="logo" value="School logo" />
                            <input
                                id="logo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                onChange={(event) => form.setData('logo', event.target.files[0])}
                                className="mt-1 block w-full rounded-md border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700"
                            />
                            <InputError message={form.errors.logo} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="principal_staff_profile_id" value="Principal report signatory" />
                            <select
                                id="principal_staff_profile_id"
                                value={form.data.principal_staff_profile_id}
                                onChange={(event) => form.setData('principal_staff_profile_id', event.target.value)}
                                className="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-[var(--brand-primary)] focus:ring-[var(--brand-primary)]"
                            >
                                <option value="">No principal signatory</option>
                                {staff.map((member) => (
                                    <option key={member.id} value={member.id}>
                                        {member.first_name} {member.last_name}{member.job_title ? ` • ${member.job_title}` : ''}{member.signature_path ? '' : ' • signature missing'}
                                    </option>
                                ))}
                            </select>
                            <InputError message={form.errors.principal_staff_profile_id} className="mt-1" />
                        </div>
                        </fieldset>

                        {canWrite && <PrimaryButton disabled={form.processing}>Save settings</PrimaryButton>}
                        {!canWrite && <div className="text-sm font-semibold text-slate-500">You have readonly access to settings.</div>}
                    </form>
                </section>

                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-100">
                    <div className="border-b border-slate-200 bg-slate-50/70 px-4 py-3">
                        <h2 className="text-sm font-bold text-slate-950">Preview</h2>
                    </div>
                    <div className="p-4">
                        <div className="rounded-xl border border-slate-200 p-4" style={{ fontFamily: form.data.brand_font }}>
                            <div className="flex items-center gap-3">
                                <div className="grid h-12 w-12 place-items-center overflow-hidden rounded-lg text-sm font-black text-white" style={{ backgroundColor: form.data.primary_color }}>
                                    {logoPreviewUrl ? <img src={logoPreviewUrl} alt={`${school.name} logo preview`} className="h-full w-full bg-white object-contain p-1" /> : school.name.charAt(0)}
                                </div>
                                <div>
                                    <div className="text-base font-bold text-slate-950">{school.name}</div>
                                    <div className="text-xs font-semibold uppercase tracking-[0.18em]" style={{ color: form.data.primary_color }}>School portal</div>
                                </div>
                            </div>
                            <div className="mt-5 rounded-lg p-4 text-white" style={{ backgroundColor: form.data.secondary_color }}>
                                <div className="text-sm font-bold">Dashboard header</div>
                                <div className="mt-1 text-sm opacity-80">This is how brand color and typography will feel.</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
