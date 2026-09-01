import { Form, Head, Link } from '@inertiajs/react';
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
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import { dashboard } from '@/routes';
import { index } from '@/routes/quotes';
import type { BreadcrumbItem, DealOption, OwnerOption } from '@/types';

type Props = {
    deals: DealOption[];
    owners: OwnerOption[];
    defaults: {
        issue_date: string;
        valid_until: string;
        tax_rate: string;
    };
};

export default function QuotesCreate({ deals, owners, defaults }: Props) {
    return (
        <>
            <Head title="New quote" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="New quote"
                    description="Create a standalone quote for a deal."
                />

                <div className="max-w-3xl">
                    <Form
                        {...QuoteController.store.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => {
                            const fieldErrors = errors as Record<
                                string,
                                string
                            >;

                            return (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="deal_id">Deal</Label>
                                        <Select name="deal_id">
                                            <SelectTrigger
                                                id="deal_id"
                                                className="w-full"
                                                aria-invalid={Boolean(
                                                    fieldErrors.deal_id,
                                                )}
                                            >
                                                <SelectValue placeholder="Select a deal" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {deals.map((deal) => (
                                                    <SelectItem
                                                        key={deal.id}
                                                        value={String(deal.id)}
                                                    >
                                                        {deal.title} —{' '}
                                                        {deal.company_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={fieldErrors.deal_id}
                                        />
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
                                                    defaults.issue_date
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
                                                    defaults.valid_until
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

                                    <div className="grid max-w-xs gap-2">
                                        <Label htmlFor="tax_rate">
                                            Tax rate
                                        </Label>
                                        <Input
                                            id="tax_rate"
                                            name="tax_rate"
                                            defaultValue={defaults.tax_rate}
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
                                        <Label htmlFor="owner_id">Owner</Label>
                                        <Select name="owner_id">
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
                                        <QuoteItemEditor
                                            items={[]}
                                            errors={fieldErrors}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="notes">Notes</Label>
                                        <Textarea
                                            id="notes"
                                            name="notes"
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
                                            Create quote
                                        </Button>
                                        <Link
                                            href={index.url()}
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

QuotesCreate.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Quotes', href: index() },
        { title: 'New', href: '#' },
    ] satisfies BreadcrumbItem[],
};
