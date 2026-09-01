import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ConfirmDelete } from '@/components/confirm-delete';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { dealStageVariant } from '@/lib/deal-stage';
import { formatMoney } from '@/lib/money';
import { quoteStatusVariant } from '@/lib/quote-status';
import DealController from '@/actions/App/Http/Controllers/DealController';
import { show as showCompany } from '@/routes/companies';
import { show as showContact } from '@/routes/contacts';
import { edit as editQuote } from '@/routes/quotes';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/deals';
import type { BreadcrumbItem, Deal, DealQuote } from '@/types';

type Props = {
    deal: Deal;
    quotes: DealQuote[];
};

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="space-y-0.5">
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="text-sm">{value}</p>
        </div>
    );
}

export default function DealsShow({ deal, quotes }: Props) {
    const reopen = () => {
        router.post(
            DealController.reopen(deal.id).url,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={deal.title} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading title={deal.title} description="Deal details." />
                    <div className="flex items-center gap-2">
                        {deal.is_terminal && (
                            <Button variant="secondary" onClick={reopen}>
                                Reopen
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href={edit.url(deal.id)}>Edit</Link>
                        </Button>
                        <ConfirmDelete
                            href={DealController.destroy(deal.id).url}
                            title="Delete this deal?"
                            description={`This soft-deletes ${deal.title}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                        />
                    </div>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-6 sm:grid-cols-2">
                    <Field
                        label="Stage"
                        value={
                            <StatusBadge
                                label={deal.stage_label}
                                variant={dealStageVariant(deal.stage)}
                            />
                        }
                    />
                    <Field
                        label="Value"
                        value={formatMoney(deal.value_minor)}
                    />
                    <Field
                        label="Expected close"
                        value={deal.expected_close_date ?? '—'}
                    />
                    <Field
                        label="Company"
                        value={
                            <Link
                                href={showCompany.url(deal.company.id)}
                                className="hover:underline"
                            >
                                {deal.company.name}
                            </Link>
                        }
                    />
                    <Field
                        label="Primary contact"
                        value={
                            deal.primary_contact ? (
                                <Link
                                    href={showContact.url(
                                        deal.primary_contact.id,
                                    )}
                                    className="hover:underline"
                                >
                                    {deal.primary_contact.name}
                                </Link>
                            ) : (
                                '—'
                            )
                        }
                    />
                    <Field label="Owner" value={deal.owner?.name ?? '—'} />
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                    <h2 className="mb-4 text-sm font-semibold">Quotes</h2>
                    {quotes.length === 0 ? (
                        <p className="text-muted-foreground text-sm">
                            No quotes yet.
                        </p>
                    ) : (
                        <ul className="divide-sidebar-border/70 dark:divide-sidebar-border divide-y">
                            {quotes.map((quote) => (
                                <li
                                    key={quote.id}
                                    className="flex items-center justify-between gap-4 py-2"
                                >
                                    <Link
                                        href={editQuote.url(quote.id)}
                                        className="text-sm hover:underline"
                                    >
                                        {quote.number}
                                    </Link>
                                    <div className="flex items-center gap-2">
                                        <span className="text-muted-foreground text-xs">
                                            {formatMoney(quote.total_minor)}
                                        </span>
                                        <StatusBadge
                                            label={quote.status_label}
                                            variant={quoteStatusVariant(
                                                quote.status,
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

DealsShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Deals', href: index() },
        { title: props.deal.title, href: '#' },
    ] satisfies BreadcrumbItem[],
});
