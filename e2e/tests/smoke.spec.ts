import { expect, test } from '@playwright/test';

test('главная страница отвечает 200', async ({ page }) => {
  const response = await page.goto('/');

  expect(response?.status()).toBe(200);
  await expect(page.getByRole('heading', { name: 'B2B Call CRM' })).toBeVisible();
});

test('вход администратором', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="_username"]', 'admin@b2b-crm.loc');
  await page.fill('input[name="_password"]', 'admin123');
  await page.click('button[type="submit"]');

  await expect(page.getByText('Вы вошли как admin@b2b-crm.loc')).toBeVisible();

  await page.goto('/dashboard');
  await expect(page.getByRole('heading', { name: 'Панель' })).toBeVisible();
});

test('вход менеджером', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="_username"]', 'manager@b2b-crm.loc');
  await page.fill('input[name="_password"]', 'manager123');
  await page.click('button[type="submit"]');

  await expect(page.getByText('Вы вошли как manager@b2b-crm.loc')).toBeVisible();

  await page.goto('/dashboard');
  await expect(page.getByRole('heading', { name: 'Панель' })).toBeVisible();
});

test('неверный пароль: ошибка и отсутствие сессии', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="_username"]', 'admin@b2b-crm.loc');
  await page.fill('input[name="_password"]', 'wrong-password');
  await page.click('button[type="submit"]');

  await expect(page.locator('.alert-error')).toBeVisible();

  await page.goto('/');
  await expect(page.getByText('Вы вошли как')).toHaveCount(0);
});
