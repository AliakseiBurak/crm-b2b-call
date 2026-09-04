## Context

See proposal.md — Why. `Call` already has `is_deal`, unused `campaign_id` (scalar, no FK), and `next_call_id`. Recipients live on `CampaignRecipient`; `MailingService` sends only those rows. Campaigns have statuses; launch is `Campaign::launch()`, not `autoLaunch`. Forms are manual Twig + `CallController`, not Symfony Form types. Same-contact add on the recipients page is currently ignored.

## Goals / Non-Goals

**Goals:**
- Keep result data on `Call`; wire mailing to `CampaignRecipient`
- Command-style next-call date and campaign select (empty unless the user acts)
- Same-contact replace + resend when `launchedAt` is set
- Manual call form, dashboard modal, delete warning, fixtures, ADR-0004, ER

**Non-Goals:**
- `CallResult` table or result-type enum
- Refusal-remove from the call result form (recipients removed only on campaign page)
- Auto-launch of campaigns
- Call session / launch window after a batch of calls
- Automated tests (manual checklist only)
- `forms-validation` change (draft; follow current form patterns)

## Decisions

### 1. No separate result entity
**Решение**: флаги и FK на `Call` (`is_deal`, `is_no_answer`, `campaign_id` → `Campaign` SET NULL, `next_call_id` → `Call` SET NULL).
**Почему**: у результата нет отдельного жизненного цикла; адресат и следующий звонок уже сущности.
**Альтернатива**: таблица `CallResult` — лишние каскады без выигрыша.

### 2. Actions, not a type enum
**Решение**: независимые действия; письмо + следующий звонок допустимы; сделка и «нет ответа» не блокируют рассылку. Отказ от рассылки в форме звонка отсутствует.
**Почему**: менеджер меняет тип письма во время звонка и всё равно планирует перезвон; удаление адресата — отдельная операция на странице адресатов.
**Альтернатива**: шесть взаимоисключающих типов — запрещает нужные сочетания.

### 3. Command fields vs sticky marks
**Решение**: дата следующего звонка и выбор рассылки пустые при открытии формы, пока действие ещё не выполнено. «Сделка» и «нет ответа» — липкие чекбоксы внизу формы. Если `next_call_id` уже задан, поле даты следующего звонка не показывается и не принимает новую дату; связанный звонок меняют или удаляют через его собственную форму в списке организации. Удаление связанного звонка (`ON DELETE SET NULL`) обнуляет `next_call_id`; поле даты снова появляется. Последняя кампания — контекст (ссылка), не командное поле.
**Почему**: повторное сохранение заметок не создаёт звонки и не шлёт письма.

### 3a. Режим «Будущий звонок» в форме
**Решение**: UI-чекбокс `is_future_call` (не колонка БД). Порядок полей: организация → контакт → заметка → «Будущий звонок» → запланированная дата (только при включённом чекбоксе) **или** факт/рассылка/следующий звонок (только при выключенном) → «Сделка совершена» / «Нет ответа». Поле фактической даты предустановлено текущими датой и временем (при создании и при открытии без `made_at`). Если у звонка уже есть `made_at`, чекбокс и поле запланированной даты недоступны; сервер игнорирует `is_future_call` для такого звонка; очистить `made_at` нельзя — только изменить (иначе 422). При включённом режиме сервер принимает `scheduled_at` (в т.ч. пустой) и заметку/орг/контакт, сбрасывает факт (`made_at`/`made_by`) и флаги сделки/нет ответа и **не** выполняет действия результата; при выключенном — поле `scheduled_at` не показывается, существующая запланированная дата на сущности сохраняется без изменения из формы. Чекбокс включён по умолчанию, если у звонка есть `scheduled_at` ≥ сегодня и нет `made_at`.
**Почему**: планирование и фиксация результата — разные режимы одной формы; менеджер не смешивает поля.
**Альтернатива**: всегда показывать `scheduled_at` — отклонена (путаница с датой следующего звонка).

### 4. Recipient upsert from the call
**Решение**: в списке рассылки — все статусы **кроме** `archived` (`draft`, `ready`, `launched`, `failed`). Нет строки → создать. Есть строка (любой контакт, в том числе тот же) → заменить. `launchedAt` задан → `replacementCount+1`, новый `pending`, flash. Иначе замена без счётчика и без flash о повторной отправке. Контакт адресата предвыбирается из контакта звонка.
**Почему**: менеджеру удобно ставить организацию в черновик или готовую кампанию прямо со звонка; архив недоступен.
**Альтернатива (отклонена)**: только `ready`|`launched` — слишком узко для черновиков и failed.

### 5. No refusal on the call form
**Решение**: поля отказа нет. Убрать адресата можно только на странице `/campaigns/{id}/recipients`.
**Почему**: отказ — операция над текущим участием кампании, не факт результата звонка.

### 6. Delete call does not undo mailing
**Решение**: удаляется только `Call`. Адресат и порождённые звонки остаются. На «Удаление звонка» — текст и ссылка `target=_blank` на `/campaigns/{id}/recipients`, если `campaign_id` задан.

### 7. Manual controllers, not Form types
**Решение**: разметка в `call/form.html.twig`, `_edit_modal.html.twig`, `_row.html.twig`; JS в `call-modal.js` (переключение режима «Будущий звонок»); разбор полей в `CallController`. Symfony `CallType` не вводить.

### 8. Recreate DB in development
**Решение**: миграции + обновлённые фикстуры; контейнеры можно пересоздать. Прод-данных нет.

## Architecture (C4-inspired)

Assumptions: one Symfony web app, MySQL, existing `app:campaign:send` worker. Format: Mermaid. Rigor: lightweight component + dynamic, not full C4.

```mermaid
flowchart LR
  manager[Manager]
  web[Symfony web: CallController]
  camp[CampaignController]
  db[(MySQL)]
  worker[app:campaign:send]

  manager -->|save call result| web
  manager -->|replace recipient| camp
  web -->|Call traces + CampaignRecipient| db
  camp -->|replace CampaignRecipient| db
  worker -->|pending recipients| db
```

```mermaid
sequenceDiagram
  participant M as Manager
  participant CC as CallController
  participant DB as MySQL
  participant W as MailingService

  M->>CC: save madeAt, madeBy + campaign + optional next date
  alt mailing campaign chosen
    CC->>DB: upsert CampaignRecipient
    CC->>DB: set Call.campaign_id
  end
  opt next-call date filled
    CC->>DB: insert Call and set next_call_id
  end
  opt launchedAt set on replace
    CC-->>M: flash resend
    W->>DB: send new pending row
  end
```

## Risks / Trade-offs

- [Повторное открытие формы с пустым полем рассылки скрывает прошлый выбор] → показывать контекст «последняя рассылка» ссылкой, поле оставлять командой
- [Два канала замены адресата (звонок и страница адресатов) разъедутся] → одно правило: существующая строка = replace; flash только при `launchedAt`
- [SET NULL при удалении кампании оставляет тип действий на звонке] → в UI «рассылка удалена», если `campaign_id` пуст, а в истории нужна подпись

## Migration Plan

1. Миграция: FK `call.campaign_id` → `campaign(id)` ON DELETE SET NULL; колонка `is_no_answer`
2. Пересоздание dev-БД / контейнеров и загрузка фикстур с примерами комбинаций
3. При apply — обновить `adr/0004-interaction-model-calls.md` и `openspec/design/er.md`

## Open Questions

<!-- none -->
