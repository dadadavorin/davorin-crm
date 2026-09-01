import { Head, Link } from '@inertiajs/react';
import { KanbanBoard } from '@/components/kanban-board';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/money';
import { dashboard } from '@/routes';
import { board, index, show } from '@/routes/quotes';
import type { BreadcrumbItem, QuoteBoardCard } from '@/types';
import type { BoardColumn } from '@/types/board';

type Props = {
    columns: BoardColumn<QuoteBoardCard>[];
};

export default function QuotesBoard({ columns }: Props) {
    return (
        <>
            <Head title="Quotes board" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Quotes board
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Drag a card to move it between statuses. Accepted,
                            Rejected and Expired quotes can only be reopened
                            from their detail page.
                        </p>
                    </div>
                    <Button asChild variant="secondary">
                        <Link href={index()}>List view</Link>
                    </Button>
                </div>

                <KanbanBoard
                    entity="quotes"
                    columns={columns}
                    renderCard={(quote) => (
                        <Link
                            href={show.url(quote.id)}
                            className="flex flex-col gap-1"
                        >
                            <span className="text-sm font-medium">
                                {quote.number}
                            </span>
                            <span className="text-muted-foreground text-xs">
                                {formatMoney(quote.total_minor)}
                            </span>
                            <span className="text-muted-foreground text-xs">
                                {quote.deal.title}
                            </span>
                            {quote.valid_until && (
                                <span className="text-muted-foreground text-xs">
                                    Valid until {quote.valid_until}
                                </span>
                            )}
                        </Link>
                    )}
                />
            </div>
        </>
    );
}

QuotesBoard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Quotes', href: index() },
        { title: 'Board', href: board() },
    ] satisfies BreadcrumbItem[],
};
