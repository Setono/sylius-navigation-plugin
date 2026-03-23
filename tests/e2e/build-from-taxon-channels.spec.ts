import { test, expect } from '@playwright/test';

/**
 * Verifies that items created via "Build from taxon" inherit the
 * navigation's channels, so they are visible on the frontend.
 */
test.describe('Build from taxon inherits channels', () => {
    const navCode = `e2e_taxon_ch_${Date.now()}`;
    let navigationId: string;

    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();

        // Create a navigation with a channel
        await page.goto('/admin/navigation/navigations/new');
        await page.getByLabel('Code').fill(navCode);
        await page.getByLabel('Description').fill('Channel inheritance test');
        await page.getByLabel('Enabled').check();

        // Select the first available channel checkbox
        const channelCheckbox = page.locator('.ui.checkbox').filter({ has: page.locator('label', { hasText: /store|channel|shop/i }) }).first();
        await channelCheckbox.click();

        await page.getByRole('button', { name: 'Create' }).click();
        await expect(page).toHaveURL(/\/navigations\/\d+\/edit/, { timeout: 10000 });
        navigationId = page.url().match(/\/navigations\/(\d+)\/edit/)![1];

        // Build from taxon
        await page.goto(`/admin/navigation/navigations/${navigationId}/edit/build-from-taxon`);

        // Wait for Sylius JS to initialize the autocomplete
        await page.waitForFunction(() => {
            const el = document.querySelector('.sylius-autocomplete');
            return el && el.classList.contains('search');
        }, { timeout: 10000 });

        // Set the taxon value using the Semantic UI dropdown API
        // First fetch available taxons to get a valid code
        const taxonCode = await page.evaluate(async () => {
            const response = await fetch('/admin/ajax/taxons/search?phrase=');
            const data = await response.json();
            // Sylius returns { results: [{ id, text }] } or similar
            const items = data._embedded?.items || data.results || data;
            return items.length > 0 ? (items[0].code || items[0].id?.toString()) : null;
        });

        if (taxonCode) {
            await page.evaluate((code) => {
                const input = document.querySelector<HTMLInputElement>('input[name="build_from_taxon[taxon]"]');
                if (input) {
                    input.value = code;
                }
            }, taxonCode);
        }

        // Verify the taxon was set
        const taxonInput = page.locator('input[name="build_from_taxon[taxon]"]');
        await expect(taxonInput).not.toHaveValue('');

        // Check "Include root"
        await page.locator('label[for="build_from_taxon_includeRoot"]').click();

        // Submit
        await page.getByRole('button', { name: 'Build from taxon' }).click();
        await expect(page).toHaveURL(/\/navigations\/\d+\/edit/, { timeout: 10000 });

        await page.close();
    });

    test('items built from taxon should have the navigation channels', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        // Wait for items to appear in the tree
        const treeNodes = page.locator('#navigation-tree .jstree-anchor');
        await expect(treeNodes.first()).toBeVisible({ timeout: 15000 });

        // Apply a channel filter
        await page.locator('#channel-filter').click();
        const channelOption = page.locator('#channel-filter .menu .item[data-value]').filter({ hasNot: page.locator('[data-value=""]') }).first();
        await channelOption.click();

        // Wait for tree to refresh and verify items are still visible
        await page.waitForTimeout(2000);
        await expect(treeNodes.first()).toBeVisible({ timeout: 10000 });

        // No items should be marked as channel-hidden
        const hiddenItems = page.locator('#navigation-tree .jstree-node.item-channel-hidden');
        expect(await hiddenItems.count()).toBe(0);
    });
});
