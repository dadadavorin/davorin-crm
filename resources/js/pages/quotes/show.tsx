import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { ConfirmDelete } from '@/components/confirm-delete';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/money';
import { quoteStatusVariant } from '@/lib/quote-status';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import { show as showDeal } from '@/routes/deals';
import { dashboard } from '@/routes';
import { edit, index } from '@/routes/quotes';
import type { BreadcrumbItem, Quote } from '@/types';

type Props = {
    quote: Quote;
};

function Field({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="space-y-0.5">
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="text-sm">{value}</p>
        </div>
    );
}

export default function QuotesShow({ quote }: Props) {
    const reopen = () => {
        router.post(
            QuoteController.reopen(quote.id).url,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={quote.number} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={quote.number}
                        description="Quote details."
                    />
                    <div className="flex items-center gap-2">
                        {quote.is_terminal && (
                            <Button variant="secondary" onClick={reopen}>
                                Reopen
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <a href={QuoteController.pdf(quote.id).url}>
                                Download PDF
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={edit.url(quote.id)}>Edit</Link>
                        </Button>
                        <ConfirmDelete
                            href={QuoteController.destroy(quote.id).url}
                            title="Delete this quote?"
                            description={`This soft-deletes ${quote.number}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                        />
                    </div>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Field
                        label="Status"
                        value={
                            <StatusBadge
                                label={quote.status_label}
                                variant={quoteStatusVariant(quote.status)}
                            />
                        }
                    />
                    <Field
                        label="Deal"
                        value={
                            <Link
                                href={showDeal.url(quote.deal.id)}
                                className="hover:underline"
                            >
                                {quote.deal.title}
                            </Link>
                        }
                    />
                    <Field label="Owner" value={quote.owner?.name ?? '—'} />
                    <Field label="Issue date" value={quote.issue_date ?? '—'} />
                    <Field
                        label="Valid until"
                        value={quote.valid_until ?? '—'}
                    />
                    <Field label="Tax rate" value={quote.tax_rate} />
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-6 sm:grid-cols-2 lg:grid-cols-4">
                    <Field label="Bill to" value={quote.bill_to_company_name} />
                    <Field
                        label="Address"
                        value={quote.bill_to_address ?? '—'}
                    />
                    <Field
                        label="Contact"
                        value={quote.bill_to_contact_name ?? '—'}
                    />
                    <Field
                        label="Contact email"
                        value={quote.bill_to_contact_email ?? '—'}
                    />
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-sidebar-border/70 dark:border-sidebar-border border-b">
                                <th className="text-muted-foreground px-4 py-2 text-left font-medium">
                                    Description
                                </th>
                                <th className="text-muted-foreground px-4 py-2 text-left font-medium">
                                    Qty
                                </th>
                                <th className="text-muted-foreground px-4 py-2 text-left font-medium">
                                    Unit price
                                </th>
                                <th className="text-muted-foreground px-4 py-2 text-left font-medium">
                                    Line total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {quote.items.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        No line items.
                                    </td>
                                </tr>
                            ) : (
                                quote.items.map((item) => (
                                    <tr
                                        key={item.id}
                                        className="border-sidebar-border/70 dark:border-sidebar-border border-b last:border-0"
                                    >
                                        <td className="px-4 py-2">
                                            {item.description}
                                        </td>
                                        <td className="px-4 py-2">
                                            {item.quantity}
                                        </td>
                                        <td className="px-4 py-2">
                                            {formatMoney(item.unit_price_minor)}
                                        </td>
                                        <td className="px-4 py-2">
                                            {formatMoney(item.line_total_minor)}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                        <tfoot>
                            <tr className="border-sidebar-border/70 dark:border-sidebar-border border-t">
                                <td
                                    colSpan={3}
                                    className="px-4 py-2 text-right font-medium"
                                >
                                    Subtotal
                                </td>
                                <td className="px-4 py-2">
                                    {formatMoney(quote.subtotal_minor)}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    colSpan={3}
                                    className="px-4 py-2 text-right font-medium"
                                >
                                    Tax
                                </td>
                                <td className="px-4 py-2">
                                    {formatMoney(quote.tax_minor)}
                                </td>
                            </tr>
                            <tr>
                                <td
                                    colSpan={3}
                                    className="px-4 py-2 text-right font-semibold"
                                >
                                    Total
                                </td>
                                <td className="px-4 py-2 font-semibold">
                                    {formatMoney(quote.total_minor)}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {(quote.notes || quote.terms) && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border grid gap-6 rounded-xl border p-6 sm:grid-cols-2">
                        {quote.notes && (
                            <Field label="Notes" value={quote.notes} />
                        )}
                        {quote.terms && (
                            <Field label="Terms" value={quote.terms} />
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

QuotesShow.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Quotes', href: index() },
        { title: props.quote.number, href: '#' },
    ] satisfies BreadcrumbItem[],
});
