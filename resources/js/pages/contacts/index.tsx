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
import { contactStatusVariant } from '@/lib/contact-status';
import { board, create, index, show } from '@/routes/contacts';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, Contact, Paginated } from '@/types';

type Filters = {
    search: string;
    sort: string;
    direction: 'asc' | 'desc';
};

type Props = {
    contacts: Paginated<Contact>;
    filters: Filters;
};

const columns: ResourceTableColumn<Contact>[] = [
    {
        key: 'last_name',
        header: 'Name',
        sortable: true,
        render: (contact) => (
            <span className="font-medium">
                {contact.first_name} {contact.last_name}
            </span>
        ),
    },
    {
        key: 'status',
        header: 'Status',
        sortable: true,
        render: (contact) => (
            <StatusBadge
                label={contact.status_label}
                variant={contactStatusVariant(contact.status)}
            />
        ),
    },
    {
        key: 'job_title',
        header: 'Job title',
        sortable: true,
        render: (contact) => contact.job_title ?? '—',
    },
    {
        key: 'company',
        header: 'Company',
        render: (contact) => contact.company?.name ?? '—',
    },
    {
        key: 'owner',
        header: 'Owner',
        render: (contact) => contact.owner?.name ?? '—',
    },
];

export default function ContactsIndex({ contacts, filters }: Props) {
    useRememberEntityView('contacts', 'list');
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
            <Head title="Contacts" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        title="Contacts"
                        description="Every contact in the CRM, across every status."
                    />
                    <div className="flex items-center gap-2">
                        <Button asChild variant="secondary">
                            <Link href={board()}>Board view</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>New contact</Link>
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
                        aria-label="Search contacts"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                <ResourceTable
                    columns={columns}
                    rows={contacts.data}
                    rowKey={(contact) => contact.id}
                    rowHref={(contact) => show.url(contact.id)}
                    sort={{ field: filters.sort, direction: filters.direction }}
                    buildSortHref={buildSortHref}
                    emptyMessage="No contacts match your search."
                />

                {contacts.last_page > 1 && (
                    <nav className="flex flex-wrap items-center gap-1">
                        {contacts.links.map((link, linkIndex) =>
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

ContactsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Contacts', href: index() },
    ] satisfies BreadcrumbItem[],
};
