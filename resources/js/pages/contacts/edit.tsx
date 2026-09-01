import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ConfirmDelete } from '@/components/confirm-delete';
import {
    ResourceForm,
    type ResourceFormField,
} from '@/components/resource-form';
import ContactController from '@/actions/App/Http/Controllers/ContactController';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/contacts';
import type {
    BreadcrumbItem,
    CompanyOption,
    Contact,
    OwnerOption,
    StatusOption,
} from '@/types';

type Props = {
    contact: Contact;
    statuses: StatusOption[];
    owners: OwnerOption[];
    companies: CompanyOption[];
};

export default function ContactsEdit({
    contact,
    statuses,
    owners,
    companies,
}: Props) {
    const fields: ResourceFormField[] = [
        {
            type: 'text',
            name: 'first_name',
            label: 'First name',
            required: true,
            defaultValue: contact.first_name,
        },
        {
            type: 'text',
            name: 'last_name',
            label: 'Last name',
            required: true,
            defaultValue: contact.last_name,
        },
        {
            type: 'select',
            name: 'status',
            label: 'Status',
            defaultValue: contact.status,
            options: statuses,
        },
        {
            type: 'text',
            name: 'email',
            label: 'Email',
            defaultValue: contact.email ?? undefined,
        },
        {
            type: 'text',
            name: 'phone',
            label: 'Phone',
            defaultValue: contact.phone ?? undefined,
        },
        {
            type: 'text',
            name: 'job_title',
            label: 'Job title',
            defaultValue: contact.job_title ?? undefined,
        },
        {
            type: 'select',
            name: 'company_id',
            label: 'Company',
            defaultValue: contact.company
                ? String(contact.company.id)
                : undefined,
            options: companies.map((company) => ({
                value: String(company.id),
                label: company.name,
            })),
        },
        {
            type: 'select',
            name: 'owner_id',
            label: 'Owner',
            defaultValue: contact.owner ? String(contact.owner.id) : undefined,
            options: owners.map((owner) => ({
                value: String(owner.id),
                label: owner.name,
            })),
        },
    ];

    const fullName = `${contact.first_name} ${contact.last_name}`;

    return (
        <>
            <Head title={`Edit ${fullName}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={`Edit ${fullName}`}
                        description="Update this contact's details."
                    />
                    <ConfirmDelete
                        href={ContactController.destroy(contact.id).url}
                        title="Delete this contact?"
                        description={`This soft-deletes ${fullName}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                    />
                </div>

                <div className="max-w-xl">
                    <ResourceForm
                        form={ContactController.update.form(contact.id)}
                        fields={fields}
                        submitLabel="Save changes"
                        cancelHref={show.url(contact.id)}
                    />
                </div>
            </div>
        </>
    );
}

ContactsEdit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Contacts', href: index() },
        {
            title: `${props.contact.first_name} ${props.contact.last_name}`,
            href: show.url(props.contact.id),
        },
        { title: 'Edit', href: '#' },
    ] satisfies BreadcrumbItem[],
});
