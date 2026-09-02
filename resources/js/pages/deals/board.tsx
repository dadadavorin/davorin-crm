import { Head, Link } from '@inertiajs/react';
import { CreateQuoteFromDealDialog } from '@/components/create-quote-from-deal-dialog';
import { KanbanBoard } from '@/components/kanban-board';
import { Button } from '@/components/ui/button';
import { useRememberEntityView } from '@/hooks/use-remember-entity-view';
import { formatMoney } from '@/lib/money';
import { dashboard } from '@/routes';
import { board, create, index, show } from '@/routes/deals';
import type { BreadcrumbItem, DealBoardCard, QuoteDefaults } from '@/types';
import type { BoardColumn } from '@/types/board';

type Props = {
    columns: BoardColumn<DealBoardCard>[];
    quoteDefaults: QuoteDefaults;
};

export default function DealsBoard({ columns, quoteDefaults }: Props) {
    useRememberEntityView('deals', 'board');

    return (
        <>
            <Head title="Deals board" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Deals board
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Drag a card to move it between stages. Won and Lost
                            deals can only be reopened from their detail page.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="secondary">
                            <Link href={index()}>List view</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>New deal</Link>
                        </Button>
                    </div>
                </div>

                <KanbanBoard
                    entity="deals"
                    columns={columns}
                    renderCard={(deal) => (
                        <div className="flex flex-col gap-2">
                            <Link
                                href={show.url(deal.id)}
                                className="flex flex-col gap-1"
                            >
                                <span className="text-sm font-medium">
                                    {deal.title}
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    {formatMoney(deal.value_minor)}
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    {deal.company.name}
                                </span>
                                {deal.primary_contact && (
                                    <span className="text-muted-foreground text-xs">
                                        {deal.primary_contact.name}
                                    </span>
                                )}
                            </Link>
                            <CreateQuoteFromDealDialog
                                dealId={deal.id}
                                defaults={quoteDefaults}
                            />
                        </div>
                    )}
                />
            </div>
        </>
    );
}

DealsBoard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Deals', href: index() },
        { title: 'Board', href: board() },
    ] satisfies BreadcrumbItem[],
};
