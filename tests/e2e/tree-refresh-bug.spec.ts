import { test, expect } from './fixtures';

/**
 * Regression test for tree refresh after adding a child item.
 *
 * When a parent node is already expanded (open) and a child is added via the
 * context menu, the new child must appear in the tree without a manual page
 * reload.
 */
test.describe('Tree refresh after adding child item', () => {
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

    test('second child should appear when parent is already expanded', async ({ page, navigation }) => {
        await page.goto(`/admin/navigation/navigations/${navigation.id}/build`);

        await page.waitForFunction(() => {
            const tree = document.querySelector('#navigation-tree');
            return tree && tree.classList.contains('jstree');
        }, { timeout: 15000 });

        // Add a root item
        await addItem(page, 'Parent');
        await expect(page.locator('#navigation-tree')).toContainText('Parent', { timeout: 10000 });

        // Add the FIRST child
        const parentAnchor = '#navigation-tree a.jstree-anchor:has-text("Parent")';
        await addItem(page, 'First Child', parentAnchor);
        await expect(page.locator('#navigation-tree')).toContainText('First Child', { timeout: 10000 });

        // Parent is now OPEN. Add a SECOND child — fails if refresh doesn't work on open nodes.
        await addItem(page, 'Second Child', parentAnchor);
        await expect(page.locator('#navigation-tree')).toContainText('Second Child', { timeout: 5000 });
    });
});
