import { test, expect } from './fixtures';

test.describe('Navigation Builder', () => {
    test('should display the navigation builder page', async ({ page, navigation }) => {
        await page.goto(`/admin/navigation/navigations/${navigation.id}/build`);

        await expect(page.locator('#navigation-tree')).toBeVisible();
        await expect(page.locator('#main-add-dropdown')).toBeVisible();
        await expect(page.locator('#tree-search')).toBeVisible();
    });

    test('should add a root item and show it in the tree', async ({ page, navigation }) => {
        await page.goto(`/admin/navigation/navigations/${navigation.id}/build`);

        await page.waitForFunction(() => {
            const tree = document.querySelector('#navigation-tree');
            return tree && tree.classList.contains('jstree');
        }, { timeout: 15000 });

        await page.locator('#main-add-dropdown').click();
        await page.locator('#main-add-dropdown .menu .item').first().click();

        const modal = page.locator('#add-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });
        await modal.locator('input[id$="_translations_en_US_label"]').fill('Home');
        await modal.getByRole('button', { name: 'Create' }).click();

        await expect(page.locator('#navigation-tree')).toContainText('Home', { timeout: 10000 });
    });

    test('should edit an existing item', async ({ page, navigation }) => {
        await page.goto(`/admin/navigation/navigations/${navigation.id}/build`);

        // Add an item first
        await page.waitForFunction(() => {
            const tree = document.querySelector('#navigation-tree');
            return tree && tree.classList.contains('jstree');
        }, { timeout: 15000 });
        await page.locator('#main-add-dropdown').click();
        await page.locator('#main-add-dropdown .menu .item').first().click();
        const addModal = page.locator('#add-item-modal');
        await expect(addModal).toBeVisible({ timeout: 5000 });
        await addModal.locator('input[id$="_translations_en_US_label"]').fill('Home');
        await addModal.getByRole('button', { name: 'Create' }).click();
        await expect(page.locator('#navigation-tree')).toContainText('Home', { timeout: 10000 });

        // Right-click to edit
        const homeNode = page.locator('#navigation-tree a', { hasText: 'Home' });
        await homeNode.click({ button: 'right' });
        const contextMenu = page.locator('.vakata-context');
        await contextMenu.locator('a', { hasText: 'Edit' }).click();

        const modal = page.locator('#edit-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });
        const labelInput = modal.locator('input[id$="_translations_en_US_label"]');
        await labelInput.clear();
        await labelInput.fill('Homepage');
        await modal.getByRole('button', { name: 'Save' }).click();

        await expect(modal).toBeHidden({ timeout: 5000 });
        await expect(page.locator('#navigation-tree')).toContainText('Homepage', { timeout: 10000 });
    });

    test('should delete an item', async ({ page, navigation }) => {
        await page.goto(`/admin/navigation/navigations/${navigation.id}/build`);

        // Add an item first
        await page.waitForFunction(() => {
            const tree = document.querySelector('#navigation-tree');
            return tree && tree.classList.contains('jstree');
        }, { timeout: 15000 });
        await page.locator('#main-add-dropdown').click();
        await page.locator('#main-add-dropdown .menu .item').first().click();
        const addModal = page.locator('#add-item-modal');
        await expect(addModal).toBeVisible({ timeout: 5000 });
        await addModal.locator('input[id$="_translations_en_US_label"]').fill('ToDelete');
        await addModal.getByRole('button', { name: 'Create' }).click();
        await expect(page.locator('#navigation-tree')).toContainText('ToDelete', { timeout: 10000 });

        // Right-click to delete
        const node = page.locator('#navigation-tree a', { hasText: 'ToDelete' });
        await node.click({ button: 'right' });
        const contextMenu = page.locator('.vakata-context');
        await contextMenu.locator('a', { hasText: 'Delete' }).click();

        const modal = page.locator('#delete-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });
        await modal.locator('.ok.button').click();

        await expect(node).toBeHidden({ timeout: 10000 });
    });
});
