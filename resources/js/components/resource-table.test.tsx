import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';

afterEach(() => {
    cleanup();
});

const { visit } = vi.hoisted(() => ({ visit: vi.fn() }));

vi.mock('@inertiajs/react', async () => {
    const actual =
        await vi.importActual<typeof import('@inertiajs/react')>(
            '@inertiajs/react',
        );

    return {
        ...actual,
        router: { visit },
        Link: ({
            href,
            children,
            ...rest
        }: {
            href: unknown;
            children: React.ReactNode;
        }) => (
            <a href={typeof href === 'string' ? href : '#'} {...rest}>
                {children}
            </a>
        ),
    };
});

import {
    ResourceTable,
    type ResourceTableColumn,
} from '@/components/resource-table';

type Row = { id: number; name: string };

const columns: ResourceTableColumn<Row>[] = [
    { key: 'name', header: 'Name', sortable: true, render: (row) => row.name },
];

describe('ResourceTable', () => {
    it('renders a row for every record', () => {
        render(
            <ResourceTable
                columns={columns}
                rows={[
                    { id: 1, name: 'Acme Corp' },
                    { id: 2, name: 'Globex Corp' },
                ]}
                rowKey={(row) => row.id}
            />,
        );

        expect(screen.getByText('Acme Corp')).toBeInTheDocument();
        expect(screen.getByText('Globex Corp')).toBeInTheDocument();
    });

    it('shows the empty message when there are no rows', () => {
        render(
            <ResourceTable
                columns={columns}
                rows={[]}
                rowKey={(row) => row.id}
                emptyMessage="No companies match your search."
            />,
        );

        expect(
            screen.getByText('No companies match your search.'),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole('row', { name: /acme/i }),
        ).not.toBeInTheDocument();
    });

    it('renders a sort link for a sortable column', () => {
        render(
            <ResourceTable
                columns={columns}
                rows={[{ id: 1, name: 'Acme Corp' }]}
                rowKey={(row) => row.id}
                sort={{ field: 'name', direction: 'asc' }}
                buildSortHref={(field) =>
                    `/companies?sort=${field}&direction=desc`
                }
            />,
        );

        expect(screen.getByRole('link', { name: /name/i })).toHaveAttribute(
            'href',
            '/companies?sort=name&direction=desc',
        );
    });

    it('navigates to the row href when a row is clicked', async () => {
        const user = userEvent.setup();
        visit.mockClear();

        render(
            <ResourceTable
                columns={columns}
                rows={[{ id: 1, name: 'Acme Corp' }]}
                rowKey={(row) => row.id}
                rowHref={(row) => `/companies/${row.id}`}
            />,
        );

        await user.click(screen.getByText('Acme Corp'));

        expect(visit).toHaveBeenCalledWith('/companies/1');
    });
});
