# AGENTS.md

## What this repo is
Documentation-only OpenSpec workspace. No application code, build system, tests, or CI exist.
Tracked by git (if `git` reports "dubious ownership", run
`git config --global --add safe.directory /workspace`).

## Source of truth
- `openspec/project.md` — видение, миссия, цели и карта возможностей продукта B2B Call CRM.
- `openspec/specs/<capability>/spec.md` — спецификации возможностей (RU-текст, fenced Gherkin).
- `adr/0001–0006` — архитектурные решения (own/custom группы, M2M членство,
  область доступа, фиксированные роли, OpenSpec как единственный источник истины).
- OpenSpec — единственный источник истины (ADR-0006); исходный PRD удалён.

## Language rule
- Тело спецификаций и шаги Gherkin пишутся **на русском**; ключевые слова Gherkin —
  **английские** (`Feature`, `Rule`, `Scenario`, `Given/When/Then`).
- Нормативные глаголы: «ДОЛЖНА», «ДОЛЖЕН», «МОЖЕТ».
- Сохраняйте русский при редактировании содержимого, унаследованного из продуктовой документации.

## Domain model constraints (hard, ADR-0001–0006)
Accredited without asking the user; keep consistent:
- Managers have an **own group** (auto-created, own orgs land there) and can be assigned **custom groups**.
- Org ↔ group is **many-to-many** (`OrganizationGroupMembership`); one group can be assigned to many managers (`GroupAssignment`).
- Managers get **full access** to orgs in own + all assigned groups.
- **Admin sees everything**, manages groups and assignments.
- **Operator** accesses only through assigned groups.
- Do not re-introduce per-org ACL tiers.

## Common task traps
- Any edit touching access/roles must match the model above.
- `ContactGroup` (contacts) vs `OrganizationGroup` (organizations) — keep naming consistent.
- `openspec` CLI 1.8.0 is installed. Caveat: `openspec validate` uses the default
  spec-driven format and does not validate the intent-driven fenced-Gherkin specs;
  use the acceptance-test-authoring extractor for structural checks.
- git repo exists (add `safe.directory` exception if needed).

## OpenSpec workflow
- intent-driven schema: proposal → specs → design → adr → tasks.
- For OpenSpec propose/apply/verify/archive workflows, use the local
  `openspec-git-discipline` skill to enforce proposal commits before apply and
  merge-before-archive discipline.