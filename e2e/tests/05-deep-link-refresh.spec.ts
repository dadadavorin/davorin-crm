import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './support/auth';
import { createCompany, createDeal } from './support/entities';
import { uniqueSuffix } from './support/unique';

test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
});

/**
 * Load-bearing (`docs/TASKS.md` T12): the only test that can catch a
 * missing nginx `index.html` fallback. Client-side routing alone can
 * never reproduce this — every other spec navigates via `<Link>`, which
 * never issues a fresh request for the deep-linked path. A real hard
 * refresh does, and nginx has to resolve it to the app, not a 404.
 */
test('a hard refresh on a deep link renders the app, not a 404', async ({
    page,
}) => {
    const company = await createCompany(page, `Deep Link Co ${uniqueSuffix()}`);
    const deal = await createDeal(page, {
        title: `Deep link deal ${uniqueSuffix()}`,
        companyName: company.name,
    });

    const dealUrl = page.url();
    expect(dealUrl).toMatch(/\/deals\/\d+$/);

    // Simulate arriving fresh, as if the URL were pasted or bookmarked.
    await page.goto(dealUrl);
    await expect(page.getByRole('heading', { name: deal.title })).toBeVisible();

    // Then the actual hard refresh.
    const response = await page.reload();

    expect(response?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: deal.title })).toBeVisible();
    await expect(page.getByText(company.name)).toBeVisible();
});
