import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import {
    cardInColumn,
    dragCardToColumn,
    dragCardToColumnAndWaitForMove,
    expectCardInColumn,
    firstCardInColumn,
} from './support/board';
import { createCompany, createContact, createDeal } from './support/entities';
import { chooseOption } from './support/select';
import { uniqueSuffix } from './support/unique';

test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

test('dragging a company card between columns persists across a refresh', async ({
    page,
}) => {
    const company = await createCompany(page, `Board Co ${uniqueSuffix()}`);

    await page.goto('/companies/board');
    const card = cardInColumn(page, 'Lead', company.name);
    await expect(card).toBeVisible();

    await dragCardToColumnAndWaitForMove(page, card, 'Prospect');
    await expectCardInColumn(page, 'Prospect', company.name);

    await page.reload();
    await expectCardInColumn(page, 'Prospect', company.name);
});

test('dragging a contact card between columns persists across a refresh', async ({
    page,
}) => {
    const company = await createCompany(
        page,
        `Contact Board Co ${uniqueSuffix()}`,
    );
    const contact = await createContact(page, {
        firstName: 'Board',
        lastName: `Test ${uniqueSuffix()}`,
        companyName: company.name,
    });

    await page.goto('/contacts/board');
    const card = cardInColumn(page, 'New', contact.name);
    await expect(card).toBeVisible();

    await dragCardToColumnAndWaitForMove(page, card, 'Active');
    await expectCardInColumn(page, 'Active', contact.name);

    await page.reload();
    await expectCardInColumn(page, 'Active', contact.name);
});

test('dragging a deal card between columns persists across a refresh', async ({
    page,
}) => {
    const company = await createCompany(
        page,
        `Deal Board Co ${uniqueSuffix()}`,
    );
    const deal = await createDeal(page, {
        title: `Board deal ${uniqueSuffix()}`,
        companyName: company.name,
    });

    await page.goto('/deals/board');
    const card = cardInColumn(page, 'New', deal.title);
    await expect(card).toBeVisible();

    await dragCardToColumnAndWaitForMove(page, card, 'Qualified');
    await expectCardInColumn(page, 'Qualified', deal.title);

    await page.reload();
    await expectCardInColumn(page, 'Qualified', deal.title);
});

test('dragging a quote card between columns persists across a refresh', async ({
    page,
}) => {
    const company = await createCompany(
        page,
        `Quote Board Co ${uniqueSuffix()}`,
    );
    const deal = await createDeal(page, {
        title: `Quote board deal ${uniqueSuffix()}`,
        companyName: company.name,
    });

    await page.goto('/quotes/create');
    await chooseOption(page, 'Deal', `${deal.title} — ${company.name}`);
    await page.getByRole('button', { name: 'Create quote' }).click();
    await expect(page).toHaveURL(/\/quotes\/\d+$/);
    const quoteNumber = (
        await page.getByRole('heading', { level: 2 }).textContent()
    )?.trim();

    if (!quoteNumber) {
        throw new Error('Could not read the new quote number.');
    }

    await page.goto('/quotes/board');
    const card = cardInColumn(page, 'Draft', quoteNumber);
    await expect(card).toBeVisible();

    await dragCardToColumnAndWaitForMove(page, card, 'Sent');
    await expectCardInColumn(page, 'Sent', quoteNumber);

    await page.reload();
    await expectCardInColumn(page, 'Sent', quoteNumber);
});

test('dragging a deal out of Won reverts it and shows the rejection reason', async ({
    page,
}) => {
    await page.goto('/deals/board');

    // The seeded demo data guarantees every board column, Won included, is
    // never empty — whichever card is there, the drag must be refused.
    const card = firstCardInColumn(page, 'Won');
    await expect(card).toBeVisible();
    const cardText = (await card.textContent())?.trim() ?? '';

    await dragCardToColumn(page, card, 'Qualified');

    await expect(page.getByText(/rejected|not allowed/i)).toBeVisible();
    await expectCardInColumn(page, 'Won', cardText);
});
