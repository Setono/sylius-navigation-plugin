import { test, expect } from '@playwright/test';

test.describe('Navigation Builder', () => {
    let navigationId: string;

    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();

        // Create a navigation to use in tests
        await page.goto('/admin/navigation/navigations/new');
        await page.locator('#setono_sylius_navigation_navigation_code').fill('e2e_test_nav');
        await page.locator('#setono_sylius_navigation_navigation_description').fill('E2E Test Navigation');
        await page.locator('#setono_sylius_navigation_navigation_enabled').check();
        await page.getByRole('button', { name: 'Create' }).click();

        // After creation, Sylius redirects to the update page: /admin/navigation/navigations/{id}/edit
        await expect(page).toHaveURL(/\/navigations\/\d+\/edit/);
        const url = page.url();
        navigationId = url.match(/\/navigations\/(\d+)\/edit/)![1];

        await page.close();
    });

    test.afterAll(async ({ browser }) => {
        const page = await browser.newPage();
        await page.goto(`/admin/navigation/navigations/${navigationId}/edit`);
        // Delete the navigation via the grid page
        await page.goto('/admin/navigation/navigations/');
        const row = page.locator('tr', { has: page.locator('td', { hasText: 'e2e_test_nav' }) });
        if (await row.count() > 0) {
            await row.locator('button.dropdown').click();
            await row.getByText('Delete').click();
            await page.locator('.modal .ok.button, .modal button:has-text("Yes")').click();
            await page.waitForURL(/\/navigations\//);
        }
        await page.close();
    });

    test('should display the navigation builder page', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        await expect(page.locator('#navigation-tree')).toBeVisible();
        await expect(page.locator('#main-add-dropdown')).toBeVisible();
        await expect(page.locator('#tree-search')).toBeVisible();
    });

    test('should show empty state when navigation has no items', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        await expect(page.locator('#empty-tree')).toBeVisible({ timeout: 10000 });
    });

    test('should add a root item and show it in the tree', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        // Wait for tree to initialize
        await expect(page.locator('#empty-tree')).toBeVisible({ timeout: 10000 });

        // Click the add item dropdown and select item type
        await page.locator('#empty-add-dropdown').click();
        await page.locator('#empty-add-dropdown .menu .item').first().click();

        // Wait for modal to appear
        const modal = page.locator('#add-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });

        // Fill in the label field
        await modal.locator('input[id$="_translations_en_US_label"]').fill('Home');

        // Submit
        await modal.getByRole('button', { name: 'Create' }).click();

        // Verify the item appears in the tree
        await expect(page.locator('#navigation-tree')).toContainText('Home', { timeout: 10000 });

        // Empty state should be hidden
        await expect(page.locator('#empty-tree')).toBeHidden();
    });

    test('should add a child item via context menu and refresh the tree', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        // Wait for the tree to load with the existing item
        const homeNode = page.locator('#navigation-tree a', { hasText: 'Home' });
        await expect(homeNode).toBeVisible({ timeout: 10000 });

        // Right-click the item to open context menu
        await homeNode.click({ button: 'right' });

        // Click "Add child" in context menu, then select the first item type
        const contextMenu = page.locator('.vakata-context');
        await expect(contextMenu).toBeVisible();
        await contextMenu.locator('a', { hasText: 'Add child' }).hover();

        // Select the first sub-menu item type
        const subMenu = contextMenu.locator('.vakata-context');
        await subMenu.locator('a').first().click();

        // Wait for modal
        const modal = page.locator('#add-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });

        // Fill in the label
        await modal.locator('input[id$="_translations_en_US_label"]').fill('About');

        // Submit
        await modal.getByRole('button', { name: 'Create' }).click();

        // Wait for modal to close
        await expect(modal).toBeHidden({ timeout: 5000 });

        // The child item should appear in the tree
        await expect(page.locator('#navigation-tree')).toContainText('About', { timeout: 10000 });
    });

    test('should edit an existing item', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        // Wait for tree to load
        const homeNode = page.locator('#navigation-tree a', { hasText: 'Home' });
        await expect(homeNode).toBeVisible({ timeout: 10000 });

        // Right-click to edit
        await homeNode.click({ button: 'right' });
        const contextMenu = page.locator('.vakata-context');
        await contextMenu.locator('a', { hasText: 'Edit' }).click();

        // Wait for edit modal
        const modal = page.locator('#edit-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });

        // Update the label
        const labelInput = modal.locator('input[id$="_translations_en_US_label"]');
        await labelInput.clear();
        await labelInput.fill('Homepage');

        // Save
        await modal.getByRole('button', { name: 'Save' }).click();

        // Wait for modal to close and verify updated label
        await expect(modal).toBeHidden({ timeout: 5000 });
        await expect(page.locator('#navigation-tree')).toContainText('Homepage', { timeout: 10000 });
    });

    test('should delete an item', async ({ page }) => {
        await page.goto(`/admin/navigation/navigations/${navigationId}/build`);

        // Wait for tree to load
        const aboutNode = page.locator('#navigation-tree a', { hasText: 'About' });
        await expect(aboutNode).toBeVisible({ timeout: 10000 });

        // Right-click to delete
        await aboutNode.click({ button: 'right' });
        const contextMenu = page.locator('.vakata-context');
        await contextMenu.locator('a', { hasText: 'Delete' }).click();

        // Confirm deletion in modal
        const modal = page.locator('#delete-item-modal');
        await expect(modal).toBeVisible({ timeout: 5000 });
        await modal.locator('.ok.button').click();

        // Verify the item is removed
        await expect(aboutNode).toBeHidden({ timeout: 10000 });
    });
});
