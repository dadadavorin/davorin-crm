import { Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type ResourceTableColumn<T> = {
    key: string;
    header: string;
    sortable?: boolean;
    className?: string;
    render: (row: T) => ReactNode;
};

export type ResourceTableSort = {
    field: string;
    direction: 'asc' | 'desc';
};

type ResourceTableProps<T> = {
    columns: ResourceTableColumn<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    rowHref?: (row: T) => string;
    sort?: ResourceTableSort;
    buildSortHref?: (field: string) => string;
    emptyMessage?: string;
};

/**
 * A column-config-driven table: index screens for every entity pass their
 * own columns and get sorting links, an empty state and row navigation for
 * free. Presentation only — the caller owns filtering, pagination and what
 * a sort click actually requests.
 */
export function ResourceTable<T>({
    columns,
    rows,
    rowKey,
    rowHref,
    sort,
    buildSortHref,
    emptyMessage = 'No records found.',
}: ResourceTableProps<T>) {
    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-sidebar-border/70 dark:border-sidebar-border border-b">
                        {columns.map((column) => (
                            <th
                                key={column.key}
                                scope="col"
                                className={cn(
                                    'text-muted-foreground px-4 py-2 text-left font-medium',
                                    column.className,
                                )}
                            >
                                {column.sortable && buildSortHref ? (
                                    <Link
                                        href={buildSortHref(column.key)}
                                        className="hover:text-foreground inline-flex items-center gap-1"
                                    >
                                        {column.header}
                                        {sort?.field === column.key ? (
                                            sort.direction === 'asc' ? (
                                                <ArrowUp className="size-3.5" />
                                            ) : (
                                                <ArrowDown className="size-3.5" />
                                            )
                                        ) : (
                                            <ArrowUpDown className="size-3.5 opacity-40" />
                                        )}
                                    </Link>
                                ) : (
                                    column.header
                                )}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 ? (
                        <tr>
                            <td
                                colSpan={columns.length}
                                className="text-muted-foreground px-4 py-8 text-center"
                            >
                                {emptyMessage}
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => (
                            <tr
                                key={rowKey(row)}
                                onClick={
                                    rowHref
                                        ? () => router.visit(rowHref(row))
                                        : undefined
                                }
                                className={cn(
                                    'border-sidebar-border/70 dark:border-sidebar-border border-b last:border-0',
                                    rowHref &&
                                        'hover:bg-accent/50 cursor-pointer',
                                )}
                            >
                                {columns.map((column) => (
                                    <td
                                        key={column.key}
                                        className={cn(
                                            'px-4 py-2',
                                            column.className,
                                        )}
                                    >
                                        {column.render(row)}
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
