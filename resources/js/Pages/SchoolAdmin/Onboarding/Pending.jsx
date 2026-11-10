import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Pending({ school }) {
    const primaryDomain = school?.domains?.find((domain) => domain.is_primary);

    return (
        <AuthenticatedLayout
            title="Approval pending"
            subtitle="The platform team needs to approve this school before the tenant portal opens."
        >
            <Head title="Approval pending" />

            <section className="max-w-3xl rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl className="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="font-semibold text-slate-500">School</dt>
                        <dd className="mt-1 text-lg font-bold text-slate-950">{school?.name}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold text-slate-500">School code</dt>
                        <dd className="mt-1 text-lg font-bold text-slate-950">{school?.code}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold text-slate-500">Status</dt>
                        <dd className="mt-1 capitalize text-slate-700">{school?.status}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold text-slate-500">Primary domain</dt>
                        <dd className="mt-1 text-slate-700">{primaryDomain?.domain}</dd>
                    </div>
                </dl>
            </section>
        </AuthenticatedLayout>
    );
}
