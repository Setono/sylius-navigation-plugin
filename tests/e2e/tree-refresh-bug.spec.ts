import { test, expect } from '@playwright/test';

/**
 * Regression test for tree refresh after adding a child item to an
 * already-expanded parent that already has children.
 *
 * When a parent node is already open and showing children, adding another
 * child via the context menu must show the new child in the tree without
 * a manual page reload.
 */
test.describe('Tree refresh after adding child item', () => {
    const navCode = `e2e_refresh_${Date.now()}`;
    let navigationId: string;

    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();

        // Create a fresh navigation
        await page.goto('/admin/navigation/navigations/new');
        await page.getByLabel('Code').fill(navCode);
        await page.getByLabel('Description').fill('Refresh test');
        await page.getByLabel('Enabled').check();
        await page.getByRole('button', { name: 'Create' }).click();
        await expect(page).toHaveURL(/\/navigations\/\d+\/edit/, { timeout: 10000 });
        navigationId = page.url().match(/\/navigations\/(\d+)\/edit/)![1];

        await page.close();
    });

    /**
     * Helper: adds an item via context menu "Add child" on the given parent node.
     * If parentNode is null, uses the main "Add item" dropdown for a root item.
     */
    async function addItem(page: import('@playwright/test').Page, label: string, parentSelector?: string) {
        if (parentSelector) {
            const parentNode = page.locator(parentSelector);
            await parentNode.click({ button: 'right' });

            const contextMenu = page.locator('.vakata-context');
            await expect(contextMenu).toBeVisible();

            const addChild = contextMenu.locator('a', { hasText: 'Add child' });
            await addChild.hover();

            const subMenu = addChild.locator('..').locator('ul a').first();
            await expect(subMenu).toBeVisible({ timeout: 3000 });
            await subMenu.click();
        } else {
            await page.locator('#main-add-dropdown').click();
            await page.locator('#main-add-dropdown .menu .item').first().click();
        }

        const modal = page.locator('#add-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });
        await modal.locator('input[id$="_translations_en_US_label"]').fill(label);
        await modal.getByRole('button', { name: 'Create' }).click();
        await expect(modal).toBeHidden({ timeout: 5000 });
    }

    test('second child should appear when parent is already expanded', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        // Wait for jsTree to initialize
        await page.waitForFunction(() => {
            const tree = document.querySelector('#navigation-tree');
            return tree && tree.classList.contains('jstree');
        }, { timeout: 15000 });

        // Step 1: Add a root item
        await addItem(page, 'Parent');
        await expect(page.locator('#navigation-tree')).toContainText('Parent', { timeout: 10000 });

        // Step 2: Add the FIRST child — this works because the parent is a leaf (closed)
        const parentAnchor = '#navigation-tree a.jstree-anchor:has-text("Parent")';
        await addItem(page, 'First Child', parentAnchor);
        await expect(page.locator('#navigation-tree')).toContainText('First Child', { timeout: 10000 });

        // Step 3: The parent is now OPEN and showing "First Child".
        // Add a SECOND child — this is the scenario that fails if refresh doesn't work
        // on already-open nodes.
        await addItem(page, 'Second Child', parentAnchor);

        // THE KEY ASSERTION: "Second Child" must appear without a page reload.
        await expect(page.locator('#navigation-tree')).toContainText('Second Child', { timeout: 5000 });
    });
});
