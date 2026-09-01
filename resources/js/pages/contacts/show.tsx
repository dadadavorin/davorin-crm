import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ConfirmDelete } from '@/components/confirm-delete';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { contactStatusVariant } from '@/lib/contact-status';
import { dealStageVariant } from '@/lib/deal-stage';
import { formatMoney } from '@/lib/money';
import ContactController from '@/actions/App/Http/Controllers/ContactController';
import { show as showCompany } from '@/routes/companies';
import { show as showDeal } from '@/routes/deals';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/contacts';
import type { BreadcrumbItem, Contact, CompanyDeal } from '@/types';

type Props = {
    contact: Contact;
    deals: CompanyDeal[];
};

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="space-y-0.5">
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="text-sm">{value}</p>
        </div>
    );
}

export default function ContactsShow({ contact, deals }: Props) {
    const fullName = `${contact.first_name} ${contact.last_name}`;

    return (
        <>
            <Head title={fullName} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading title={fullName} description="Contact details." />
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit.url(contact.id)}>Edit</Link>
                        </Button>
                        <ConfirmDelete
                            href={ContactController.destroy(contact.id).url}
                            title="Delete this contact?"
                            description={`This soft-deletes ${fullName}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                        />
                    </div>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-6 sm:grid-cols-2">
                    <Field
                        label="Status"
                        value={
                            <StatusBadge
                                label={contact.status_label}
                                variant={contactStatusVariant(contact.status)}
                            />
                        }
                    />
                    <Field label="Job title" value={contact.job_title ?? '—'} />
                    <Field label="Email" value={contact.email ?? '—'} />
                    <Field label="Phone" value={contact.phone ?? '—'} />
                    <Field
                        label="Company"
                        value={
                            contact.company ? (
                                <Link
                                    href={showCompany.url(contact.company.id)}
                                    className="hover:underline"
                                >
                                    {contact.company.name}
                                </Link>
                            ) : (
                                '—'
                            )
                        }
                    />
                    <Field label="Owner" value={contact.owner?.name ?? '—'} />
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                    <h2 className="mb-4 text-sm font-semibold">
                        Deals (primary contact)
                    </h2>
                    {deals.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No deals yet.
                        </p>
                    ) : (
                        <ul className="divide-sidebar-border/70 dark:divide-sidebar-border divide-y">
                            {deals.map((deal) => (
                                <li
                                    key={deal.id}
                                    className="flex items-center justify-between gap-4 py-2"
                                >
                                    <Link
                                        href={showDeal.url(deal.id)}
                                        className="text-sm hover:underline"
                                    >
                                        {deal.title}
                                    </Link>
                                    <div className="flex items-center gap-2">
                                        <span className="text-muted-foreground text-xs">
                                            {formatMoney(deal.value_minor)}
                                        </span>
                                        <StatusBadge
                                            label={deal.stage_label}
                                            variant={dealStageVariant(
                                                deal.stage,
                                            )}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}

ContactsShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Contacts', href: index() },
        {
            title: `${props.contact.first_name} ${props.contact.last_name}`,
            href: '#',
        },
    ] satisfies BreadcrumbItem[],
});
