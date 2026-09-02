import { defineConfig, devices } from '@playwright/test';

/**
 * Runs against the real Compose stack (`docker compose up`), never a
 * bundled dev server — the whole point of this suite is proving the app
 * behind nginx + php-fpm, not the Vite dev build. Start the stack yourself
 * and wait for it to be reachable before running `npm test`.
 */
export default defineConfig({
    testDir: './tests',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['html'], ['github']] : 'list',
    timeout: 30_000,
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8080',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        // The starter kit's own convention (`data-test="..."`), not the
        // Playwright default `data-testid`.
        testIdAttribute: 'data-test',
        // Radix's <Select> popper is rendered with `avoidCollisions={false}`
        // (deliberately, so it never jumps sides) — a short viewport can
        // then push an option below the fold with nothing repositioning it
        // back into view. A tall viewport sidesteps that instead of fighting
        // the component's own choice. Wide enough that every board's
        // columns (six, at their widest — the deals board) sit side by
        // side with no horizontal scroll: a drag that needs a scroll
        // mid-gesture to reach its target column isn't reproducible with a
        // synthetic mouse path the way a real trackpad drag is.
        viewport: { width: 2200, height: 1600 },
    },
    projects: [
        {
            name: 'chromium',
            // `devices['Desktop Chrome']` carries its own 1280×720
            // viewport, which would otherwise win over the wide/tall one
            // set above — re-assert it after the spread.
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 2200, height: 1600 },
            },
        },
    ],
});
