import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { chooseOption } from './support/select';
import { uniqueSuffix } from './support/unique';

test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

test('creating a company, a contact, a deal and a quote through the UI', async ({
    page,
}) => {
    const suffix = uniqueSuffix();
    const companyName = `Journey Co ${suffix}`;
    const contactFirstName = 'Iva';
    const contactLastName = `Novak ${suffix}`;
    const dealTitle = `Journey deal ${suffix}`;

    // Company
    await page.goto('/companies/create');
    await page.getByLabel('Name').fill(companyName);
    await page.getByRole('button', { name: 'Create company' }).click();

    await expect(page).toHaveURL(/\/companies\/\d+$/);
    await expect(
        page.getByRole('heading', { name: companyName }),
    ).toBeVisible();

    // Contact, linked to that company
    await page.goto('/contacts/create');
    await page.getByLabel('First name').fill(contactFirstName);
    await page.getByLabel('Last name').fill(contactLastName);
    await chooseOption(page, 'Company', companyName);
    await page.getByRole('button', { name: 'Create contact' }).click();

    await expect(page).toHaveURL(/\/contacts\/\d+$/);
    const contactName = `${contactFirstName} ${contactLastName}`;
    await expect(
        page.getByRole('heading', { name: contactName }),
    ).toBeVisible();

    // Deal, linked to the same company and contact
    await page.goto('/deals/create');
    await page.getByLabel('Title').fill(dealTitle);
    await chooseOption(page, 'Company', companyName);
    await chooseOption(page, 'Primary contact', contactName);
    await page.getByRole('button', { name: 'Create deal' }).click();

    await expect(page).toHaveURL(/\/deals\/\d+$/);
    await expect(page.getByRole('heading', { name: dealTitle })).toBeVisible();
    await expect(page.getByText(companyName)).toBeVisible();

    // Standalone quote, linked to that deal
    await page.goto('/quotes/create');
    await chooseOption(page, 'Deal', `${dealTitle} — ${companyName}`);
    await page.getByRole('button', { name: 'Create quote' }).click();

    await expect(page).toHaveURL(/\/quotes\/\d+$/);
    await expect(page.getByText(dealTitle)).toBeVisible();
    await expect(page.getByText('Draft')).toBeVisible();
});
