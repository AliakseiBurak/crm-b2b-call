import { expect, test } from '@playwright/test';

const loginSubmit = 'form[action="/login"] button[type="submit"]';

test('главная страница отвечает 200', async ({ page }) => {
  const response = await page.goto('/');

  expect(response?.status()).toBe(200);
  await expect(page.getByRole('heading', { name: 'B2B Call CRM — обзвон, который приводит к договорённостям' })).toBeVisible();
});

test('вход администратором', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="_username"]', 'admin@b2b-crm.loc');
  await page.fill('input[name="_password"]', 'admin123');
  await page.click(loginSubmit);

  await expect(page.locator('.header__menu-link', { hasText: 'Панель' })).toBeVisible();

  await page.goto('/dashboard');
  await expect(page.getByRole('heading', { name: 'Панель' })).toBeVisible();
  await expect(page.locator('.dashboard-head__greeting')).toHaveText('Вы вошли как администратор admin@b2b-crm.loc');
  await expect(page.locator('.stats__caption', { hasText: 'Обзвонено сегодня' })).toBeVisible();
});

test('вход менеджером', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="_username"]', 'manager@b2b-crm.loc');
  await page.fill('input[name="_password"]', 'manager123');
  await page.click(loginSubmit);

  await expect(page.locator('.header__menu-link', { hasText: 'Панель' })).toBeVisible();

  await page.goto('/dashboard');
  await expect(page.getByRole('heading', { name: 'Панель' })).toBeVisible();
  await expect(page.locator('.dashboard-head__greeting')).toHaveText('Вы вошли как менеджер manager@b2b-crm.loc');
  await expect(page.locator('.stats__caption', { hasText: 'Обзвонено сегодня' })).toBeVisible();
});

test('неверный пароль: ошибка и отсутствие сессии', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="_username"]', 'admin@b2b-crm.loc');
  await page.fill('input[name="_password"]', 'wrong-password');
  await page.click(loginSubmit);

  await expect(page.locator('.alert-error')).toBeVisible();
  await expect(page.locator('.header__menu-link', { hasText: 'Панель' })).toHaveCount(0);
});