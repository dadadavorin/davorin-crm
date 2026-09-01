import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    ResourceForm,
    type ResourceFormField,
} from '@/components/resource-form';
import ContactController from '@/actions/App/Http/Controllers/ContactController';
import { dashboard } from '@/routes';
import { index } from '@/routes/contacts';
import type {
    BreadcrumbItem,
    CompanyOption,
    OwnerOption,
    StatusOption,
} from '@/types';

type Props = {
    statuses: StatusOption[];
    owners: OwnerOption[];
    companies: CompanyOption[];
};

export default function ContactsCreate({ statuses, owners, companies }: Props) {
    const fields: ResourceFormField[] = [
        {
            type: 'text',
            name: 'first_name',
            label: 'First name',
            required: true,
        },
        { type: 'text', name: 'last_name', label: 'Last name', required: true },
        {
            type: 'select',
            name: 'status',
            label: 'Status',
            defaultValue: statuses[0]?.value,
            options: statuses,
        },
        { type: 'text', name: 'email', label: 'Email' },
        { type: 'text', name: 'phone', label: 'Phone' },
        { type: 'text', name: 'job_title', label: 'Job title' },
        {
            type: 'select',
            name: 'company_id',
            label: 'Company',
            options: companies.map((company) => ({
                value: String(company.id),
                label: company.name,
            })),
        },
        {
            type: 'select',
            name: 'owner_id',
            label: 'Owner',
            options: owners.map((owner) => ({
                value: String(owner.id),
                label: owner.name,
            })),
        },
    ];

    return (
        <>
            <Head title="New contact" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="New contact"
                    description="Add a contact to the CRM."
                />

                <div className="max-w-xl">
                    <ResourceForm
                        form={ContactController.store.form()}
                        fields={fields}
                        submitLabel="Create contact"
                        cancelHref={index.url()}
                    />
                </div>
            </div>
        </>
    );
}

ContactsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Contacts', href: index() },
        { title: 'New', href: '#' },
    ] satisfies BreadcrumbItem[],
};
