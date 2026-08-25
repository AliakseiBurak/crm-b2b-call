import { expect, test, type Page } from '@playwright/test';

// Модальные окна звонков (change calls-crud): быстрое редактирование
// на дашборде без перезагрузки страницы, динамическая загрузка контактов.

const editModal = '[data-call-edit-modal] .modal__window';

async function login(page: Page, email: string, password: string) {
  await page.goto('/login');
  await page.fill('input[name="_username"]', email);
  await page.fill('input[name="_password"]', password);
  await page.click('form[action="/login"] button[type="submit"]');
  await expect(page.locator('.header__menu-link', { hasText: 'Панель' })).toBeVisible();
}

// Маркер живёт до перезагрузки страницы: если он «пережил» действие,
// перезагрузки не было (spec: модальные окна работают без перезагрузки).
async function markAlive(page: Page) {
  await page.evaluate(() => {
    (window as unknown as { __noReloadMarker?: boolean }).__noReloadMarker = true;
  });
}

async function expectStillSamePage(page: Page) {
  const marker = await page.evaluate(
    () => (window as unknown as { __noReloadMarker?: boolean }).__noReloadMarker,
  );
  expect(marker).toBe(true);
}

async function openFirstCallEditModal(page: Page) {
  await page.goto('/dashboard');
  const row = page.locator('[data-call-row]').first();
  // Строка звонка внутри <details> (аккордеон «Все звонки») — нужно раскрыть
  const details = row.locator('xpath=ancestor::details').first();
  await details.locator('xpath=summary').first().click();
  if (!(await row.isVisible())) {
    await details.locator('.org-calls__all-summary').click();
  }
  await row.locator('[data-call-edit]').click();
  await expect(page.locator(editModal)).toBeVisible();

  return row;
}

test('кнопка Изменить открывает модальное окно с данными звонка', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');

  const row = await openFirstCallEditModal(page);

  const scheduledAt = await row.getAttribute('data-call-scheduled-at');
  await expect(page.locator('[data-call-field="scheduled_at"]')).toHaveValue(scheduledAt ?? '');
});

test('отмена закрывает модальное окно без сохранения и без перезагрузки', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');

  await openFirstCallEditModal(page);
  await markAlive(page);

  await page.locator(editModal).locator('[data-modal-close]:visible').last().click();
  await expect(page.locator(editModal)).not.toBeVisible();
  await expectStillSamePage(page);
});

test('сохранение в модальном окне обновляет строку без перезагрузки страницы', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');

  const row = await openFirstCallEditModal(page);
  await markAlive(page);

  const notesField = page.locator('[data-call-field="notes"]');
  const newNotes = `e2e ${Date.now()}`;
  await notesField.fill(newNotes);
  await page.locator(editModal).locator('button[type="submit"]').first().click();

  await expect(page.locator(editModal)).not.toBeVisible();
  await expect(page.locator(`[data-call-row]:has-text("${newNotes}")`).first()).toBeVisible();
  await expectStillSamePage(page);
});

test('ошибка валидации в модальном окне показывается на русском', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');

  await openFirstCallEditModal(page);

  await page.locator('[data-call-field="scheduled_at"]').fill('');
  await page.locator(editModal).locator('button[type="submit"]').first().click();

  await expect(page.locator('[data-call-error="scheduledAt"]')).toBeVisible();
  await expect(page.locator('[data-call-error="scheduledAt"]')).toHaveText(
    'Дата звонка обязательна для заполнения',
  );
});

test('форма создания звонка подгружает контакты выбранной организации', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');

  await page.goto('/calls/new');
  // Выбор организации из выпадающего списка триггерит AJAX-загрузку контактов.
  const organizationSelect = page.locator('[data-call-organization]');
  const orgValue = await organizationSelect.locator('option:nth-child(2)').getAttribute('value');
  await organizationSelect.selectOption(orgValue ?? '');

  const contactOptions = page.locator('[data-call-contact-select] option');
  await expect(contactOptions.nth(1)).toBeAttached({ timeout: 5_000 });
  expect(await contactOptions.count()).toBeGreaterThan(1);
});

test('виджет выбора даты открывает календарь с выделенной текущей датой', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');

  await page.goto('/calls/new');
  const madeAt = page.locator('#made_at');
  await madeAt.click();

  const popover = madeAt
    .locator('xpath=ancestor::div[contains(@class,"field")]')
    .locator('.date-picker');
  await expect(popover).toBeVisible();
  // У пустого поля с временем виджет предвыбирает текущую дату
  // (spec «Менеджер фиксирует факт звонка»).
  await expect(popover.locator('.date-picker__day--today')).toBeVisible();
  await expect(popover.locator('.date-picker__day--selected')).toBeVisible();
});
