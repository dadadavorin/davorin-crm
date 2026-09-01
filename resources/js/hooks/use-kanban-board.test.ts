import { act, renderHook } from '@testing-library/react';
import type { DragEndEvent } from '@dnd-kit/core';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';

const { toastError } = vi.hoisted(() => ({ toastError: vi.fn() }));

vi.mock('sonner', () => ({
    toast: { error: toastError },
}));

import { useKanbanBoard } from '@/hooks/use-kanban-board';
import type { BoardColumn } from '@/types/board';

type Card = { id: number; position: string; name: string };

function makeColumns(): BoardColumn<Card>[] {
    return [
        {
            status: 'lead',
            label: 'Lead',
            total: 1,
            has_more: false,
            cards: [{ id: 1, position: '1024', name: 'Acme' }],
        },
        {
            status: 'prospect',
            label: 'Prospect',
            total: 1,
            has_more: false,
            cards: [{ id: 2, position: '1024', name: 'Globex' }],
        },
    ];
}

function dragEvent(activeId: number, overId: number | string): DragEndEvent {
    return {
        active: {
            id: activeId,
            data: { current: undefined },
            rect: {} as never,
        },
        over: {
            id: overId,
            data: { current: undefined },
            rect: {} as never,
            disabled: false,
        },
        collisions: null,
        delta: { x: 0, y: 0 },
        activatorEvent: new Event('pointerdown'),
    } as unknown as DragEndEvent;
}

afterEach(() => {
    vi.unstubAllGlobals();
    toastError.mockClear();
});

describe('useKanbanBoard', () => {
    it('applies the move optimistically before the request resolves', () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve(new Response(null, { status: 204 }))),
        );

        const initialColumns = makeColumns();
        const { result } = renderHook(() =>
            useKanbanBoard('companies', initialColumns),
        );

        act(() => {
            result.current.handleDragEnd(dragEvent(1, 'prospect'));
        });

        expect(result.current.columns[0].cards).toHaveLength(0);
        expect(result.current.columns[1].cards.map((card) => card.id)).toEqual([
            2, 1,
        ]);
    });

    it('reverts the optimistic update and surfaces the reason on a rejected move', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(() =>
                Promise.resolve(
                    new Response(
                        JSON.stringify({
                            detail: 'That transition is not allowed.',
                        }),
                        {
                            status: 422,
                            headers: {
                                'Content-Type': 'application/problem+json',
                            },
                        },
                    ),
                ),
            ),
        );

        const initialColumns = makeColumns();
        const { result } = renderHook(() =>
            useKanbanBoard('companies', initialColumns),
        );

        await act(async () => {
            result.current.handleDragEnd(dragEvent(1, 'prospect'));
            await Promise.resolve();
            await Promise.resolve();
        });

        expect(result.current.columns[0].cards.map((card) => card.id)).toEqual([
            1,
        ]);
        expect(result.current.columns[1].cards.map((card) => card.id)).toEqual([
            2,
        ]);
        expect(toastError).toHaveBeenCalledWith(
            'That transition is not allowed.',
        );
    });
});
