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
import { type MutableRefObject, type ReactNode, useRef } from 'react';
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
    // in place instead, so the browser's own click still fires on whatever
    // is under the pointer once a drag clears the distance threshold — e.g.
    // the card's `Link`, opening it right after the drop. Track whether a
    // drag actually started and swallow the one click that follows it.
    const didDragRef = useRef(false);

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragStart={() => {
                didDragRef.current = true;
            }}
            onDragEnd={(event) => {
                handleDragEnd(event);
            }}
        >
            <div className="flex h-full flex-1 gap-4 overflow-x-auto pb-4">
                {columns.map((column) => (
                    <KanbanColumn
                        key={column.status}
                        column={column}
                        renderCard={renderCard}
                        didDragRef={didDragRef}
                    />
                ))}
            </div>
        </DndContext>
    );
}

type KanbanColumnProps<TCard extends BoardCard> = {
    column: BoardColumn<TCard>;
    renderCard: (card: TCard) => ReactNode;
    didDragRef: MutableRefObject<boolean>;
};

function KanbanColumn<TCard extends BoardCard>({
    column,
    renderCard,
    didDragRef,
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
                        <KanbanCard
                            key={card.id}
                            id={card.id}
                            didDragRef={didDragRef}
                        >
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
    didDragRef: MutableRefObject<boolean>;
};

function KanbanCard({ id, children, didDragRef }: KanbanCardProps) {
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
            onClickCapture={(event) => {
                if (didDragRef.current) {
                    event.preventDefault();
                    event.stopPropagation();
                    didDragRef.current = false;
                }
            }}
            className="border-sidebar-border/70 dark:border-sidebar-border bg-background touch-none rounded-lg border p-3 shadow-sm"
        >
            {children}
        </div>
    );
}
