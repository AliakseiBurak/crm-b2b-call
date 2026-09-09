## 1. Репозиторий

- [ ] 1.1 `CampaignRecipientRepository::statsForAllCampaigns(): array` —
  агрегатная выборка по всем рассылкам:
  `[{ campaignId, delivered, total }]`, где delivered — получатели со
  статусом `delivered` или `opened`, total — все получатели
- [ ] 1.2 `CampaignRecipientRepository::findStatusesForCampaign(int $campaignId): array` —
  `[{ recipientId, status }]` для всех адресатов рассылки

## 2. Контроллер

- [ ] 2.1 Создать `App\Controller\CampaignProgressController`
- [ ] 2.2 `statuses()` — маршрут `GET /campaigns/statuses`: JSON
  `[{ campaignId, delivered, total }]`, заголовок `Cache-Control: no-store`
- [ ] 2.3 `recipientStatuses(int $id)` — маршрут
  `GET /campaigns/{id}/recipients/statuses`: JSON `[{ recipientId, status }]`,
  заголовок `Cache-Control: no-store`, 404 для несуществующей рассылки

## 3. Клиентский JS: список рассылок

- [ ] 3.1 В `campaign/index.html.twig`: `setInterval(1900)` →
  `fetch('/campaigns/statuses')`
- [ ] 3.2 Обновлять ячейку `.campaign-table__stats-cell` строки
  `#campaign-<id>` значением «delivered из total» только при изменении
  значения (без перезагрузки страницы)

## 4. Клиентский JS: страница адресатов

- [ ] 4.1 В `campaign/recipients.html.twig`: добавить `data-recipient-id`
  строкам таблицы адресатов
- [ ] 4.2 `setInterval(1100)` → `fetch('/campaigns/{id}/recipients/statuses')`
- [ ] 4.3 Обновлять бейдж `.campaign-recipients__status` (текст и класс
  `--<status>`) строки с соответствующим `recipientId` только при изменении
  статуса; статусы других рассылок не затрагиваются

## 5. Документация

- [ ] 5.1 Обновить `openspec/specs/campaigns/spec.md` при архивировании
