import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { createCompany, createDeal } from './support/entities';
import { uniqueSuffix } from './support/unique';

test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

test('deleting a company with a live deal is refused, naming what blocks it', async ({
    page,
}) => {
    const company = await createCompany(
        page,
        `Blocked Delete Co ${uniqueSuffix()}`,
    );
    await createDeal(page, {
        title: `Blocking deal ${uniqueSuffix()}`,
        companyName: company.name,
    });

    await page.goto(`/companies/${company.id}`);

    await page.getByRole('button', { name: 'Delete' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByRole('button', { name: 'Delete' }).click();

    await expect(
        page.getByText(/Cannot delete this company:.*deal/i),
    ).toBeVisible();

    // The refusal didn't navigate away or soft-delete the record.
    await expect(page).toHaveURL(/\/companies\/\d+$/);
    await page.reload();
    await expect(
        page.getByRole('heading', { name: company.name }),
    ).toBeVisible();
});
