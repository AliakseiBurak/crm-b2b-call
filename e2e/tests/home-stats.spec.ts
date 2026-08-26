import { expect, test, type Page } from '@playwright/test';

// Статистика на домашней странице (change dashboard-stats-by-organization):
// карточка «Доступно организаций: Y», 9 показателей (Звонков/Ожидают/Просроченные),
// индикаторы «По организациям: N» со ссылками /dashboard?filter=<bucket>.
//
// Ожидаемые числа выведены из фикстур AppFixtures и детерминированы внутри
// суток: планы «на сегодня» стоят на 00:05, поэтому для любого запуска
// позже 00:05 они не попадают в окна waitingWeek/waitingMonth (> now),
// но остаются в waitingToday (BETWEEN todayStart..todayEnd):
//   Вектор   — факт сегодня 08:00 (исключает организацию из «Ожидают
//              сегодня»), нереализованный план вчера → overdue1 + called1
//              одновременно; план +1д с заметкой;
//   Парус    — 5 планов на вчера (3 совершены, 2 нет) + план сегодня без
//              факта сегодня → waiting1, overdue1, called7;
//   Сидоров  — просрочки -2д и -20д (overdue7/overdue30), план +45д — вне
//              окон ожидания;
//   Конкурент— вне области менеджера: факт сегодня, план сегодня, план
//              +14д, просрочка -5д — виден только админу;
//   Ромашка  — факты -3д/-10д, план +1д с контактом.
//
// Показатели «Звонков/Ожидают» считают звонки, «Просроченные» — организации.
// Менеджер (Y=6):  figures = [1,6,7 | 2,2,2 | 2,3,3], orgs = [1,3,3 | 1,0,0 | 2,3,3]
// Администратор (Y=7): figures = [2,7,9 | 3,2,3 | 2,4,4], orgs = [2,4,4 | 1,0,0 | 2,4,4]

const CAPTIONS = [
  'Звонков сегодня',
  'Звонков за 7 дней',
  'Звонков за 30 дней',
  'Ожидают сегодня',
  'Ожидают на неделе',
  'Ожидают в месяце',
  'Просроченные: вчера',
  'Просроченные: за 7 дней',
  'Просроченные: за 30 дней',
] as const;

const BUCKETS = [
  'called1', 'called7', 'called30',
  'waiting1', 'waiting7', 'waiting30',
  'overdue1', 'overdue7', 'overdue30',
] as const;

const OLD_CAPTIONS = ['Обзвонено сегодня', 'В течение недели', 'В течение месяца'] as const;

const loginSubmit = 'form[action="/login"] button[type="submit"]';

function statItem(page: Page, caption: string) {
  return page.locator('.stats__item', { hasText: caption });
}

async function login(page: Page, email: string, password: string) {
  await page.goto('/login');
  await page.fill('input[name="_username"]', email);
  await page.fill('input[name="_password"]', password);
  await page.click(loginSubmit);
  // Редирект после логина — на домашнюю страницу со статистикой (5.3)
  await expect(page).toHaveURL(/\/$/);
}

// 5.1, 5.3, 5.6 (менеджер): карточка Y и девять показателей после логина
test('менеджер после логина видит карточку Y=6 и 9 показателей на главной', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');

  await expect(page.locator('.stats__total')).toHaveText('Доступно организаций: 6');
  const figures = page.locator('.stats-home .stats__figure');
  await expect(figures).toHaveCount(9);

  const expectedFigures = ['1', '6', '7', '2', '2', '2', '2', '3', '3'];
  for (let i = 0; i < CAPTIONS.length; ++i) {
    await expect(statItem(page, CAPTIONS[i]).locator('.stats__figure')).toHaveText(expectedFigures[i]);
  }
});

// 5.6 (администратор): Y — все организации системы
test('администратор видит карточку Y=7 и показатели по всем организациям', async ({ page }) => {
  await login(page, 'admin@b2b-crm.loc', 'admin123');

  await expect(page.locator('.stats__total')).toHaveText('Доступно организаций: 7');

  const expectedFigures = ['2', '7', '9', '3', '2', '3', '2', '4', '4'];
  for (let i = 0; i < CAPTIONS.length; ++i) {
    await expect(statItem(page, CAPTIONS[i]).locator('.stats__figure')).toHaveText(expectedFigures[i]);
  }
});

// 5.2: гость видит только hero
test('гость на главной видит только hero, без карточки и статистики', async ({ page }) => {
  await page.goto('/');

  await expect(page.locator('.hero')).toBeVisible();
  await expect(page.locator('.stats__total')).toHaveCount(0);
  await expect(page.locator('.stats-home')).toHaveCount(0);
});

// 5.4: на панели организаций статистики нет — только таблица
test('на /dashboard нет карточки и статистики, только таблица организаций', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');
  await page.goto('/dashboard');

  await expect(page.getByRole('heading', { name: 'Панель' })).toBeVisible();
  await expect(page.locator('.org-table__row').first()).toBeVisible();
  await expect(page.locator('.stats__total')).toHaveCount(0);
  await expect(page.locator('.stats__figure')).toHaveCount(0);
  await expect(page.locator('.stats-home')).toHaveCount(0);
});

// 5.5: под каждым показателем — «По организации: N» со ссылкой filter=<bucket>
test('под каждым из девяти показателей ссылка «По организациям: N» с filter=<bucket>', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');

  const expectedOrgs = ['1', '3', '3', '1', '0', '0', '2', '3', '3'];
  for (let i = 0; i < CAPTIONS.length; ++i) {
    const link = statItem(page, CAPTIONS[i]).locator('a.stats__orgs');
    await expect(link).toHaveText(`По организациям: ${expectedOrgs[i]}`);
    await expect(link).toHaveAttribute('href', `/dashboard?filter=${BUCKETS[i]}`);
  }
});

// 5.12: пустая категория — «По организациям: 0», ссылка присутствует
test('пустая категория отображается как «По организациям: 0» с активной ссылкой', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');

  const link = statItem(page, 'Ожидают на неделе').locator('a.stats__orgs');
  await expect(link).toHaveText('По организациям: 0');
  await expect(link).toHaveAttribute('href', '/dashboard?filter=waiting7');
});

// 5.7: клик по индикатору ведёт на панель с фильтром
test('клик по индикатору переходит на /dashboard?filter=<bucket>', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');

  await statItem(page, 'Просроченные: вчера').locator('a.stats__orgs').click();

  await expect(page).toHaveURL(/\/dashboard\?filter=overdue1$/);
  await expect(page.locator('.org-table__row').first()).toBeVisible();
});

// 5.8: исключающая логика — факт сегодня убирает организацию из «Ожидают сегодня»
test('организация с фактом сегодня не учитывается в «Ожидают сегодня»', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');

  // У Вектора есть и факт сегодня, и план на сегодня; у Паруса — только план.
  // В индикаторе остаётся одна организация (Парус), Вектор исключён.
  await expect(statItem(page, 'Ожидают сегодня').locator('a.stats__orgs')).toHaveText('По организациям: 1');
});

// 5.10, 5.11: просрочки независимы от фактов; частичная реализация не отменяет просрочку
test('просроченная организация учитывается и в просроченных, и в звонках', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');

  // Вектор: нереализованный план вчера + факт сегодня → в обеих категориях;
  // Парус: 2 нереализованных из 5 вчерашних → тоже в «Просроченные: вчера».
  // Сидоров: просрочка -2д попадает только в «за 7 дней», не во «вчера».
  await expect(statItem(page, 'Просроченные: вчера').locator('a.stats__orgs')).toHaveText('По организациям: 2');
  await expect(statItem(page, 'Звонков сегодня').locator('a.stats__orgs')).toHaveText('По организациям: 1');
});

// 5.13: новые подписи на месте, старые отсутствуют
test('подписи показателей обновлены, старых подписей нет', async ({ page }) => {
  await login(page, 'manager@b2b-crm.loc', 'manager123');

  for (const caption of CAPTIONS) {
    await expect(page.locator('.stats__caption', { hasText: caption })).toBeVisible();
  }
  const texts = await page.locator('.stats__caption').allTextContents();
  for (const old of OLD_CAPTIONS) {
    expect(texts.some((t) => t.includes(old))).toBe(false);
  }
  // Старая подпись «Сегодня» (ждут) — точное совпадение, чтобы не задеть
  // новые подписи вида «Ожидают сегодня».
  expect(texts).not.toContain('Сегодня');
});
