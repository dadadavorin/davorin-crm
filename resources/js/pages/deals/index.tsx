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
import { dealStageVariant } from '@/lib/deal-stage';
import { formatMoney } from '@/lib/money';
import { board, create, index, show } from '@/routes/deals';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, Deal, Paginated } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    deals: Paginated<Deal>;
    filters: Filters;
};

const columns: ResourceTableColumn<Deal>[] = [
    {
        key: 'title',
        header: 'Title',
        sortable: true,
        render: (deal) => <span className="font-medium">{deal.title}</span>,
    },
    {
        key: 'stage',
        header: 'Stage',
        sortable: true,
        render: (deal) => (
            <StatusBadge
                label={deal.stage_label}
                variant={dealStageVariant(deal.stage)}
            />
        ),
    },
    {
        key: 'value_minor',
        header: 'Value',
        sortable: true,
        render: (deal) => formatMoney(deal.value_minor),
    },
    {
        key: 'company',
        header: 'Company',
        render: (deal) => deal.company.name,
    },
    {
        key: 'expected_close_date',
        header: 'Expected close',
        sortable: true,
        render: (deal) => deal.expected_close_date ?? '—',
    },
    {
        key: 'owner',
        header: 'Owner',
        render: (deal) => deal.owner?.name ?? '—',
    },
];

export default function DealsIndex({ deals, filters }: Props) {
    useRememberEntityView('deals', 'list');
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
            <Head title="Deals" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="Deals"
                        description="Every deal in the CRM, across every stage."
                    />
                    <div className="flex items-center gap-2">
                        <Button asChild variant="secondary">
                            <Link href={board()}>Board view</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>New deal</Link>
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
                        placeholder="Search by title"
                        aria-label="Search deals"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <ResourceTable
                    columns={columns}
                    rows={deals.data}
                    rowKey={(deal) => deal.id}
                    rowHref={(deal) => show.url(deal.id)}
                    sort={{ field: filters.sort, direction: filters.direction }}
                    buildSortHref={buildSortHref}
                    emptyMessage="No deals match your search."
                />

                {deals.last_page > 1 && (
                    <nav className="flex flex-wrap items-center gap-1">
                        {deals.links.map((link, linkIndex) =>
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

DealsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Deals', href: index() },
    ] satisfies BreadcrumbItem[],
};
