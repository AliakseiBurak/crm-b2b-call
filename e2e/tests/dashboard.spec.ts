import { expect, test, type Page } from '@playwright/test';

// 5.3 Дашборд: рендер статистики из реальных данных Call с учётом области
// доступа (ADR-0007). Числовые значения не ассертируются: фикстуры
// загружаются один раз, и количество звонков не должно валить тесты.

const loginSubmit = 'form[action="/login"] button[type="submit"]';

async function login(page: Page, email: string, password: string) {
  await page.goto('/login');
  await page.fill('input[name="_username"]', email);
  await page.fill('input[name="_password"]', password);
  await page.click(loginSubmit);
  await expect(page.locator('.header__menu-link', { hasText: 'Панель' })).toBeVisible();
}

test('гость редиректится с дашборда на вход', async ({ page }) => {
  await page.goto('/dashboard');
  await expect(page).toHaveURL(/\/login$/);
});

test('администратор видит блоки статистики по всем организациям', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');
  await page.goto('/dashboard');

  await expect(page.getByRole('heading', { name: 'Панель' })).toBeVisible();
  await expect(page.locator('.dashboard-head__greeting')).toHaveText('Вы вошли как администратор admin@b2b-crm.loc');

  const captions = await page.locator('.stats__caption').allTextContents();
  for (const label of ['Обзвонено сегодня', 'Обзвонено за 7 дней', 'Обзвонено за 30 дней', 'Сегодня', 'В течение недели', 'В течение месяца']) {
    expect(captions.some((c) => c.includes(label) || c === label)).toBe(true);
  }

  const figures = await page.locator('.stats__figure').count();
  expect(figures).toBe(6);
});

test('менеджер видит те же блоки статистики на своей области доступа', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  await page.goto('/dashboard');

  await expect(page.getByRole('heading', { name: 'Панель' })).toBeVisible();
  await expect(page.locator('.dashboard-head__greeting')).toHaveText('Вы вошли как менеджер manager@b2b-crm.loc');
  await expect(page.locator('.stats__figure')).toHaveCount(6);
});