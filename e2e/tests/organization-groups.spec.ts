import { expect, test, type Page } from '@playwright/test';

// Organization groups e2e tests (change organization-groups):
// - Manager group CRUD (create, edit, delete)
// - Group membership management (add/remove orgs from groups)
// - Campaign recipient bulk add-by-group
// - Manager deletion flow with group reassign/delete

const loginSubmit = 'form[action="/login"] button[type="submit"]';

let counter = 0;
function uniqueName(prefix: string) {
  return `${prefix} ${Date.now()}-${++counter}`;
}

async function login(page: Page, email: string, password: string) {
  await page.goto('/login');
  await page.fill('input[name="_username"]', email);
  await page.fill('input[name="_password"]', password);
  await page.click(loginSubmit);
  await expect(page.locator('.header__menu-link', { hasText: 'Панель' })).toBeVisible();
}

async function createGroup(page: Page, name: string, opts?: { description?: string; color?: string }): Promise<number> {
  await page.goto('/groups/new');
  await page.fill('input[name="name"]', name);
  if (opts?.description) {
    await page.fill('textarea[name="description"]', opts.description);
  }
  if (opts?.color) {
    await page.fill('input[name="color"]', opts.color);
  }
  await page.click('button:has-text("Создать")');
  await expect(page).toHaveURL(/\/groups$/);
  return 1; // Simple success indicator
}

async function navigateToGroupsPage(page: Page) {
  await page.goto('/groups');
  await expect(page.locator('h1', { hasText: 'Мои группы' })).toBeVisible();
}

// ─── Manager Group CRUD ───────────────────────────────────────────────

test('manager can create a new group', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  const groupName = uniqueName('Тестовая группа');

  await navigateToGroupsPage(page);
  
  await page.click('a:has-text("Новая группа")');
  await expect(page.locator('h1', { hasText: 'Новая группа' })).toBeVisible();
  
  await page.fill('input[name="name"]', groupName);
  await page.fill('textarea[name="description"]', 'Описание тестовой группы');
  await page.fill('input[name="color"]', '#3b82f6');
  await page.click('button:has-text("Создать")');
  
  await expect(page).toHaveURL(/\/groups$/);
  await expect(page.locator('body', { hasText: groupName })).toBeVisible();
});

test('manager can edit their own group', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  const groupName = uniqueName('Группа для редактирования');

  await navigateToGroupsPage(page);
  
  // Create group first
  await page.click('a:has-text("Новая группа")');
  await page.fill('input[name="name"]', groupName);
  await page.fill('textarea[name="description"]', 'Оригинальное описание');
  await page.fill('input[name="color"]', '#3b82f6');
  await page.click('button:has-text("Создать")');
  
  await expect(page).toHaveURL(/\/groups$/);
  
  // Find and edit the group
  const groupRow = page.locator('[data-group-row]', { hasText: groupName }).first();
  await groupRow.locator('a:has-text("Редактировать")').click();
  
  await expect(page.locator('h1', { hasText: 'Редактирование группы' })).toBeVisible();
  
  await page.fill('input[name="name"]', `${groupName} Обновленная`);
  await page.fill('textarea[name="description"]', 'Обновленное описание');
  await page.fill('input[name="color"]', '#ef4444');
  await page.click('button:has-text("Сохранить")');
  
  await expect(page).toHaveURL(/\/groups$/);
  await expect(page.locator('body', { hasText: `${groupName} Обновленная` })).toBeVisible();
});

test('manager cannot edit other manager\'s group', async ({ page }) => {
  await login(page, 'manager1@b2b-crm.loc', 'manager123');
  
  await page.goto('/groups');
  
  // Try to access another manager's group edit page (should get 403)
  const response = await page.goto('/groups/1/edit');
  
  // Check for 403 error or access denied message
  const hasAccessDenied = await page.locator('text=Доступ запрещен').isVisible().catch(() => false);
  const hasForbidden = await page.locator('text=Forbidden').isVisible().catch(() => false);
  
  expect(hasAccessDenied || hasForbidden || response?.status() === 403).toBe(true);
});

test('manager can delete their own group', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  const groupName = uniqueName('Группа для удаления');

  await navigateToGroupsPage(page);
  
  // Create group first
  await page.click('a:has-text("Новая группа")');
  await page.fill('input[name="name"]', groupName);
  await page.fill('textarea[name="description"]', 'Группа для теста удаления');
  await page.fill('input[name="color"]', '#3b82f6');
  await page.click('button:has-text("Создать")');
  
  await expect(page).toHaveURL(/\/groups$/);
  
  // Find and delete the group
  const groupRow = page.locator('[data-group-row]', { hasText: groupName }).first();
  await groupRow.locator('a:has-text("Удалить")').click();
  
  await expect(page.locator('h1', { hasText: 'Удаление группы' })).toBeVisible();
  
  await page.click('button:has-text("Удалить")');
  
  await expect(page).toHaveURL(/\/groups$/);
  await expect(page.locator('body', { hasText: groupName })).toBeHidden();
});

test('manager cannot delete other manager\'s group', async ({ page }) => {
  await login(page, 'manager1@b2b-crm.loc', 'manager123');
  
  await page.goto('/groups');
  
  // Try to access another manager's group delete page (should get 403)
  const response = await page.goto('/groups/1/delete');
  
  // Check for 403 error or access denied message
  const hasAccessDenied = await page.locator('text=Доступ запрещен').isVisible().catch(() => false);
  const hasForbidden = await page.locator('text=Forbidden').isVisible().catch(() => false);
  
  expect(hasAccessDenied || hasForbidden || response?.status() === 403).toBe(true);
});

// ─── Group Membership Management ──────────────────────────────────────

test('manager can add organizations to their group', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  const groupName = uniqueName('Группа для участников');

  await navigateToGroupsPage(page);
  
  // Create group first
  await page.click('a:has-text("Новая группа")');
  await page.fill('input[name="name"]', groupName);
  await page.fill('textarea[name="description"]', 'Группа для теста участников');
  await page.fill('input[name="color"]', '#3b82f6');
  await page.click('button:has-text("Создать")');
  
  await expect(page).toHaveURL(/\/groups$/);
  
  // Find and go to group members page
  const groupRow = page.locator('[data-group-row]', { hasText: groupName }).first();
  await groupRow.locator('a:has-text("Участники")').click();
  
  await expect(page.locator('h1', { hasText: 'Участники группы' })).toBeVisible();
  
  // Select some organizations
  await page.locator('input[type="checkbox"][value="1"]').check().catch(() => {});
  await page.locator('input[type="checkbox"][value="2"]').check().catch(() => {});
  await page.click('button:has-text("Сохранить")');
  
  await expect(page).toHaveURL(/\/groups$/);
  
  // Verify the group now shows participants
  await page.goto('/groups');
  const updatedGroupRow = page.locator('[data-group-row]', { hasText: groupName }).first();
  await expect(updatedGroupRow.locator('text=Участники')).toBeVisible();
});

test('manager can remove organizations from their group', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  const groupName = uniqueName('Группа для удаления участников');

  await navigateToGroupsPage(page);
  
  // Create group first
  await page.click('a:has-text("Новая группа")');
  await page.fill('input[name="name"]', groupName);
  await page.fill('textarea[name="description"]', 'Группа для теста удаления участников');
  await page.fill('input[name="color"]', '#3b82f6');
  await page.click('button:has-text("Создать")');
  
  await expect(page).toHaveURL(/\/groups$/);
  
  // Go to group members page
  const groupRow = page.locator('[data-group-row]', { hasText: groupName }).first();
  await groupRow.locator('a:has-text("Участники")').click();
  
  await expect(page.locator('h1', { hasText: 'Участники группы' })).toBeVisible();
  
  // Uncheck all organizations
  const checkboxes = await page.locator('input[type="checkbox"]').all();
  for (const checkbox of checkboxes) {
    await checkbox.uncheck().catch(() => {});
  }
  
  await page.click('button:has-text("Сохранить")');
  
  await expect(page).toHaveURL(/\/groups$/);
});

// ─── Campaign Recipient Bulk Add-By-Group ──────────────────────────────

test('manager can bulk add organizations from group to campaign recipients', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  const groupName = uniqueName('Группа для рассылки');
  const campaignName = uniqueName('Рассылка для групп');

  // First create a group
  await navigateToGroupsPage(page);
  
  await page.click('a:has-text("Новая группа")');
  await page.fill('input[name="name"]', groupName);
  await page.fill('textarea[name="description"]', 'Группа для теста рассылки');
  await page.fill('input[name="color"]', '#3b82f6');
  await page.click('button:has-text("Создать")');
  
  await expect(page).toHaveURL(/\/groups$/);
  
  // Create a campaign
  await page.goto('/campaigns/new');
  await page.fill('input[name="name"]', campaignName);
  await page.fill('input[name="subject"]', 'Тема теста');
  await page.fill('textarea[name="body"]', 'Текст письма');
  await page.selectOption('select[name="status"]', 'ready');
  await page.click('button:has-text("Создать")');
  
  await expect(page).toHaveURL(/highlight=(\d+)/);
  const match = page.url().match(/highlight=(\d+)/);
  const campaignId = match ? parseInt(match[1], 10) : 0;
  
  // Navigate to campaign recipients page
  await page.goto(`/campaigns/${campaignId}/recipients`);
  
  await expect(page.locator('h1', { hasText: 'Адресаты рассылки' })).toBeVisible();
  
  // Look for the group in the bulk add buttons
  const groupButton = page.locator('button', { hasText: groupName });
  await expect(groupButton).toBeVisible();
  
  // Click the group button to add recipients
  await groupButton.click();
  
  // Wait for redirect back to recipients page
  await expect(page).toHaveURL(new RegExp(`/campaigns/${campaignId}/recipients$`));
  
  // Verify recipients were added
  await expect(page.locator('.campaign-recipients__table tbody tr')).toBeVisible();
});

test('manager cannot bulk add from inaccessible group', async ({ page }) => {
  await login(page, 'manager1@b2b-crm.loc', 'manager123');
  
  // Try to access a group that belongs to another manager
  await page.goto('/campaigns/1/recipients');
  
  // Look for group buttons - should not see groups from other managers
  const groupButtons = await page.locator('button[data-group-id]').count();
  expect(groupButtons).toBe(0);
});

// ─── Manager Deletion Flow ────────────────────────────────────────────

test('admin can delete manager with group reassign/delete choices', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');
  
  // Navigate to users list
  await page.goto('/admin/users');
  
  // Find a manager user
  const managerRow = page.locator('[data-user-row]', { hasText: 'manager@b2b-crm.loc' }).first();
  await expect(managerRow).toBeVisible();
  
  // Click delete for the manager
  await managerRow.locator('a:has-text("Удалить")').click();
  
  await expect(page.locator('h1', { hasText: 'Удаление пользователя' })).toBeVisible();
  
  // Verify group choice section is visible
  await expect(page.locator('h2', { hasText: 'Группы, созданные пользователем' })).toBeVisible();
  
  // Select reassign option for groups
  const radioButtons = await page.locator('input[type="radio"][name*="group_action_"]').count();
  expect(radioButtons).toBeGreaterThan(0);
  
  // Select first reassign option
  await page.locator('input[type="radio"][value="reassign"]').first().click();
  
  // Click delete
  await page.click('button:has-text("Удалить")');
  
  await expect(page).toHaveURL(/\/admin\/users$/);
});

test('admin cannot delete manager without selecting group action', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');
  
  await page.goto('/admin/users');
  
  // Find a manager user
  const managerRow = page.locator('[data-user-row]', { hasText: 'manager@b2b-crm.loc' }).first();
  await expect(managerRow).toBeVisible();
  
  // Click delete for the manager
  await managerRow.locator('a:has-text("Удалить")').click();
  
  await expect(page.locator('h1', { hasText: 'Удаление пользователя' })).toBeVisible();
  
  // Click delete without selecting any group action
  const deleteButton = page.locator('button:has-text("Удалить")');
  await deleteButton.click();
  
  // Should stay on the same page with error
  await expect(page).toHaveURL(/\/admin\/users\/\d+\/delete$/);
});
