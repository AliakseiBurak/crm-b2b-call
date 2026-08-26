## 1. Домен и контроллер

- [ ] 1.1 Снять `#[Assert\NotNull]` c `Call::$scheduledAt` в `src/Entity/Call.php`
- [ ] 1.2 Проверить `CallController::applyRequest`: пустая дата → null без ошибки; правило «не в прошлом» только для непустых значений

## 2. Шаблон формы

- [ ] 2.1 Убрать нативный `required` у `#scheduled_at` в `templates/call/form.html.twig`
- [ ] 2.2 Восстановление введённых значений в create-режиме: `scheduled_at`, `made_at`, `contact`, `notes` читаются из `call` без guard'а `isEdit`

## 3. Тесты

- [ ] 3.1 Переписать `testCreateWithBlankScheduledAtShowsRussianErrorAndDoesNotSave`: пустая дата + фактическая → успешное создание; пустая дата без обеих дат → создание допустимо
- [ ] 3.2 Переписать `testEditWithClearedScheduledAtShowsRussianErrorAndKeepsValue`: очистка даты сохраняет звонок с null
- [ ] 3.3 Добавить тест: при ошибке (дата в прошлом) форма 422 восстанавливает введённые значения
- [ ] 3.4 Обновить e2e `calls-crud.spec.ts`: сценарий создания с только фактической датой через UI
- [ ] 3.5 Прогнать функциональные тесты и полный e2e-набор

## 4. Верификация

- [ ] 4.1 `openspec validate call-scheduled-date-optional --type change`
- [ ] 4.2 Ручная проверка в браузере: создание проведённого звонка с org-scoped страницы без запланированной даты
