import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ConfirmDelete } from '@/components/confirm-delete';
import {
    ResourceForm,
    type ResourceFormField,
} from '@/components/resource-form';
import CompanyController from '@/actions/App/Http/Controllers/CompanyController';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/companies';
import type {
    BreadcrumbItem,
    Company,
    OwnerOption,
    StatusOption,
} from '@/types';

type Props = {
    company: Company;
    statuses: StatusOption[];
    owners: OwnerOption[];
};

export default function CompaniesEdit({ company, statuses, owners }: Props) {
    const fields: ResourceFormField[] = [
        {
            type: 'text',
            name: 'name',
            label: 'Name',
            required: true,
            defaultValue: company.name,
        },
        {
            type: 'select',
            name: 'status',
            label: 'Status',
            defaultValue: company.status,
            options: statuses,
        },
        {
            type: 'text',
            name: 'industry',
            label: 'Industry',
            defaultValue: company.industry ?? undefined,
        },
        {
            type: 'text',
            name: 'website',
            label: 'Website',
            defaultValue: company.website ?? undefined,
        },
        {
            type: 'text',
            name: 'email',
            label: 'Email',
            defaultValue: company.email ?? undefined,
        },
        {
            type: 'text',
            name: 'phone',
            label: 'Phone',
            defaultValue: company.phone ?? undefined,
        },
        {
            type: 'textarea',
            name: 'address',
            label: 'Address',
            defaultValue: company.address ?? undefined,
        },
        {
            type: 'select',
            name: 'owner_id',
            label: 'Owner',
            defaultValue: company.owner ? String(company.owner.id) : undefined,
            options: owners.map((owner) => ({
                value: String(owner.id),
                label: owner.name,
            })),
        },
        {
            type: 'textarea',
            name: 'notes',
            label: 'Notes',
            defaultValue: company.notes ?? undefined,
        },
    ];

    return (
        <>
            <Head title={`Edit ${company.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={`Edit ${company.name}`}
                        description="Update this company's details."
                    />
                    <ConfirmDelete
                        href={CompanyController.destroy(company.id).url}
                        title="Delete this company?"
                        description={`This soft-deletes ${company.name}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                    />
                </div>

                <div className="max-w-xl">
                    <ResourceForm
                        form={CompanyController.update.form(company.id)}
                        fields={fields}
                        submitLabel="Save changes"
                        cancelHref={show.url(company.id)}
                    />
                </div>
            </div>
        </>
    );
}

CompaniesEdit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Companies', href: index() },
        { title: props.company.name, href: show.url(props.company.id) },
        { title: 'Edit', href: '#' },
    ] satisfies BreadcrumbItem[],
});
