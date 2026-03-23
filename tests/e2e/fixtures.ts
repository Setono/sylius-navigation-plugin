import { test as base, expect } from '@playwright/test';

type Navigation = {
    id: string;
    code: string;
};

type Fixtures = {
    navigation: Navigation;
};

export const test = base.extend<Fixtures>({
    navigation: async ({ page }, use) => {
        const code = `e2e_nav_${Date.now()}`;

        // Create
        await page.goto('/admin/navigation/navigations/new');
        await page.getByLabel('Code').fill(code);
        await page.getByLabel('Description').fill('E2E test navigation');
        await page.getByLabel('Enabled').check();

        // Select the first available channel
        const channelCheckbox = page.locator('.ui.checkbox').filter({ has: page.locator('label', { hasText: /store|channel|shop/i }) }).first();
        if (await channelCheckbox.count() > 0) {
            await channelCheckbox.click();
        }

        await page.getByRole('button', { name: 'Create' }).click();
        await expect(page).toHaveURL(/\/navigations\/\d+\/edit/, { timeout: 10000 });
        const id = page.url().match(/\/navigations\/(\d+)\/edit/)![1];

        await use({ id, code });

        // Teardown: no cleanup needed — test navigations accumulate harmlessly
    },
});

export { expect } from '@playwright/test';
