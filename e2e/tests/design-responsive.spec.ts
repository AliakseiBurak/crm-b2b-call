import { expect, test } from '@playwright/test';

// 4.4 Адаптивность на брейкпоинтах 576/768/992/1200/1400.
const WIDTHS: [number, number][] = [
  [1400, 1360],
  [1200, 1200],
  [992, 992],
  [768, 768],
  [576, 576],
];

for (const [width, maxContainer] of WIDTHS) {
  test(`viewport ${width}px: контейнер не шире ${maxContainer}px по центру`, async ({ page }) => {
    await page.setViewportSize({ width, height: 800 });
    await page.goto('/');
    const box = await page.locator('.container').first().evaluate((el) => {
      const r = el.getBoundingClientRect();
      return { width: r.width, left: r.left, right: r.right };
    });
    expect(box.width).toBeLessThanOrEqual(maxContainer);
    expect(box.left).toBeGreaterThanOrEqual(0);
    expect(box.right).toBeLessThanOrEqual(width);
  });

  test(`viewport ${width}px: ленты полновесны, рабочие секции белые`, async ({ page }) => {
    await page.setViewportSize({ width, height: 800 });
    await page.goto('/');

    for (const sel of ['.stats', '.footer']) {
      const bandWidth = await page.locator(sel).evaluate((el) => el.getBoundingClientRect().width);
      expect(bandWidth, `${sel} полновесная`).toBe(width);
    }

    const workBg = await page.locator('.section--white').first().evaluate((el) => getComputedStyle(el).backgroundColor);
    expect(workBg).toBe('rgb(255, 255, 255)');

    const cardsSection = await page.locator('.section--cards').first().evaluate((el) => getComputedStyle(el).backgroundColor);
    expect(cardsSection).toBe('rgb(245, 246, 246)'); // #f5f6f6
  });
}

test('h2: 28px на широком, 24px на 576px', async ({ page }) => {
  await page.setViewportSize({ width: 1200, height: 800 });
  await page.goto('/');
  expect(await page.locator('h2').first().evaluate((el) => getComputedStyle(el).fontSize)).toBe('28px');

  await page.setViewportSize({ width: 575, height: 800 });
  await page.reload();
  expect(await page.locator('h2').first().evaluate((el) => getComputedStyle(el).fontSize)).toBe('24px');
});