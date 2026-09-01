import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ConfirmDelete } from '@/components/confirm-delete';
import {
    ResourceForm,
    type ResourceFormField,
} from '@/components/resource-form';
import { moneyToInputValue } from '@/lib/money';
import DealController from '@/actions/App/Http/Controllers/DealController';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/deals';
import type {
    BreadcrumbItem,
    CompanyOption,
    ContactOption,
    Deal,
    OwnerOption,
    StatusOption,
} from '@/types';

type Props = {
    deal: Deal;
    stages: StatusOption[];
    owners: OwnerOption[];
    companies: CompanyOption[];
    contacts: ContactOption[];
};

export default function DealsEdit({
    deal,
    stages,
    owners,
    companies,
    contacts,
}: Props) {
    const fields: ResourceFormField[] = [
        {
            type: 'text',
            name: 'title',
            label: 'Title',
            required: true,
            defaultValue: deal.title,
        },
        {
            type: 'text',
            name: 'value',
            label: 'Value (EUR)',
            defaultValue: moneyToInputValue(deal.value_minor),
        },
        {
            type: 'select',
            name: 'stage',
            label: 'Stage',
            defaultValue: deal.stage,
            options: stages,
        },
        {
            type: 'text',
            name: 'expected_close_date',
            label: 'Expected close date',
            inputType: 'date',
            defaultValue: deal.expected_close_date ?? undefined,
        },
        {
            type: 'select',
            name: 'company_id',
            label: 'Company',
            required: true,
            defaultValue: String(deal.company.id),
            options: companies.map((company) => ({
                value: String(company.id),
                label: company.name,
            })),
        },
        {
            type: 'select',
            name: 'primary_contact_id',
            label: 'Primary contact',
            defaultValue: deal.primary_contact
                ? String(deal.primary_contact.id)
                : undefined,
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
            defaultValue: deal.owner ? String(deal.owner.id) : undefined,
            options: owners.map((owner) => ({
                value: String(owner.id),
                label: owner.name,
            })),
        },
    ];

    return (
        <>
            <Head title={`Edit ${deal.title}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={`Edit ${deal.title}`}
                        description="Update this deal's details."
                    />
                    <ConfirmDelete
                        href={DealController.destroy(deal.id).url}
                        title="Delete this deal?"
                        description={`This soft-deletes ${deal.title}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                    />
                </div>

                <div className="max-w-xl">
                    <ResourceForm
                        form={DealController.update.form(deal.id)}
                        fields={fields}
                        submitLabel="Save changes"
                        cancelHref={show.url(deal.id)}
                    />
                </div>
            </div>
        </>
    );
}

DealsEdit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Deals', href: index() },
        { title: props.deal.title, href: show.url(props.deal.id) },
        { title: 'Edit', href: '#' },
    ] satisfies BreadcrumbItem[],
});
