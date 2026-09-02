import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import {
    ResourceTable,
    type ResourceTableColumn,
} from '@/components/resource-table';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useRememberEntityView } from '@/hooks/use-remember-entity-view';
import { formatMoney } from '@/lib/money';
import { quoteStatusVariant } from '@/lib/quote-status';
import { board, create, index, show } from '@/routes/quotes';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, Paginated, Quote } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    quotes: Paginated<Quote>;
    filters: Filters;
};

const columns: ResourceTableColumn<Quote>[] = [
    {
        key: 'number',
        header: 'Number',
        sortable: true,
        render: (quote) => <span className="font-medium">{quote.number}</span>,
    },
    {
        key: 'status',
        header: 'Status',
        sortable: true,
        render: (quote) => (
            <StatusBadge
                label={quote.status_label}
                variant={quoteStatusVariant(quote.status)}
            />
        ),
    },
    {
        key: 'total_minor',
        header: 'Total',
        sortable: true,
        render: (quote) => formatMoney(quote.total_minor),
    },
    {
        key: 'deal',
        header: 'Deal',
        render: (quote) => quote.deal.title,
    },
    {
        key: 'valid_until',
        header: 'Valid until',
        sortable: true,
        render: (quote) => quote.valid_until ?? '—',
    },
    {
        key: 'owner',
        header: 'Owner',
        render: (quote) => quote.owner?.name ?? '—',
    },
];

export default function QuotesIndex({ quotes, filters }: Props) {
    useRememberEntityView('quotes', 'list');
    const [search, setSearch] = useState(filters.search);

    const buildSortHref = (field: string): string => {
        const direction =
            filters.sort === field && filters.direction === 'asc'
                ? 'desc'
                : 'asc';

        return index.url({
            query: { search: filters.search, sort: field, direction },
        });
    };

    const submitSearch = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.get(
            index.url({
                query: {
                    search,
                    sort: filters.sort,
                    direction: filters.direction,
                },
            }),
            {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Quotes" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="Quotes"
                        description="Every quote in the CRM, across every status."
                    />
                    <div className="flex items-center gap-2">
                        <Button asChild variant="secondary">
                            <Link href={board()}>Board view</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>New quote</Link>
                        </Button>
                    </div>
                </div>

                <form
                    onSubmit={submitSearch}
                    className="flex max-w-sm items-center gap-2"
                >
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search by number"
                        aria-label="Search quotes"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <ResourceTable
                    columns={columns}
                    rows={quotes.data}
                    rowKey={(quote) => quote.id}
                    rowHref={(quote) => show.url(quote.id)}
                    sort={{ field: filters.sort, direction: filters.direction }}
                    buildSortHref={buildSortHref}
                    emptyMessage="No quotes match your search."
                />

                {quotes.last_page > 1 && (
                    <nav className="flex flex-wrap items-center gap-1">
                        {quotes.links.map((link, linkIndex) =>
                            link.url === null ? (
                                <span
                                    key={`${link.label}-${linkIndex}`}
                                    className="text-muted-foreground/40 rounded-md px-3 py-1 text-sm"
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <Link
                                    key={`${link.label}-${linkIndex}`}
                                    href={link.url}
                                    preserveScroll
                                    className={
                                        link.active
                                            ? 'bg-primary text-primary-foreground rounded-md px-3 py-1 text-sm'
                                            : 'text-muted-foreground hover:text-foreground rounded-md px-3 py-1 text-sm'
                                    }
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </div>
        </>
    );
}

QuotesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Quotes', href: index() },
    ] satisfies BreadcrumbItem[],
};
