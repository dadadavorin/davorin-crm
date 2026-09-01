import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    ResourceForm,
    type ResourceFormField,
} from '@/components/resource-form';
import DealController from '@/actions/App/Http/Controllers/DealController';
import { dashboard } from '@/routes';
import { index } from '@/routes/deals';
import type {
    BreadcrumbItem,
    CompanyOption,
    ContactOption,
    OwnerOption,
    StatusOption,
} from '@/types';

type Props = {
    stages: StatusOption[];
    owners: OwnerOption[];
    companies: CompanyOption[];
    contacts: ContactOption[];
};

export default function DealsCreate({
    stages,
    owners,
    companies,
    contacts,
}: Props) {
    const fields: ResourceFormField[] = [
        { type: 'text', name: 'title', label: 'Title', required: true },
        { type: 'text', name: 'value', label: 'Value (EUR)' },
        {
            type: 'select',
            name: 'stage',
            label: 'Stage',
            defaultValue: stages[0]?.value,
            options: stages,
        },
        {
            type: 'text',
            name: 'expected_close_date',
            label: 'Expected close date',
            inputType: 'date',
        },
        {
            type: 'select',
            name: 'company_id',
            label: 'Company',
            required: true,
            options: companies.map((company) => ({
                value: String(company.id),
                label: company.name,
            })),
        },
        {
            type: 'select',
            name: 'primary_contact_id',
            label: 'Primary contact',
            options: contacts.map((contact) => ({
                value: String(contact.id),
                label: contact.company_name
                    ? `${contact.name} — ${contact.company_name}`
                    : contact.name,
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
            <Head title="New deal" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="New deal"
                    description="Add a deal to the CRM."
                />

                <div className="max-w-xl">
                    <ResourceForm
                        form={DealController.store.form()}
                        fields={fields}
                        submitLabel="Create deal"
                        cancelHref={index.url()}
                    />
                </div>
            </div>
        </>
    );
}

DealsCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Deals', href: index() },
        { title: 'New', href: '#' },
    ] satisfies BreadcrumbItem[],
};
