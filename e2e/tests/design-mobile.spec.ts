import { expect, test } from '@playwright/test';

// 4.5 Мобильный кейс (576px): навигация и tel:-ссылки присутствуют.
test.use({ viewport: { width: 576, height: 800 } });

test('ссылки навигации шапки видимы', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.header__menu-link').first()).toBeVisible();
  await expect(page.locator('.header__logo')).toBeVisible();
});

test('tel:-ссылки присутствуют в подвале', async ({ page }) => {
  await page.goto('/');
  const telHrefs = await page.locator('a[href^="tel:"]').evaluateAll((els) => els.map((e) => e.getAttribute('href')));
  expect(telHrefs.length).toBeGreaterThanOrEqual(1);
  for (const href of telHrefs) {
    expect(href).toMatch(/^tel:\+?\d+$/);
  }
});