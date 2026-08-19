import { expect, test } from '@playwright/test';

// 4.1 Токены: computed-стили welcome page сверяются с hex-значениями спецификации
const CTA_GRADIENT = 'linear-gradient(rgb(214, 106, 43) 0%, rgb(224, 154, 104) 100%)';
const GREEN_GRADIENT = 'linear-gradient(rgb(85, 150, 74) 0%, rgb(71, 133, 64) 100%)';
const BLUE = 'rgb(32, 121, 158)';
const ORANGE = 'rgb(214, 106, 43)';
const GRAY = 'rgb(90, 90, 90)';

test.use({ viewport: { width: 1440, height: 900 } });

test('первичная CTA-кнопка: оранжевый градиент и белый текст', async ({ page }) => {
  await page.goto('/');
  const btn = page.locator('.hero .btn--primary');
  await expect(btn).toHaveCount(1);
  const styles = await btn.evaluate((el) => {
    const s = getComputedStyle(el);
    return { backgroundImage: s.backgroundImage, color: s.color, borderRadius: s.borderRadius, fontWeight: s.fontWeight };
  });
  expect(styles.backgroundImage).toBe(CTA_GRADIENT);
  expect(styles.color).toBe('rgb(255, 255, 255)');
  expect(styles.borderRadius).toBe('30px');
  expect(styles.fontWeight).toBe('700');
});

test('статистика: числа 56px bold белые на зелёном градиенте', async ({ page }) => {
  await page.goto('/');
  const fig = page.locator('.stats__figure').first();
  await expect(fig).toBeVisible();
  const styles = await fig.evaluate((el) => {
    const s = getComputedStyle(el);
    return { fontSize: s.fontSize, fontWeight: s.fontWeight, color: s.color };
  });
  expect(styles.fontSize).toBe('56px');
  expect(styles.fontWeight).toBe('700');
  expect(styles.color).toBe('rgb(255, 255, 255)');

  const band = page.locator('.stats');
  expect(await band.evaluate((el) => getComputedStyle(el).backgroundImage)).toBe(GREEN_GRADIENT);
});

test('заголовок h2: Roboto Condensed, синяя полоса, синий цвет', async ({ page }) => {
  await page.goto('/');
  const h2 = page.locator('h2').first();
  const styles = await h2.evaluate((el) => {
    const s = getComputedStyle(el);
    return {
      fontFamily: s.fontFamily,
      fontSize: s.fontSize,
      fontWeight: s.fontWeight,
      color: s.color,
      borderLeftWidth: s.borderLeftWidth,
      borderLeftColor: s.borderLeftColor,
      paddingLeft: s.paddingLeft,
    };
  });
  expect(styles.fontFamily).toContain('Roboto Condensed');
  expect(styles.fontSize).toBe('28px');
  expect(styles.fontWeight).toBe('700');
  expect(styles.color).toBe(BLUE);
  expect(parseFloat(styles.borderLeftWidth)).toBeGreaterThanOrEqual(5); // 0.19em от 28px (браузер округляет)
  expect(styles.borderLeftColor).toBe(BLUE);
  expect(parseFloat(styles.paddingLeft)).toBeGreaterThanOrEqual(8); // 0.3em
});

test('тело страницы: Roboto 15px, серый текст, белый фон', async ({ page }) => {
  await page.goto('/');
  const body = await page.evaluate(() => {
    const s = getComputedStyle(document.body);
    return { fontFamily: s.fontFamily, fontSize: s.fontSize, color: s.color, background: s.backgroundColor };
  });
  expect(body.fontFamily).toContain('Roboto');
  expect(body.fontSize).toBe('15px');
  expect(body.color).toBe(GRAY);
  expect(body.background).toBe('rgb(255, 255, 255)');
});

test('отсутствие box-shadow на всех элементах welcome page', async ({ page }) => {
  await page.goto('/');
  await page.waitForLoadState('networkidle');
  const offenders = await page.evaluate(() => {
    const bad: string[] = [];
    for (const el of document.querySelectorAll('*')) {
      const s = getComputedStyle(el);
      if (s.boxShadow && s.boxShadow !== 'none') {
        bad.push(`${el.tagName}.${el.className}`.slice(0, 80));
      }
    }
    return bad.slice(0, 10);
  });
  expect(offenders).toEqual([]);
});

test('контейнер на 1440px: не шире 1360px по центру', async ({ page }) => {
  await page.goto('/');
  const box = await page.locator('.container').first().evaluate((el) => {
    const r = el.getBoundingClientRect();
    return { width: r.width, left: r.left, right: r.right };
  });
  expect(box.width).toBeLessThanOrEqual(1360);
  expect(box.left).toBeGreaterThan(0);
  expect(box.right).toBeLessThan(1440);
});

test('ни один элемент не использует тёмную тему', async ({ page }) => {
  await page.goto('/');
  const bg = await page.evaluate(() => {
    const s = getComputedStyle(document.body);
    return Number(s.backgroundColor.match(/\d+/g)?.[0] ?? 0);
  });
  expect(bg).toBeGreaterThan(240); // светлый фон
});