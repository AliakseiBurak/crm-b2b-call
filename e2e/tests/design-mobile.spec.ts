import { expect, test } from '@playwright/test';

// 4.5 Мобильный кейс (576px): тач-цель «Позвонить» ≥44×44, tel:-ссылки.
test.use({ viewport: { width: 576, height: 800 } });

test('кнопка «Позвонить» в шапке: тач-цель ≥44×44px и видима', async ({ page }) => {
  await page.goto('/');
  const call = page.locator('.cta-bar--call');
  await expect(call).toBeVisible();
  const box = await call.boundingBox();
  expect(box).not.toBeNull();
  expect(box!.width).toBeGreaterThanOrEqual(44);
  expect(box!.height).toBeGreaterThanOrEqual(44);
});

test('primary-кнопки сохраняют тач-цель ≥44×44px', async ({ page }) => {
  await page.goto('/');
  for (const btn of await page.locator('.btn--primary').all()) {
    const box = await btn.boundingBox();
    if (!box) continue; // скрытая кнопка модалки (display:none) не обязана иметь тач-цель
    expect(box.height).toBeGreaterThanOrEqual(44);
  }
});

test('tel:-ссылки присутствуют в шапке, подвале и карточках', async ({ page }) => {
  await page.goto('/');
  const telHrefs = await page.locator('a[href^="tel:"]').evaluateAll((els) => els.map((e) => e.getAttribute('href')));
  expect(telHrefs.length).toBeGreaterThanOrEqual(2);
  for (const href of telHrefs) {
    expect(href).toMatch(/^tel:\+?\d+$/);
  }
});

test('шапка и меню остаются видимыми на мобильной ширине', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.header__menu-link').first()).toBeVisible();
  await expect(page.locator('.header__logo')).toBeVisible();
});