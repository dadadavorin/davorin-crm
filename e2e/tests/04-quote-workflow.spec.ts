import { expect, type Page, test } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { cardInColumn } from './support/board';
import { createCompany, createDeal } from './support/entities';
import { chooseOption } from './support/select';
import { uniqueSuffix } from './support/unique';

test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

async function addLineItem(
    page: Page,
    description: string,
    quantity: string,
    unitPrice: string,
): Promise<void> {
    await page.getByRole('button', { name: 'Add line item' }).click();

    const rows = page.getByLabel('Description');
    const index = (await rows.count()) - 1;

    await page.getByLabel('Description').nth(index).fill(description);
    await page.getByLabel('Quantity').nth(index).fill(quantity);
    await page.getByLabel('Unit price').nth(index).fill(unitPrice);
}

test('creating a quote from a deal, adding line items and sending updates the totals', async ({
    page,
}) => {
    const company = await createCompany(
        page,
        `Quote Flow Co ${uniqueSuffix()}`,
    );
    const deal = await createDeal(page, {
        title: `Quote flow deal ${uniqueSuffix()}`,
        companyName: company.name,
    });

    await page.goto('/deals/board');
    const dealCard = cardInColumn(page, 'New', deal.title);
    await dealCard.getByRole('button', { name: 'Create quote' }).click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: 'Create quote' }).click();

    await expect(page).toHaveURL(/\/quotes\/\d+\/edit$/);

    await addLineItem(page, 'Consulting hours', '2', '10.00');
    await addLineItem(page, 'Setup fee', '1', '5.00');

    await page.getByRole('button', { name: 'Save changes' }).click();

    await expect(page).toHaveURL(/\/quotes\/\d+$/);
    await expect(page.getByText('Consulting hours')).toBeVisible();
    await expect(page.getByText('Setup fee')).toBeVisible();

    // subtotal 20.00 + 5.00 = 25.00, tax at the 25% default = 6.25, total 31.25
    await expect(page.getByText('€25.00', { exact: true })).toBeVisible();
    await expect(page.getByText('€6.25', { exact: true })).toBeVisible();
    await expect(page.getByText('€31.25', { exact: true })).toBeVisible();

    const quoteUrl = page.url();
    await page.goto(`${quoteUrl}/edit`);
    await chooseOption(page, 'Status', 'Sent');
    await page.getByRole('button', { name: 'Save changes' }).click();

    await expect(page).toHaveURL(quoteUrl);
    await expect(page.getByText('Sent')).toBeVisible();
});

test('downloading the quote PDF returns the right file', async ({ page }) => {
    const company = await createCompany(page, `PDF Co ${uniqueSuffix()}`);
    const deal = await createDeal(page, {
        title: `PDF deal ${uniqueSuffix()}`,
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

    const quoteId = /\/quotes\/(\d+)$/.exec(page.url())?.[1];
    const response = await page.context().request.get(`/quotes/${quoteId}/pdf`);

    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/pdf');
    expect(response.headers()['content-disposition']).toContain(
        `quote-${quoteNumber}.pdf`,
    );

    const body = await response.body();
    expect(body.subarray(0, 4).toString('latin1')).toBe('%PDF');
});
