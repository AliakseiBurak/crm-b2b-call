import { expect, test } from '@playwright/test';

// 4.3 Семантика цветов: зелёный #5e9e47 — только статусы/успех.
const GREEN = 'rgb(94, 158, 71)';

test('заголовки и карточки не окрашены в зелёный', async ({ page }) => {
  await page.goto('/');
  const greenHeadings = await page.evaluate(() => {
    const seen: string[] = [];
    for (const el of document.querySelectorAll('h1, h2, h3')) {
      const c = getComputedStyle(el).color;
      if (c === 'rgb(94, 158, 71)') seen.push(el.textContent?.trim().slice(0, 40) ?? el.tagName);
    }
    return seen;
  });
  expect(greenHeadings).toEqual([]);

  const greenCards = await page.evaluate(() => {
    const seen: string[] = [];
    for (const el of document.querySelectorAll('.card__name, .offer-card__title, .promo__title')) {
      const c = getComputedStyle(el).color;
      if (c === 'rgb(94, 158, 71)') seen.push(el.textContent?.trim().slice(0, 40) ?? el.className);
    }
    return seen;
  });
  expect(greenCards).toEqual([]);
});

test('зелёный присутствует только на статусных/градиентных элементах', async ({ page }) => {
  await page.goto('/');
  const greenOwners = await page.evaluate((green) => {
    const found: string[] = [];
    for (const el of document.querySelectorAll('*')) {
      const s = getComputedStyle(el);
      const bg = s.backgroundColor === green || s.backgroundImage.includes('85, 150, 74') || s.backgroundImage.includes('71, 133, 64') || s.backgroundImage.includes('94, 158, 71');
      if (bg) found.push(`${el.tagName.toLowerCase()}.${el.className}`.trim().slice(0, 60));
    }
    return [...new Set(found)].slice(0, 12);
  }, GREEN);
  // Допустимые владельцы зелёного: градиентные ленты (stats, footer, меню, бейдж цены, модалка, слоган)
  for (const owner of greenOwners) {
    expect(owner).toMatch(/stats|footer|header__menu|offer-card__price-badge|hero__slogan|modal__/);
  }
});