import { expect, test } from '@playwright/test';

// 4.2 Компоненты: кнопки, поля форм, оффер-карточки, модалка, шапка/подвал.
// Контактные карточки и таблицы покрываются на уровне скомпилированного CSS
// (экраны списков появится с доменным CRUD-изменением).

const CTA_GRADIENT = 'linear-gradient(rgb(214, 106, 43) 0%, rgb(224, 154, 104) 100%)';
const GREEN_GRADIENT = 'linear-gradient(rgb(85, 150, 74) 0%, rgb(71, 133, 64) 100%)';
const MENU_GRADIENT = 'linear-gradient(rgb(71, 133, 64) 0%, rgb(94, 158, 71) 100%)';
const ORANGE = 'rgb(214, 106, 43)';
const BLUE = 'rgb(32, 121, 158)';

test('primary-кнопка не меняется при hover', async ({ page }) => {
  await page.goto('/');
  const btn = page.locator('.hero .btn--primary');
  const before = await btn.evaluate((el) => getComputedStyle(el).backgroundImage);
  await btn.hover();
  const after = await btn.evaluate((el) => getComputedStyle(el).backgroundImage);
  expect(before).toBe(CTA_GRADIENT);
  expect(after).toBe(CTA_GRADIENT);
});

test('secondary-кнопка: белая, рамка 2px, hover-инверсия', async ({ page }) => {
  await page.goto('/');
  const btn = page.locator('.hero .btn--secondary');
  await expect(btn).toBeVisible();

  const normal = await btn.evaluate((el) => {
    const s = getComputedStyle(el);
    return { bg: s.backgroundColor, color: s.color, border: s.borderWidth, style: s.borderStyle, borderColor: s.borderColor };
  });
  expect(normal.bg).toBe('rgb(255, 255, 255)'); // белая подложка
  expect(normal.color).toBe(ORANGE);
  expect(normal.style).toBe('solid');
  expect(normal.border).toBe('2px');
  expect(normal.borderColor).toBe(ORANGE);

  await btn.hover();
  await page.waitForFunction(() => getComputedStyle(document.querySelector('.hero .btn--secondary')!).backgroundColor === 'rgb(214, 106, 43)');
  const hovered = await btn.evaluate((el) => {
    const s = getComputedStyle(el);
    return { bg: s.backgroundColor, color: s.color };
  });
  expect(hovered.bg).toBe(ORANGE);
  expect(hovered.color).toBe('rgb(255, 255, 255)');
});

test('ссылка «все элементы» с >>>', async ({ page }) => {
  await page.goto('/');
  const link = page.locator('.btn--all');
  await expect(link).toBeVisible();
  expect(await link.evaluate((el) => getComputedStyle(el, '::after').content)).toBe('">>>"');
  const styles = await link.evaluate((el) => {
    const s = getComputedStyle(el);
    return { fontSize: s.fontSize, fontWeight: s.fontWeight, color: s.color };
  });
  expect(styles.fontSize).toBe('18px');
  expect(styles.fontWeight).toBe('700');
  expect(styles.color).toBe(ORANGE);
});

test.describe('поля форм (страница входа)', () => {
  test('линия 2px #d66a2b, текст и плейсхолдер цветом линии', async ({ page }) => {
    await page.goto('/login');
    const input = page.locator('input[name="_username"]');
    await input.evaluate((el) => el.blur()); // снимаем autofocus
    await page.waitForFunction(() => getComputedStyle(document.querySelector('input[name="_username"]')!).borderBottomWidth === '2px');
    const styles = await input.evaluate((el) => {
      const s = getComputedStyle(el);
      return { borderBottom: s.borderBottomWidth, borderColor: s.borderBottomColor, color: s.color, fontFamily: s.fontFamily, fontSize: s.fontSize, background: s.backgroundColor };
    });
    expect(styles.borderBottom).toBe('2px');
    expect(styles.borderColor).toBe(ORANGE);
    expect(styles.color).toBe(ORANGE);
    expect(styles.fontFamily).toContain('Roboto Condensed');
    expect(styles.fontSize).toBe('16px');
    expect(styles.background).toBe('rgba(0, 0, 0, 0)');
  });

  test('фокус утолщает линию до 4px без смены цвета', async ({ page }) => {
    await page.goto('/login');
    const input = page.locator('input[name="_username"]');
    await input.focus();
    await page.waitForFunction(() => getComputedStyle(document.querySelector('input[name="_username"]')!).borderBottomWidth === '4px');
    const styles = await input.evaluate((el) => {
      const s = getComputedStyle(el);
      return { borderBottom: s.borderBottomWidth, borderColor: s.borderBottomColor };
    });
    expect(styles.borderBottom).toBe('4px');
    expect(styles.borderColor).toBe(ORANGE);
  });
});

test('бейдж цены: белый текст на зелёном градиенте', async ({ page }) => {
  await page.goto('/');
  const badge = page.locator('.offer-card__price-badge').first();
  await expect(badge).toBeVisible();
  const styles = await badge.evaluate((el) => {
    const s = getComputedStyle(el);
    return { bg: s.backgroundImage, color: s.color, fontWeight: s.fontWeight };
  });
  expect(styles.bg).toBe(GREEN_GRADIENT);
  expect(styles.color).toBe('rgb(255, 255, 255)');
  expect(styles.fontWeight).toBe('700');
});

test.describe('модальное окно', () => {
  test('открытие по «Создать»: градиент, backdrop, закрытие', async ({ page }) => {
    await page.goto('/');
    await page.click('[data-modal-open="modal-call"]');

    const modal = page.locator('#modal-call');
    await expect(modal).toHaveClass(/is-open/);

    const dialogBg = await modal.locator('.modal__dialog').evaluate((el) => getComputedStyle(el).backgroundImage);
    expect(dialogBg).toBe(GREEN_GRADIENT);

    const backdrop = await modal.locator('.modal__backdrop').evaluate((el) => getComputedStyle(el).backgroundColor);
    expect(backdrop).toBe('rgba(0, 0, 0, 0.6)');

    const closeBtn = modal.locator('.modal__close');
    await closeBtn.click();
    await expect(modal).not.toHaveClass(/is-open/);
  });

  test('форма в модалке: имя, телефон, согласие, пилюля', async ({ page }) => {
    await page.goto('/');
    await page.click('[data-modal-open="modal-call"]');
    await expect(page.locator('#modal-call-form input[name="call_name"]')).toBeVisible();
    await expect(page.locator('#modal-call-form input[name="call_phone"]')).toBeVisible();
    await expect(page.locator('#modal-call-form input[name="call_consent"]')).toBeVisible();
    await expect(page.locator('#modal-call-form .btn--primary')).toHaveText('Отправить заявку');
  });
});

test.describe('шапка', () => {
  test('CTA-пара: оранжевая и синяя полосы', async ({ page }) => {
    await page.goto('/');
    const call = page.locator('.cta-bar--call');
    const create = page.locator('.cta-bar--create');
    await expect(call).toHaveText('Позвонить');
    await expect(create).toHaveText('Создать');
    const callBar = await call.evaluate((el) => getComputedStyle(el, '::before').backgroundColor);
    const createBar = await create.evaluate((el) => getComputedStyle(el, '::before').backgroundColor);
    expect(callBar).toBe(ORANGE);
    expect(createBar).toBe(BLUE);
  });

  test('полоса меню: зелёный градиент, белые пункты, hover светлее', async ({ page }) => {
    await page.goto('/');
    const strip = page.locator('.header__menu');
    expect(await strip.evaluate((el) => getComputedStyle(el).backgroundImage)).toBe(MENU_GRADIENT);

    const link = strip.locator('.header__menu-link').first();
    expect(await link.evaluate((el) => getComputedStyle(el).color)).toBe('rgb(255, 255, 255)');
    await link.hover();
    expect(await link.evaluate((el) => getComputedStyle(el).color)).toBe('rgb(143, 196, 111)');
  });
});

test('подвал: зелёный градиент, белый текст', async ({ page }) => {
  await page.goto('/');
  const footer = page.locator('.footer');
  expect(await footer.evaluate((el) => getComputedStyle(el).backgroundImage)).toBe(GREEN_GRADIENT);
  expect(await footer.evaluate((el) => getComputedStyle(el).color)).toBe('rgb(255, 255, 255)');
  await expect(footer.locator('.footer__menu li').first()).toBeVisible();
});

test('скомпилированный CSS: контактные карточки и таблицы соответствуют спецификации', async ({ page }) => {
  await page.goto('/');
  const cssUrl = await page.evaluate(() => {
    const link = document.querySelector('link[rel="stylesheet"]');
    return link?.getAttribute('href') ?? '';
  });
  const css = await page.request.get(cssUrl).then((r) => r.text());

  expect(css).toContain('linear-gradient(180deg, #d66a2b 0%, #e09a68 100%)');

  // Контактные карточки
  expect(css).toContain('.card__name');
  expect(css).toContain('.card__phone');
  expect(css).toContain('#20799e'); // имя синее
  expect(css).toContain('#d66a2b'); // телефон оранжевый
  expect(css).toContain('border-left:3px solid #20799e'); // левая полоса имени

  // Таблицы: зебра
  const zebra1 = css.indexOf('#e3f1f6');
  const zebra2 = css.indexOf('#e5f5fb');
  expect(zebra1).toBeGreaterThan(-1);
  expect(zebra2).toBeGreaterThan(-1);
  expect(css).toContain('.table__status-success'); // зелёный статус
});