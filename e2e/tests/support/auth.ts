import { expect, type Page } from '@playwright/test';

export const ADMIN_EMAIL =
    process.env.E2E_ADMIN_EMAIL ?? 'admin@davorincrm.test';
export const ADMIN_PASSWORD = process.env.E2E_ADMIN_PASSWORD ?? 'password';

export async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Email address').fill(ADMIN_EMAIL);
    await page.getByLabel('Password', { exact: true }).fill(ADMIN_PASSWORD);
    await page.getByTestId('login-button').click();
    await expect(page).toHaveURL(/\/dashboard$/);
}
