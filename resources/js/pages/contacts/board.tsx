import { Head, Link } from '@inertiajs/react';
import { KanbanBoard } from '@/components/kanban-board';
import { Button } from '@/components/ui/button';
import { useRememberEntityView } from '@/hooks/use-remember-entity-view';
import { dashboard } from '@/routes';
import { board, create, index, show } from '@/routes/contacts';
import type { BreadcrumbItem, ContactBoardCard } from '@/types';
import type { BoardColumn } from '@/types/board';

type Props = {
    columns: BoardColumn<ContactBoardCard>[];
};

export default function ContactsBoard({ columns }: Props) {
    useRememberEntityView('contacts', 'board');

    return (
        <>
            <Head title="Contacts board" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">
                            Contacts board
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Drag a card to move it between statuses.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button asChild variant="secondary">
                            <Link href={index()}>List view</Link>
                        </Button>
                        <Button asChild>
                            <Link href={create()}>New contact</Link>
                        </Button>
                    </div>
                </div>

                <KanbanBoard
                    entity="contacts"
                    columns={columns}
                    renderCard={(contact) => (
                        <Link
                            href={show.url(contact.id)}
                            className="flex flex-col gap-1"
                        >
                            <span className="text-sm font-medium">
                                {contact.first_name} {contact.last_name}
                            </span>
                            {contact.job_title && (
                                <span className="text-muted-foreground text-xs">
                                    {contact.job_title}
                                </span>
                            )}
                            <span className="text-muted-foreground text-xs">
                                {contact.company?.name ?? 'No company'}
                            </span>
                        </Link>
                    )}
                />
            </div>
        </>
    );
}

ContactsBoard.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Contacts', href: index() },
        { title: 'Board', href: board() },
    ] satisfies BreadcrumbItem[],
};
