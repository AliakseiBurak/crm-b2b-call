# Tasks: remove-prd-sources

## 1. Удаление исходных документов

- [x] 1.1 Выполнить `git rm docs/source/PRD.md docs/source/PRD_WORKFLOW.md`; удалить пустую директорию `docs/source/`.

## 2. Вычистка ссылок в спецификациях

- [x] 2.1 Удалить строку-источник «Источник: PRD.md §…» из `openspec/specs/organizations/spec.md`.
- [x] 2.2 То же для `openspec/specs/contacts/spec.md`.
- [x] 2.3 То же для `openspec/specs/contact-groups/spec.md`.
- [x] 2.4 То же для `openspec/specs/communication-templates/spec.md`.
- [x] 2.5 То же для `openspec/specs/courses/spec.md`.
- [x] 2.6 То же для `openspec/specs/calls/spec.md`.
- [x] 2.7 То же для `openspec/specs/email-campaigns/spec.md`.
- [x] 2.8 То же для `openspec/specs/integrations/spec.md`.
- [x] 2.9 То же для `openspec/specs/organization-groups/spec.md`.
- [x] 2.10 То же для `openspec/specs/access-control/spec.md`.

## 3. Мета-документы

- [x] 3.1 `openspec/project.md`: Принцип №1 → «OpenSpec — единственный источник истины»; Принцип №4 → трассируемость через ADR и перекрёстные ссылки; References — удалить записи о `docs/source/PRD.md`.
- [x] 3.2 `openspec/config.yaml`: удалить строку «Source docs are archived under docs/source/ (PRD.md, PRD_WORKFLOW.md)» из context.
- [x] 3.3 `AGENTS.md` (корень): удалить разделы «Source of truth» о PRD; исправить «openspec CLI не установлен» → «CLI 1.8.0 установлен»; «нет git-репозитория» → «git есть (нужен safe.directory)».
- [x] 3.4 `openspec/AGENTS.md`: удалить раздел «Source of truth» о PRD/PRD_WORKFLOW.

## 4. Проверка

- [x] 4.1 Grep по репозиторию: не осталось ссылок `PRD.md`, `docs/source/PRD`, `Источник: PRD` (допустимы только исторические упоминания в `adr/0005`, `adr/0006` и `openspec/changes/archive/`).
- [x] 4.2 Повторный извлекатель Gherkin (`extract-gherkin.cjs`) — все 10 спецификаций извлекаются без ошибок.
