import type { Page } from '@playwright/test';

/**
 * The app's `<Select>` is Radix's, rendered with `avoidCollisions={false}`
 * — its own deliberate choice, not a bug (see `resource-form.tsx` /
 * `resources/js/components/ui/select.tsx`). Clicking a specific option by
 * coordinates fights that: scrolling the option into view can trigger the
 * popper to reposition mid-click, and the two chase each other
 * indefinitely. Radix's own type-ahead search sidesteps all of it — no
 * scrolling, no coordinates, just the keyboard.
 */
export async function chooseOption(
    page: Page,
    label: string,
    optionPrefix: string,
): Promise<void> {
    await page.getByLabel(label).click();
    await page.keyboard.type(optionPrefix, { delay: 15 });
    await page.keyboard.press('Enter');
}
