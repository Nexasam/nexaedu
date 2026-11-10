import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Forgot Password" />

            <div className="mb-7">
                <div className="text-sm font-bold uppercase tracking-[0.18em] text-emerald-600">
                    Account recovery
                </div>
                <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                    Reset your password
                </h1>
                <p className="mt-2 text-sm leading-6 text-slate-500">
                    Enter your email and we will send a secure reset link.
                </p>
            </div>

            {status && (
                <div className="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-5">
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-2 block w-full"
                        placeholder="admin@nexaedu.test"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <PrimaryButton className="w-full" disabled={processing}>
                    Send reset link
                </PrimaryButton>
            </form>
        </GuestLayout>
    );
}
