import { Head, Link } from '@inertiajs/react';
import { KanbanBoard } from '@/components/kanban-board';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { board, index, show } from '@/routes/companies';
import type { BreadcrumbItem, CompanyBoardCard } from '@/types';
import type { BoardColumn } from '@/types/board';

type Props = {
    columns: BoardColumn<CompanyBoardCard>[];
};

export default function CompaniesBoard({ columns }: Props) {
    return (
        <>
            <Head title="Companies board" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Companies board
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Drag a card to move it between statuses.
                        </p>
                    </div>
                    <Button asChild variant="secondary">
                        <Link href={index()}>List view</Link>
                    </Button>
                </div>

                <KanbanBoard
                    entity="companies"
                    columns={columns}
                    renderCard={(company) => (
                        <Link
                            href={show.url(company.id)}
                            className="flex flex-col gap-1"
                        >
                            <span className="text-sm font-medium">
                                {company.name}
                            </span>
                            {company.industry && (
                                <span className="text-muted-foreground text-xs">
                                    {company.industry}
                                </span>
                            )}
                            <span className="text-muted-foreground text-xs">
                                {company.owner?.name ?? 'Unassigned'}
                            </span>
                        </Link>
                    )}
                />
            </div>
        </>
    );
}

CompaniesBoard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Companies', href: index() },
        { title: 'Board', href: board() },
    ] satisfies BreadcrumbItem[],
};
