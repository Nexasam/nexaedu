import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        address: '',
        phone: '',
        email: '',
    });

    const submit = (event) => {
        event.preventDefault();
        post(route('school.onboarding.store'));
    };

    return (
        <AuthenticatedLayout
            title="School onboarding"
            subtitle="Submit the basic school profile for platform approval."
        >
            <Head title="School onboarding" />

            <form onSubmit={submit} className="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <InputLabel htmlFor="name" value="School name" />
                    <TextInput
                        id="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        onChange={(event) => setData('name', event.target.value)}
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="slug" value="Subdomain slug" />
                    <TextInput
                        id="slug"
                        value={data.slug}
                        className="mt-1 block w-full"
                        placeholder="corona-school-ikoyi"
                        onChange={(event) => setData('slug', event.target.value)}
                        required
                    />
                    <InputError message={errors.slug} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="address" value="Address" />
                    <TextInput
                        id="address"
                        value={data.address}
                        className="mt-1 block w-full"
                        onChange={(event) => setData('address', event.target.value)}
                    />
                    <InputError message={errors.address} className="mt-2" />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="phone" value="Phone" />
                        <TextInput
                            id="phone"
                            value={data.phone}
                            className="mt-1 block w-full"
                            onChange={(event) => setData('phone', event.target.value)}
                        />
                        <InputError message={errors.phone} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="email" value="School email" />
                        <TextInput
                            id="email"
                            type="email"
                            value={data.email}
                            className="mt-1 block w-full"
                            onChange={(event) => setData('email', event.target.value)}
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>
                </div>

                <PrimaryButton disabled={processing}>Submit for approval</PrimaryButton>
            </form>
        </AuthenticatedLayout>
    );
}
