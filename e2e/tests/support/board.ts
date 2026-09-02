import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Each column is `<h3>{label}</h3>` nested two levels under the column's
 * own wrapper (`w-72 shrink-0 flex-col`) — walking up from the heading to
 * that wrapper is the only reliable way to scope a column, since a plain
 * `:has(heading)` filter also matches the board's outer container (it too
 * "has" every column's heading as a descendant).
 */
function column(page: Page, label: string): Locator {
    return page
        .getByRole('heading', { name: label, exact: true })
        .locator(
            'xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " w-72 ")]',
        )
        .first();
}

function dropZone(page: Page, label: string): Locator {
    return column(page, label).locator('div.min-h-24');
}

/** Every draggable card container within one column. */
function cardsInColumn(page: Page, columnLabel: string): Locator {
    return dropZone(page, columnLabel).locator('div.touch-none');
}

/** The single card in a column whose rendered text contains `cardText`. */
export function cardInColumn(
    page: Page,
    columnLabel: string,
    cardText: string,
): Locator {
    return cardsInColumn(page, columnLabel).filter({ hasText: cardText });
}

/** The first card in a column, whatever it is — for "any card here" cases. */
export function firstCardInColumn(page: Page, columnLabel: string): Locator {
    return cardsInColumn(page, columnLabel).first();
}

/**
 * The board uses dnd-kit's `PointerSensor`, not native HTML5 drag-and-drop
 * — it listens for real pointer events with a 4px activation distance, so
 * Playwright's `locator.dragTo()` (which fires `dragstart`/`drop`) never
 * triggers it. A manual, stepped mouse sequence is the only thing that
 * reliably crosses the activation threshold and lands on the right column.
 */
export async function dragCardToColumn(
    page: Page,
    card: Locator,
    columnLabel: string,
): Promise<void> {
    const source = await card.boundingBox();
    const target = await dropZone(page, columnLabel).boundingBox();

    if (!source || !target) {
        throw new Error('Could not measure card or column for drag.');
    }

    const startX = source.x + source.width / 2;
    const startY = source.y + source.height / 2;
    const endX = target.x + target.width / 2;
    const endY = target.y + Math.min(target.height / 2, 20);

    await page.mouse.move(startX, startY);
    await page.mouse.down();
    // Small first step to cross the activation distance before the big jump.
    await page.mouse.move(startX + 10, startY + 10, { steps: 5 });
    await page.mouse.move(endX, endY, { steps: 15 });
    await page.mouse.move(endX, endY, { steps: 2 });
    await page.mouse.up();
}

/**
 * The move itself is a `fetch` outside Inertia's own lifecycle (ADR-0006),
 * so nothing about page navigation waits for it — a refresh right after
 * `dragCardToColumn` can race the request that's supposed to persist the
 * move. Waiting for its response first is what makes "refresh and assert
 * it stuck" a real assertion instead of a coin flip.
 */
export async function dragCardToColumnAndWaitForMove(
    page: Page,
    card: Locator,
    columnLabel: string,
): Promise<void> {
    const movePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/api/v1/boards/') &&
            response.request().method() === 'POST',
    );

    await dragCardToColumn(page, card, columnLabel);
    await movePromise;
}

export async function expectCardInColumn(
    page: Page,
    columnLabel: string,
    cardText: string,
): Promise<void> {
    await expect(cardInColumn(page, columnLabel, cardText)).toBeVisible();
}
