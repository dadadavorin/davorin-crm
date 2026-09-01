import { Form, Head, Link } from '@inertiajs/react';
import AlertError from '@/components/alert-error';
import { ConfirmDelete } from '@/components/confirm-delete';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { QuoteItemEditor } from '@/components/quote-item-editor';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatMoney } from '@/lib/money';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/quotes';
import type { BreadcrumbItem, OwnerOption, Quote, StatusOption } from '@/types';

type Props = {
    quote: Quote;
    statuses: StatusOption[];
    owners: OwnerOption[];
};

export default function QuotesEdit({ quote, statuses, owners }: Props) {
    return (
        <>
            <Head title={`Edit ${quote.number}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title={`Edit ${quote.number}`}
                        description="Update this quote's details."
                    />
                    <ConfirmDelete
                        href={QuoteController.destroy(quote.id).url}
                        title="Delete this quote?"
                        description={`This soft-deletes ${quote.number}. It can be recovered by direct database access, but it will disappear from every listing immediately.`}
                    />
                </div>

                <div className="max-w-3xl">
                    <Form
                        {...QuoteController.update.form(quote.id)}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => {
                            const fieldErrors = errors as Record<
                                string,
                                string
                            >;
                            const topLevelErrors = [
                                fieldErrors.quote_not_editable,
                                fieldErrors.illegal_status_transition,
                            ].filter((message): message is string =>
                                Boolean(message),
                            );

                            return (
                                <>
                                    {topLevelErrors.length > 0 && (
                                        <AlertError errors={topLevelErrors} />
                                    )}

                                    <div className="grid gap-2">
                                        <Label>Deal</Label>
                                        <p className="text-sm">
                                            {quote.deal.title}
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="issue_date">
                                                Issue date
                                            </Label>
                                            <Input
                                                id="issue_date"
                                                name="issue_date"
                                                type="date"
                                                defaultValue={
                                                    quote.issue_date ??
                                                    undefined
                                                }
                                                required
                                                aria-invalid={Boolean(
                                                    fieldErrors.issue_date,
                                                )}
                                            />
                                            <InputError
                                                message={fieldErrors.issue_date}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="valid_until">
                                                Valid until
                                            </Label>
                                            <Input
                                                id="valid_until"
                                                name="valid_until"
                                                type="date"
                                                defaultValue={
                                                    quote.valid_until ??
                                                    undefined
                                                }
                                                required
                                                aria-invalid={Boolean(
                                                    fieldErrors.valid_until,
                                                )}
                                            />
                                            <InputError
                                                message={
                                                    fieldErrors.valid_until
                                                }
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="tax_rate">
                                                Tax rate
                                            </Label>
                                            <Input
                                                id="tax_rate"
                                                name="tax_rate"
                                                defaultValue={quote.tax_rate}
                                                disabled={!quote.is_draft}
                                                required
                                                aria-invalid={Boolean(
                                                    fieldErrors.tax_rate,
                                                )}
                                            />
                                            <InputError
                                                message={fieldErrors.tax_rate}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="status">
                                                Status
                                            </Label>
                                            <Select
                                                name="status"
                                                defaultValue={quote.status}
                                            >
                                                <SelectTrigger
                                                    id="status"
                                                    className="w-full"
                                                    aria-invalid={Boolean(
                                                        fieldErrors.status,
                                                    )}
                                                >
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {statuses.map((status) => (
                                                        <SelectItem
                                                            key={status.value}
                                                            value={status.value}
                                                        >
                                                            {status.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={fieldErrors.status}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="owner_id">Owner</Label>
                                        <Select
                                            name="owner_id"
                                            defaultValue={
                                                quote.owner
                                                    ? String(quote.owner.id)
                                                    : undefined
                                            }
                                        >
                                            <SelectTrigger
                                                id="owner_id"
                                                className="w-full"
                                                aria-invalid={Boolean(
                                                    fieldErrors.owner_id,
                                                )}
                                            >
                                                <SelectValue placeholder="Assign an owner" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {owners.map((owner) => (
                                                    <SelectItem
                                                        key={owner.id}
                                                        value={String(owner.id)}
                                                    >
                                                        {owner.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={fieldErrors.owner_id}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Line items</Label>
                                        {quote.is_draft ? (
                                            <QuoteItemEditor
                                                items={quote.items}
                                                errors={fieldErrors}
                                            />
                                        ) : (
                                            <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                                                <table className="w-full text-sm">
                                                    <thead>
                                                        <tr className="border-sidebar-border/70 dark:border-sidebar-border border-b">
                                                            <th className="text-muted-foreground px-3 py-2 text-left font-medium">
                                                                Description
                                                            </th>
                                                            <th className="text-muted-foreground px-3 py-2 text-left font-medium">
                                                                Qty
                                                            </th>
                                                            <th className="text-muted-foreground px-3 py-2 text-left font-medium">
                                                                Unit price
                                                            </th>
                                                            <th className="text-muted-foreground px-3 py-2 text-left font-medium">
                                                                Line total
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {quote.items.map(
                                                            (item) => (
                                                                <tr
                                                                    key={
                                                                        item.id
                                                                    }
                                                                    className="border-sidebar-border/70 dark:border-sidebar-border border-b last:border-0"
                                                                >
                                                                    <td className="px-3 py-2">
                                                                        {
                                                                            item.description
                                                                        }
                                                                    </td>
                                                                    <td className="px-3 py-2">
                                                                        {
                                                                            item.quantity
                                                                        }
                                                                    </td>
                                                                    <td className="px-3 py-2">
                                                                        {formatMoney(
                                                                            item.unit_price_minor,
                                                                        )}
                                                                    </td>
                                                                    <td className="px-3 py-2">
                                                                        {formatMoney(
                                                                            item.line_total_minor,
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            ),
                                                        )}
                                                    </tbody>
                                                </table>
                                                <p className="text-muted-foreground px-3 py-2 text-xs">
                                                    Line items are frozen once a
                                                    quote is no longer a draft.
                                                </p>
                                            </div>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">Notes</Label>
                                        <Textarea
                                            id="notes"
                                            name="notes"
                                            defaultValue={
                                                quote.notes ?? undefined
                                            }
                                            aria-invalid={Boolean(
                                                fieldErrors.notes,
                                            )}
                                        />
                                        <InputError
                                            message={fieldErrors.notes}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="terms">Terms</Label>
                                        <Textarea
                                            id="terms"
                                            name="terms"
                                            defaultValue={
                                                quote.terms ?? undefined
                                            }
                                            aria-invalid={Boolean(
                                                fieldErrors.terms,
                                            )}
                                        />
                                        <InputError
                                            message={fieldErrors.terms}
                                        />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <Button disabled={processing}>
                                            Save changes
                                        </Button>
                                        <Link
                                            href={show.url(quote.id)}
                                            className="text-muted-foreground text-sm hover:underline"
                                        >
                                            Cancel
                                        </Link>
                                    </div>
                                </>
                            );
                        }}
                    </Form>
                </div>
            </div>
        </>
    );
}

QuotesEdit.layout = (props: Props) => ({
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Quotes', href: index() },
        { title: props.quote.number, href: show.url(props.quote.id) },
        { title: 'Edit', href: '#' },
    ] satisfies BreadcrumbItem[],
});
