import { expect, test } from '@playwright/test';
import { ADMIN_EMAIL, ADMIN_PASSWORD, loginAsAdmin } from './support/auth';

test.describe('authentication', () => {
    test('logging in lands on the dashboard', async ({ page }) => {
        await loginAsAdmin(page);

        await expect(page).toHaveURL(/\/dashboard$/);
        const main = page.getByRole('main');
        await expect(main.getByText('Companies')).toBeVisible();
        await expect(main.getByText('Quotes')).toBeVisible();
    });

    test('an unauthenticated deep link redirects to login', async ({
        page,
    }) => {
        await page.goto('/deals');

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.getByLabel('Email address')).toBeVisible();
    });

    test('the wrong password is rejected', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email address').fill(ADMIN_EMAIL);
        await page
            .getByLabel('Password', { exact: true })
            .fill(`not-${ADMIN_PASSWORD}`);
        await page.getByTestId('login-button').click();

        await expect(page).toHaveURL(/\/login$/);
    });
});
