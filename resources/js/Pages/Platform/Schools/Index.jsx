import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import DataTable from '@/Components/DataTable';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, router } from '@inertiajs/react';

export default function Index({ schools }) {
    const approve = (school) => {
        router.post(route('platform.schools.approval.store', school.id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout
            title="Platform schools"
            subtitle="Review tenant schools and approve them for portal access."
        >
            <Head title="Platform schools" />

            <DataTable
                columns={[
                    { key: 'name', label: 'School', className: 'font-semibold text-slate-950' },
                    { key: 'owner', label: 'Owner', className: 'text-slate-600', render: (school) => school.owner?.name, sortValue: (school) => school.owner?.name },
                    { key: 'code', label: 'Code', className: 'text-slate-600' },
                    { key: 'status', label: 'Status', className: 'capitalize text-slate-600' },
                    { key: 'domain', label: 'Domain', className: 'text-slate-600', render: (school) => school.domains?.[0]?.domain, sortValue: (school) => school.domains?.[0]?.domain },
                    {
                        key: 'actions',
                        label: '',
                        sortable: false,
                        headerClassName: 'text-right',
                        className: 'text-right',
                        render: (school) => school.status === 'pending' && (
                            <PrimaryButton onClick={() => approve(school)}>Approve</PrimaryButton>
                        ),
                    },
                ]}
                rows={schools}
                emptyLabel="No schools found."
                searchPlaceholder="Search schools"
            />
        </AuthenticatedLayout>
    );
}
