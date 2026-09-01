import { move } from '@/routes/api/boards';

export type MoveCardPayload = {
    entity: string;
    id: number;
    status: string;
    beforeId: number | null;
    afterId: number | null;
};

/**
 * The board move endpoint is a plain JSON route, not Inertia (ADR-0006): a
 * rejection needs to come back as a real 422 body this call can inspect and
 * revert on, not a 302 with flashed session errors. Hit with `fetch`, never
 * `router.post`.
 */
export async function moveBoardCard({
    entity,
    id,
    status,
    beforeId,
    afterId,
}: MoveCardPayload): Promise<void> {
    const response = await fetch(move.url({ entity, id }), {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            status,
            before_id: beforeId,
            after_id: afterId,
        }),
    });

    if (response.ok) {
        return;
    }

    let detail = 'This move was rejected.';

    try {
        const body: unknown = await response.json();

        if (
            body !== null &&
            typeof body === 'object' &&
            'detail' in body &&
            typeof body.detail === 'string'
        ) {
            detail = body.detail;
        }
    } catch {
        // No JSON body to read — fall back to the generic message.
    }

    throw new BoardMoveError(response.status, detail);
}

export class BoardMoveError extends Error {
    constructor(
        public readonly status: number,
        message: string,
    ) {
        super(message);
        this.name = 'BoardMoveError';
    }
}
