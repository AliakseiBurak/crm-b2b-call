## 1. Доменный слой — сущности Doctrine

- [ ] 1.1 Создать сущность `CallResult` с enum-типом `ResultType` (new_call, no_answer_new_call, refusal, refusal_mailing, mailing, dealing), nullable FK campaign_id, nullable self-ref FK next_call_id, notes, created_at
- [ ] 1.2 Настроить enum `ResultType` в `CallResult` (PHP 8.5 backed enum для Doctrine)
- [ ] 1.3 Обновить сущность `Call`: удалить is_deal, campaign_id, next_call_id; добавить nullable FK result_id на CallResult
- [ ] 1.4 Настроить каскадные связи: Call → CallResult (onDelete SET NULL), CallResult → Campaign (onDelete SET NULL), CallResult → Call (onDelete SET NULL)

## 2. Репозитории

- [ ] 2.1 Создать `CallResultRepository` с методами findByType(), findWithCampaign()
- [ ] 2.2 Обновить `CallRepository`: удалить методы is_deal/campaign_id/next_call_id; добавить join с CallResult

## 3. Фикстуры

- [ ] 3.1 Создать фикстуры `call_result` с 6 типами результатов

## 4. Миграция БД

- [ ] 4.1 Создать миграцию: таблица call_result (id, type ENUM, campaign_id FK, next_call_id FK, notes, created_at)
- [ ] 4.2 Создать миграцию: изменение таблицы call (удалить is_deal, campaign_id, next_call_id; добавить result_id FK)

## 5. Формы Symfony

- [ ] 5.1 Создать форму `CallResultType` с выбором типа (enum), условными полями (campaign для mailing/refusal_mailing, next_call_date для new_call/no_answer_new_call), notes
- [ ] 5.2 Обновить форму `CallType`: заменить is_deal/campaign_id/next_call_id на поле result (CallResultType)
- [ ] 5.3 Настроить валидацию: обязательные поля при определённых типах (campaign при mailing/refusal_mailing, next_call_date при new_call/no_answer_new_call)

## 6. Контроллеры

- [ ] 6.1 Обновить `CallController`: интегрировать форму с динамическими полями результата
- [ ] 6.2 Добавить AJAX-эндпоинт для загрузки кампаний (выпадающий список при типе mailing/refusal_mailing)
- [ ] 6.3 Добавить проверку области доступа через организацию звонка для всех действий с результатом
- [ ] 6.4 Авто-запуск: при создании CallResult типа mailing/refusal_mailing, если Campaign.autoLaunch — проставить launchedAt (контракт с изменением campaign-entity)

## 7. Шаблоны Twig

- [ ] 7.1 Обновить шаблон формы звонка: выпадающий список типа результата и условные блоки полей
- [ ] 7.2 Обновить отображение звонка на дашборде: показывать тип результата вместо отдельных пометок

## 8. JavaScript — динамические поля

- [ ] 8.1 Реализовать показ/скрытие полей формы при выборе типа результата (vanilla JS)
- [ ] 8.2 Реализовать AJAX-загрузку списка кампаний при выборе типа mailing/refusal_mailing
- [ ] 8.3 Добавить валидацию на клиенте: обязательные поля при определённых типах

## 9. Валидация

- [ ] 9.1 Серверная валидация: campaign обязателен при типе mailing/refusal_mailing
- [ ] 9.2 Серверная валидация: next_call_date обязателен при типе new_call/no_answer_new_call

## 10. Обновление ER-схемы

- [ ] 10.1 Обновить `openspec/design/er.md`: добавить CallResult, удалить is_deal/campaign_id/next_call_id с Call
