# Tasks: reconcile-specs-with-prd

## 1. Конфигурация и проверка

- [ ] 1.1 Зафиксировать `stack:` в `openspec/config.yaml` (javascript или python) для будущего acceptance-test ранера; стек не угадывать, при необходимости уточнить у пользователя.
- [ ] 1.2 Выполнить `openspec validate reconcile-specs-with-prd --type change --strict` и устранить найденные проблемы.

## 2. Артефакты решений

- [ ] 2.1 Подтвердить, что `adr/0005-roles-fixed-enum.md` существует и отражает решение D2 из design.md (фиксированный набор ролей, без Roles CRUD).
- [ ] 2.2 Подтвердить, что манифест `adr.md` заполнен и перечисляет созданный ADR.

## 3. Дельта-спецификации

- [ ] 3.1 Проверить дельту `specs/access-control/spec.md`: MODIFIED-правило о фиксированном наборе ролей, сценарий отклонения создания роли.
- [ ] 3.2 Проверить дельту `specs/email-campaigns/spec.md`: MODIFIED-правило о рассылке в рамках организации и доставке по группе контактов.

## 4. Синхронизация и архивация

- [ ] 4.1 Синхронизировать дельты в основные спецификации (`openspec/specs/access-control/spec.md`, `openspec/specs/email-campaigns/spec.md`), сохранив остальные правила нетронутыми.
- [ ] 4.2 Заархивировать change `reconcile-specs-with-prd` после успешной проверки.
