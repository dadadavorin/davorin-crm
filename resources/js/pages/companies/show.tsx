import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ConfirmDelete } from '@/components/confirm-delete';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { companyStatusVariant } from '@/lib/company-status';
import CompanyController from '@/actions/App/Http/Controllers/CompanyController';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/companies';
import type { BreadcrumbItem, Company } from '@/types';

type Props = {
    company: Company;
};

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="space-y-0.5">
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="text-sm">{value}</p>
        </div>
    );
}

export default function CompaniesShow({ company }: Props) {
    return (
        <>
            <Head title={company.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={company.name}
                        description="Company details."
                    />
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit.url(company.id)}>Edit</Link>
                        </Button>
                        <ConfirmDelete
                            href={CompanyController.destroy(company.id).url}
                            title="Delete this company?"
                            description={`This soft-deletes ${company.name}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                        />
                    </div>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-6 sm:grid-cols-2">
                    <Field
                        label="Status"
                        value={
                            <StatusBadge
                                label={company.status_label}
                                variant={companyStatusVariant(company.status)}
                            />
                        }
                    />
                    <Field label="Industry" value={company.industry ?? '—'} />
                    <Field label="Website" value={company.website ?? '—'} />
                    <Field label="Email" value={company.email ?? '—'} />
                    <Field label="Phone" value={company.phone ?? '—'} />
                    <Field label="Owner" value={company.owner?.name ?? '—'} />
                    <Field label="Address" value={company.address ?? '—'} />
                    <Field label="Notes" value={company.notes ?? '—'} />
                </div>
            </div>
        </>
    );
}

CompaniesShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Companies', href: index() },
        { title: props.company.name, href: '#' },
    ] satisfies BreadcrumbItem[],
});
