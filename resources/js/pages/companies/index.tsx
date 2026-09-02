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
import { companyStatusVariant } from '@/lib/company-status';
import { board, create, index, show } from '@/routes/companies';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, Company, Paginated } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    companies: Paginated<Company>;
    filters: Filters;
};

const columns: ResourceTableColumn<Company>[] = [
    {
        key: 'name',
        header: 'Name',
        sortable: true,
        render: (company) => (
            <span className="font-medium">{company.name}</span>
        ),
    },
    {
        key: 'status',
        header: 'Status',
        sortable: true,
        render: (company) => (
            <StatusBadge
                label={company.status_label}
                variant={companyStatusVariant(company.status)}
            />
        ),
    },
    {
        key: 'industry',
        header: 'Industry',
        sortable: true,
        render: (company) => company.industry ?? '—',
    },
    {
        key: 'owner',
        header: 'Owner',
        render: (company) => company.owner?.name ?? '—',
    },
];

export default function CompaniesIndex({ companies, filters }: Props) {
    useRememberEntityView('companies', 'list');
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
            <Head title="Companies" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="Companies"
                        description="Every company in the CRM, across every status."
                    />
                    <div className="flex items-center gap-2">
                        <Button asChild variant="secondary">
                            <Link href={board()}>Board view</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>New company</Link>
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
                        placeholder="Search by name"
                        aria-label="Search companies"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <ResourceTable
                    columns={columns}
                    rows={companies.data}
                    rowKey={(company) => company.id}
                    rowHref={(company) => show.url(company.id)}
                    sort={{ field: filters.sort, direction: filters.direction }}
                    buildSortHref={buildSortHref}
                    emptyMessage="No companies match your search."
                />

                {companies.last_page > 1 && (
                    <nav className="flex flex-wrap items-center gap-1">
                        {companies.links.map((link, linkIndex) =>
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

CompaniesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Companies', href: index() },
    ] satisfies BreadcrumbItem[],
};
