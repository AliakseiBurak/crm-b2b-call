## Context

Contact содержит неиспользуемые `contactType` / `contactPerson`. Ошибки доставки рассылок не сведены к контакту/организации; сброс статуса и навигация из адресатов отсутствуют. Организацию без e-mail можно сделать адресатом.

См. proposal.md — Why.

## Goals / Non-Goals

**Goals:**
- Упростить Contact: удалить type/person, сохранить `position`
- Показать ошибки доставки: на контакте (только этот contact), на организации (все ошибки org — org-wide и по контактам), отметка bounced на дашборде
- Единый сброс получателя `failed`/`bounced` → `pending` с очисткой retry-полей; для bounced — страница подтверждения
- Навигация org/contact из таблицы адресатов и с формы контакта
- Список контактов на форме организации
- Запрет адресата без доставляемого e-mail на уровне домена рассылки

**Non-Goals:**
- Менять логику MailingService / worker (кроме потребления сброшенного `pending`)
- Новые статусы получателей
- Менять модель доступа (ADR-0005–0008); reset использует ту же область, что и операции с адресатами

## Decisions

### 1. Где показывать ошибки доставки

```
CampaignRecipient (failed|bounced, campaign ≠ archived)
        │
        ├── GET /contacts/{id}/edit
        │     только recipients с этим contact
        │
        └── GET /organizations/{id}/edit
              все recipients этой организации
              (contact NULL или любой contact)
              колонка «Контакт»: "—" или имя → edit

Dashboard contact card:
  mark, если EXISTS bounced для этого contact, campaign ≠ archived
```

**Обоснование:** форма организации — свод ошибок по org; форма контакта — узкий список для правки e-mail этого контакта. Дублирование строк контакта на org и contact — намеренно.

### 2. Один POST для сброса; bounced через confirm

**Решение:** `POST /campaigns/{id}/recipients/{recipientId}/reset` принимает и `failed`, и `bounced`. Для bounced UI сначала открывает страницу подтверждения (предупреждение об отклонении почтовым сервером), затем тот же POST. Сброс: статус → `pending`, очистка `errorMessage`, `retryCount`, `retryAt`.

**Доступ:** как у прочих операций с адресатами — admin или менеджер с org в области доступа; иначе 403. Несуществующий / чужой recipientId → 404.

**Альтернативы:** отдельный `reset-confirm` POST — отклонена как лишняя поверхность.

### 3. E-mail обязателен для адресата (домен рассылки)

**Решение:** создание `CampaignRecipient` SHALL требовать хотя бы один e-mail у контактов организации. Правило общее для ручного добавления, массового «все организации» и пути из результата звонка.

### 4. Удаление полей Contact

**Решение:** миграция удаляет `contact_type`, `contact_person`; enum `ContactType` удаляется; формы, модалки, JS обновляются. `position` остаётся. ADR-0002 и Purpose в main `contacts` — при архивации.

### 5. Навигация

Кнопка «Организация» на edit контакта; кликабельные Организация/Контакт в таблице адресатов → соответствующие `/edit`.

## Risks / Trade-offs

- [Потеря данных type/person] → приемлемо: поля не используются в продуктовой логике.
- [Bounced reset без смены e-mail] → mitigate: обязательная страница подтверждения с явным предупреждением.
- [Дублирование строк ошибки контакта на org и contact] → намеренно: org = свод, contact = точка правки e-mail.

## Migration Plan

1. Миграция: drop `contact_type`, `contact_person`
2. Обновить entity, контроллеры, формы, модалки, JS
3. Reset + confirm page; таблицы ошибок; dashboard mark
4. Доменная проверка e-mail на всех entry points
5. `openspec/design/er.md` при реализации
6. При архивации: ADR-0002 + Purpose в `openspec/specs/contacts/spec.md`

## Open Questions

<!-- resolved: bounce mark = any non-archived bounced for that contact -->
