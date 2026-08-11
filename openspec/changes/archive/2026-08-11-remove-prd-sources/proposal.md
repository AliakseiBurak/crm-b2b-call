# Proposal: remove-prd-sources

## Why

OpenSpec объявлен единственным источником истины (ADR-0006); спецификации
приведены в соответствие с PRD (change `reconcile-specs-with-prd`), пробелы —
в реестре отложенных решений. Двойной источник (PRD + OpenSpec) больше не нужен.
Остаётся физически удалить PRD и `PRD_WORKFLOW.md` и вычистить все ссылки на них,
чтобы репозиторий полностью опирался на OpenSpec.

## What Changes

- **BREAKING (документация): удаление** `docs/source/PRD.md` и
  `docs/source/PRD_WORKFLOW.md`; пустая директория `docs/source/` удаляется.
- **10 спецификаций**: удаляется строка-источник «Источник: PRD.md §…» в
  `openspec/specs/<capability>/spec.md` (поведение не меняется).
- **`openspec/project.md`**: Принцип №1 — источник истины OpenSpec (вместо PRD);
  Принцип №4 — трассируемость через ADR/спецификации (вместо «ссылки на разделы
  PRD»); раздел References — удаляются ссылки на `docs/source/PRD.md`.
- **`openspec/config.yaml`**: из context удаляется строка «Source docs are
  archived under docs/source/ (PRD.md, PRD_WORKFLOW.md)».
- **`AGENTS.md` (корень) и `openspec/AGENTS.md`**: удаляются разделы о PRD как
  источнике истины; исправляются устаревшие утверждения (CLI 1.8.0 установлен;
  git-репозиторий существует).

## Capabilities

### New Capabilities

Нет.

### Modified Capabilities

Нет. Поведение спецификаций не меняется (`skip_specs: true`); правки — только
проза вне Gherkin-фесов.

## Impact

- Удаляемые файлы: `docs/source/PRD.md`, `docs/source/PRD_WORKFLOW.md` (в git —
  через `git rm`).
- Правки: 10 spec-заголовков, `project.md`, `config.yaml`, два `AGENTS.md`.
- Код, API, зависимости не затрагиваются.
