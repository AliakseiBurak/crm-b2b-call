# AGENTS.md

## What this repo is
Documentation-only OpenSpec workspace. No application code, build system, tests, or CI exist.
Untracked by git (`git` commands fail; do not rely on `git status`).

## Source of truth
- `openspec/project.md` — видение, миссия, цели и карта возможностей продукта B2B Call CRM.
- `openspec/specs/<capability>/spec.md` — спецификации возможностей (RU-текст, fenced Gherkin).
- `adr/0001–0004` — архитектурные решения по модели доступа (own/custom группы, M2M членство, область доступа).
- `docs/source/PRD.md` — исходный продукт-документ (русский), источник требований §1–§9.
- `docs/source/PRD_WORKFLOW.md` — generic PRD-writing guide, **not** product requirements.

## Language rule
- Тело спецификаций и шаги Gherkin пишутся **на русском**; ключевые слова Gherkin —
  **английские** (`Feature`, `Rule`, `Scenario`, `Given/When/Then`).
- Нормативные глаголы: «ДОЛЖНА», «ДОЛЖЕН», «МОЖЕТ».
- Сохраняйте русский при редактировании содержимого, унаследованного от PRD.

## Domain model constraints (hard, from PRD §2.9–2.12, §3.7; ADR-0001–0004)
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
- `openspec` CLI is **not installed**; structural validation of specs deferred until tooling available.
- No git repo: no commits/branches.

## OpenSpec workflow
- intent-driven schema: proposal → specs → design → adr → tasks.
- For OpenSpec propose/apply/verify/archive workflows, use the local
  `openspec-git-discipline` skill to enforce proposal commits before apply and
  merge-before-archive discipline (once git is available).