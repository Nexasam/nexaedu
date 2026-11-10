import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            <div className="mb-7">
                <div className="text-sm font-bold uppercase tracking-[0.18em] text-emerald-600">
                    Welcome back
                </div>
                <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-950">
                    Sign in to NexaEdu
                </h1>
                <p className="mt-2 text-sm leading-6 text-slate-500">
                    Continue to your school operations dashboard.
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
                        autoComplete="username"
                        placeholder="admin@nexaedu.test"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-2 block w-full"
                        autoComplete="current-password"
                        placeholder="Enter your password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="flex items-center justify-between gap-4">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600">
                            Remember me
                        </span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="rounded-md text-sm font-semibold text-emerald-700 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                        >
                            Forgot password?
                        </Link>
                    )}
                </div>

                <PrimaryButton className="w-full" disabled={processing}>
                    Log in
                </PrimaryButton>

                <p className="text-center text-sm text-slate-500">
                    New school account?{' '}
                    <Link
                        href={route('register')}
                        className="font-bold text-emerald-700 hover:text-emerald-800"
                    >
                        Create one
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
