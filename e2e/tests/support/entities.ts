import { expect, type Page } from '@playwright/test';
import { chooseOption } from './select';

function idFromUrl(page: Page): number {
    const match = /\/(\d+)(?:\/edit)?$/.exec(new URL(page.url()).pathname);

    if (!match) {
        throw new Error(`Could not extract an id from ${page.url()}`);
    }

    return Number(match[1]);
}

export async function createCompany(
    page: Page,
    name: string,
): Promise<{ id: number; name: string }> {
    await page.goto('/companies/create');
    await page.getByLabel('Name').fill(name);
    await page.getByRole('button', { name: 'Create company' }).click();
    await expect(page).toHaveURL(/\/companies\/\d+$/);

    return { id: idFromUrl(page), name };
}

export async function createContact(
    page: Page,
    options: { firstName: string; lastName: string; companyName: string },
): Promise<{ id: number; name: string }> {
    await page.goto('/contacts/create');
    await page.getByLabel('First name').fill(options.firstName);
    await page.getByLabel('Last name').fill(options.lastName);
    await chooseOption(page, 'Company', options.companyName);
    await page.getByRole('button', { name: 'Create contact' }).click();
    await expect(page).toHaveURL(/\/contacts\/\d+$/);

    return {
        id: idFromUrl(page),
        name: `${options.firstName} ${options.lastName}`,
    };
}

export async function createDeal(
    page: Page,
    options: { title: string; companyName: string; contactName?: string },
): Promise<{ id: number; title: string }> {
    await page.goto('/deals/create');
    await page.getByLabel('Title').fill(options.title);
    await chooseOption(page, 'Company', options.companyName);

    if (options.contactName) {
        await chooseOption(page, 'Primary contact', options.contactName);
    }

    await page.getByRole('button', { name: 'Create deal' }).click();
    await expect(page).toHaveURL(/\/deals\/\d+$/);

    return { id: idFromUrl(page), title: options.title };
}

export async function createStandaloneQuote(
    page: Page,
    options: { dealTitle: string; companyName: string },
): Promise<{ id: number }> {
    await page.goto('/quotes/create');
    await chooseOption(
        page,
        'Deal',
        `${options.dealTitle} — ${options.companyName}`,
    );
    await page.getByRole('button', { name: 'Create quote' }).click();
    await expect(page).toHaveURL(/\/quotes\/\d+$/);

    return { id: idFromUrl(page) };
}
