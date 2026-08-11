# AGENTS.md

## What this repo is
Documentation-only OpenSpec workspace. No application code, build system, tests, or CI exist.
Tracked by git (if `git` reports "dubious ownership", run
`git config --global --add safe.directory /workspace`).

## Source of truth
- `openspec/project.md` — видение, миссия, цели и карта возможностей продукта B2B Call CRM.
- `openspec/specs/<capability>/spec.md` — спецификации возможностей (spec-driven:
  `## Purpose`, `## Requirements` с `### Requirement` и `#### Scenario`).
- `adr/0001–0006` — архитектурные решения (own/custom группы, M2M членство,
  область доступа, фиксированные роли, OpenSpec как единственный источник истины).
- OpenSpec — единственный источник истины (ADR-0006); исходный PRD удалён.

## Language rule
- Сценарии (`#### Scenario`) и шаги (`- **WHEN**`/`- **THEN**`/`- **AND**`) пишутся
  **на русском**; ключевые слова Gherkin — **английские** (`WHEN`, `THEN`, `AND`).
- Нормативные глаголы в тексте требований — **английские**: `SHALL`, `MUST`, `MAY`
  (требование `openspec validate`; русские «ДОЛЖНА/ДОЛЖЕН» не распознаются
  валидатором и дают warning).
- Сохраняйте русский при редактировании содержимого, унаследованного из продуктовой документации.

## Domain model constraints (hard, ADR-0001–0006)
Accredited without asking the user; keep consistent:
- Managers have an **own group** (auto-created, own orgs land there) and can be assigned **custom groups**.
- Org ↔ group is **many-to-many** (`OrganizationGroupMembership`); one group can be assigned to many managers (`GroupAssignment`).
- Managers get **full access** to orgs in own + all assigned groups.
- **Admin sees everything**, manages groups and assignments.
- Do not re-introduce per-org ACL tiers.

## Common task traps
- Any edit touching access/roles must match the model above.
- `ContactGroup` (contacts) vs `OrganizationGroup` (organizations) — keep naming consistent.
- `openspec` CLI 1.8.0 is installed. Specs use the spec-driven format;
  `openspec validate <capability> --type spec` works out of the box.
- git repo exists (add `safe.directory` exception if needed).

## OpenSpec workflow
- spec-driven schema: proposal → specs → design → tasks.
- For OpenSpec propose/apply/verify/archive workflows, use the local
  `openspec-git-discipline` skill to enforce proposal commits before apply and
  merge-before-archive discipline.