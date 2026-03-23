import { test, expect } from './fixtures';

/**
 * Verifies that items created via "Build from taxon" inherit the
 * navigation's channels, so they are visible on the frontend.
 */
test.describe('Build from taxon inherits channels', () => {
    test('items built from taxon should have the navigation channels', async ({ page, navigation }) => {
        // Build from taxon
        await page.goto(`/admin/navigation/navigations/${navigation.id}/edit/build-from-taxon`);

        // Wait for Sylius JS to initialize the autocomplete
        await page.waitForFunction(() => {
            const el = document.querySelector('.sylius-autocomplete');
            return el && el.classList.contains('search');
        }, { timeout: 10000 });

        // Set the taxon value via the AJAX API and hidden input
        const taxonCode = await page.evaluate(async () => {
            const response = await fetch('/admin/ajax/taxons/search?phrase=');
            const data = await response.json();
            const items = data._embedded?.items || data.results || data;
            return items.length > 0 ? (items[0].code || items[0].id?.toString()) : null;
        });

        if (taxonCode) {
            await page.evaluate((code) => {
                const input = document.querySelector<HTMLInputElement>('input[name="build_from_taxon[taxon]"]');
                if (input) input.value = code;
            }, taxonCode);
        }

        // Check "Include root"
        await page.locator('label[for="build_from_taxon_includeRoot"]').click();

        // Submit
        await page.getByRole('button', { name: 'Build from taxon' }).click();
        await expect(page).toHaveURL(/\/navigations\/\d+\/edit/, { timeout: 10000 });

        // Go to the builder page
        await page.goto(`/admin/navigation/navigations/${navigation.id}/build`);

        // Wait for items to appear
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
