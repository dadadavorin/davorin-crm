import type { DragEndEvent } from '@dnd-kit/core';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { BoardMoveError, moveBoardCard } from '@/lib/board-move';
import type { BoardCard, BoardColumn } from '@/types/board';

/**
 * Drives one `<KanbanBoard>`: the card, wherever it's dropped, is applied to
 * local state immediately (optimistic), the move is posted to the server,
 * and a rejection reverts the local state back to what it was before the
 * drop and surfaces the server's reason as a toast.
 */
export function useKanbanBoard<TCard extends BoardCard>(
    entity: string,
    initialColumns: BoardColumn<TCard>[],
) {
    const [columns, setColumns] = useState(initialColumns);

    useEffect(() => {
        setColumns(initialColumns);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialColumns]);

    const findColumnIndex = (status: string): number =>
        columns.findIndex((column) => column.status === status);

    const findCardColumnStatus = (cardId: number): string | undefined =>
        columns.find((column) =>
            column.cards.some((card) => card.id === cardId),
        )?.status;

    const handleDragEnd = (event: DragEndEvent): void => {
        const { active, over } = event;

        if (!over || active.id === over.id) {
            return;
        }

        const activeId = Number(active.id);
        const sourceStatus = findCardColumnStatus(activeId);

        if (sourceStatus === undefined) {
            return;
        }

        // `over.id` is either another card's id (dropped near a card — its
        // column is the target) or a column's own status (dropped on empty
        // column space).
        const overCardStatus = findCardColumnStatus(Number(over.id));
        const targetStatus = overCardStatus ?? String(over.id);

        const previousColumns = columns;

        const sourceColumnIndex = findColumnIndex(sourceStatus);
        const targetColumnIndex = findColumnIndex(targetStatus);

        if (sourceColumnIndex === -1 || targetColumnIndex === -1) {
            return;
        }

        const movingCard = previousColumns[sourceColumnIndex].cards.find(
            (card) => card.id === activeId,
        );

        if (!movingCard) {
            return;
        }

        const cardsWithoutMoving = previousColumns[
            targetColumnIndex
        ].cards.filter((card) => card.id !== activeId);

        const overCardIndex = cardsWithoutMoving.findIndex(
            (card) => card.id === Number(over.id),
        );
        const insertAt =
            overCardIndex === -1 ? cardsWithoutMoving.length : overCardIndex;

        const nextTargetCards = [
            ...cardsWithoutMoving.slice(0, insertAt),
            movingCard,
            ...cardsWithoutMoving.slice(insertAt),
        ];

        const beforeId = nextTargetCards[insertAt - 1]?.id ?? null;
        const afterId = nextTargetCards[insertAt + 1]?.id ?? null;

        const nextColumns = columns.map((column, index) => {
            if (index === sourceColumnIndex && index === targetColumnIndex) {
                return { ...column, cards: nextTargetCards };
            }

            if (index === sourceColumnIndex) {
                return {
                    ...column,
                    cards: column.cards.filter((card) => card.id !== activeId),
                };
            }

            if (index === targetColumnIndex) {
                return { ...column, cards: nextTargetCards };
            }

            return column;
        });

        setColumns(nextColumns);

        moveBoardCard({
            entity,
            id: activeId,
            status: targetStatus,
            beforeId,
            afterId,
        }).catch((error: unknown) => {
            setColumns(previousColumns);

            toast.error(
                error instanceof BoardMoveError
                    ? error.message
                    : 'This move was rejected.',
            );
        });
    };

    return { columns, handleDragEnd };
}
