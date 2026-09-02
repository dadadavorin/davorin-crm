import {
    closestCenter,
    DndContext,
    PointerSensor,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { type ReactNode, useEffect, useRef } from 'react';
import { useKanbanBoard } from '@/hooks/use-kanban-board';
import type { BoardCard, BoardColumn } from '@/types/board';

type KanbanBoardProps<TCard extends BoardCard> = {
    entity: string;
    columns: BoardColumn<TCard>[];
    renderCard: (card: TCard) => ReactNode;
};

/**
 * One board, generic over whichever entity's cards it renders. Dragging a
 * card applies the new column and slot immediately (`useKanbanBoard`), posts
 * the move, and reverts with a visible reason if the server rejects it.
 * Every entity's board is this same component plus its own `renderCard`.
 */
export function KanbanBoard<TCard extends BoardCard>({
    entity,
    columns: initialColumns,
    renderCard,
}: KanbanBoardProps<TCard>) {
    const { columns, handleDragEnd } = useKanbanBoard(entity, initialColumns);
    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
    );

    // dnd-kit only suppresses the click that would otherwise land on the
    // pointerup target when a `DragOverlay` is used; this board moves cards
    // in place instead, so the browser's own click still fires once a drag
    // clears the distance threshold — landing wherever the pointer ends up,
    // not necessarily back on the card that was dragged, since the drop
    // reflows the column. A `click` listener registered on `document` in
    // the capture phase — ahead of every element the event could land on,
    // including ones outside this component's own tree — swallows the one
    // click that follows a real drag, however it's dispatched.
    //
    // Not every drag is followed by a click at all (e.g. the keyboard
    // sensor, or a pointer released outside the window), so the flag also
    // gets cleared on a short timer after drop — otherwise it would sit
    // `true` and swallow the next, unrelated click instead.
    const didDragRef = useRef(false);
    const resetTimeoutRef = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => {
        const swallowPostDragClick = (event: MouseEvent) => {
            if (!didDragRef.current) {
                return;
            }

            didDragRef.current = false;
            clearTimeout(resetTimeoutRef.current);
            event.preventDefault();
            event.stopPropagation();
        };

        document.addEventListener('click', swallowPostDragClick, true);

        return () => {
            document.removeEventListener('click', swallowPostDragClick, true);
            clearTimeout(resetTimeoutRef.current);
        };
    }, []);

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragStart={() => {
                didDragRef.current = true;
            }}
            onDragEnd={(event) => {
                handleDragEnd(event);
                resetTimeoutRef.current = setTimeout(() => {
                    didDragRef.current = false;
                }, 300);
            }}
        >
            <div className="flex h-full flex-1 gap-4 overflow-x-auto pb-4">
                {columns.map((column) => (
                    <KanbanColumn
                        key={column.status}
                        column={column}
                        renderCard={renderCard}
                    />
                ))}
            </div>
        </DndContext>
    );
}

type KanbanColumnProps<TCard extends BoardCard> = {
    column: BoardColumn<TCard>;
    renderCard: (card: TCard) => ReactNode;
};

function KanbanColumn<TCard extends BoardCard>({
    column,
    renderCard,
}: KanbanColumnProps<TCard>) {
    const { setNodeRef } = useDroppable({ id: column.status });

    return (
        <div className="flex w-72 shrink-0 flex-col gap-2">
            <div className="flex items-center justify-between px-1">
                <h3 className="text-sm font-semibold">{column.label}</h3>
                <span className="text-muted-foreground text-xs">
                    {column.total}
                </span>
            </div>

            <div
                ref={setNodeRef}
                className="bg-muted/30 border-sidebar-border/70 dark:border-sidebar-border flex min-h-24 flex-1 flex-col gap-2 rounded-xl border p-2"
            >
                <SortableContext
                    items={column.cards.map((card) => card.id)}
                    strategy={verticalListSortingStrategy}
                >
                    {column.cards.map((card) => (
                        <KanbanCard key={card.id} id={card.id}>
                            {renderCard(card)}
                        </KanbanCard>
                    ))}
                </SortableContext>

                {column.has_more && (
                    <p className="text-muted-foreground px-1 text-xs">
                        +{column.total - column.cards.length} more not shown
                    </p>
                )}
            </div>
        </div>
    );
}

type KanbanCardProps = {
    id: number;
    children: ReactNode;
};

function KanbanCard({ id, children }: KanbanCardProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    return (
        <div
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition: transition ?? undefined,
                opacity: isDragging ? 0.5 : 1,
            }}
            {...attributes}
            {...listeners}
            className="border-sidebar-border/70 dark:border-sidebar-border bg-background touch-none rounded-lg border p-3 shadow-sm"
        >
            {children}
        </div>
    );
}
