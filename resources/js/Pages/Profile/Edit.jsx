import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm } from '@inertiajs/react';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({ mustVerifyEmail, status, staffProfile }) {
    const signatureForm = useForm({ signature: null });

    const submitSignature = (event) => {
        event.preventDefault();
        signatureForm.post(route('profile.signature'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => signatureForm.reset(),
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Profile
                </h2>
            }
        >
            <Head title="Profile" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    {staffProfile && (
                        <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                            <form onSubmit={submitSignature} className="max-w-xl">
                                <h2 className="text-lg font-medium text-gray-900">Report-card signature</h2>
                                <p className="mt-1 text-sm text-gray-600">Upload the signature used when you are configured as a report-card signatory.</p>
                                <div className="mt-4">
                                    <input type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => signatureForm.setData('signature', event.target.files[0])} className="block w-full rounded-md border border-slate-300 bg-white text-sm file:mr-3 file:border-0 file:bg-[var(--brand-primary-soft)] file:px-3 file:py-2 file:font-bold file:text-[var(--brand-primary)]" />
                                    <InputError message={signatureForm.errors.signature} className="mt-2" />
                                    <div className="mt-2 text-xs font-semibold text-slate-500">{staffProfile.signature_path ? 'A signature is currently uploaded.' : 'No signature uploaded yet.'}</div>
                                </div>
                                <PrimaryButton className="mt-4" disabled={!signatureForm.data.signature || signatureForm.processing}>Upload signature</PrimaryButton>
                            </form>
                        </div>
                    )}

                    <div className="bg-white p-4 shadow sm:rounded-lg sm:p-8">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
