import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    ResourceForm,
    type ResourceFormField,
} from '@/components/resource-form';
import CompanyController from '@/actions/App/Http/Controllers/CompanyController';
import { dashboard } from '@/routes';
import { index } from '@/routes/companies';
import type { BreadcrumbItem, OwnerOption, StatusOption } from '@/types';

type Props = {
    statuses: StatusOption[];
    owners: OwnerOption[];
};

export default function CompaniesCreate({ statuses, owners }: Props) {
    const fields: ResourceFormField[] = [
        { type: 'text', name: 'name', label: 'Name', required: true },
        {
            type: 'select',
            name: 'status',
            label: 'Status',
            defaultValue: statuses[0]?.value,
            options: statuses,
        },
        { type: 'text', name: 'industry', label: 'Industry' },
        { type: 'text', name: 'website', label: 'Website' },
        { type: 'text', name: 'email', label: 'Email' },
        { type: 'text', name: 'phone', label: 'Phone' },
        { type: 'textarea', name: 'address', label: 'Address' },
        {
            type: 'select',
            name: 'owner_id',
            label: 'Owner',
            options: owners.map((owner) => ({
                value: String(owner.id),
                label: owner.name,
            })),
        },
        { type: 'textarea', name: 'notes', label: 'Notes' },
    ];

    return (
        <>
            <Head title="New company" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="New company"
                    description="Add a company to the CRM."
                />

                <div className="max-w-xl">
                    <ResourceForm
                        form={CompanyController.store.form()}
                        fields={fields}
                        submitLabel="Create company"
                        cancelHref={index.url()}
                    />
                </div>
            </div>
        </>
    );
}

CompaniesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Companies', href: index() },
        { title: 'New', href: '#' },
    ] satisfies BreadcrumbItem[],
};
