# [AGENTS.md](http://AGENTS.md)

## What this repo is

Documentation-only OpenSpec workspace. No application code, build system, tests, or CI exist. Tracked by git.

## Source of truth

- `openspec/project.md` — видение, миссия, цели и карта возможностей продукта B2B Call CRM.
- `openspec/specs/<capability>/spec.md` — спецификации возможностей (spec-driven:
`## Purpose`, `## Requirements` с `### Requirement` и `#### Scenario`).
- `adr/<adr>.md` — архитектурные решения (инфраструктура, организация, контакты, модель взаимодействия/обзвон, группы `user-<id>-group`/custom,
M2M членство, область доступа, фиксированные роли, e-mail/рассылки).
- `openspec/design/` — дизайн-артефакты (ER-схема БД, sequence-диаграммы),
сгенерированные из спек для верификации.
- OpenSpec — единственный источник истины.



## Language rule

- Сценарии (`#### Scenario`) и шаги (`- **WHEN**`/`- **THEN**`/`- **AND**`) пишутся
**на русском**; ключевые слова Gherkin — **английские** (`WHEN`, `THEN`, `AND`).
- Нормативные глаголы в тексте требований — **английские**: `SHALL`, `MUST`, `MAY`
(требование `openspec validate`; русские «ДОЛЖНА/ДОЛЖЕН» не распознаются
валидатором и дают warning).
- Сохраняйте русский при редактировании содержимого, унаследованного из продуктовой документации.



## Domain model constraints (hard, ADR-0003–0008)

Accredited without asking the user; keep consistent:

- Managers have a **personal group** (`user-<id>-group`, auto-created, own orgs
land there) and can be assigned **custom groups**. **Admin has no personal
group**; groups are not checked for admin.
- Org ↔ group is **many-to-many** (`OrganizationGroupMembership`); one group can be assigned to many managers (`GroupAssignment`).
- Managers get **full access** to orgs in own (`user-<id>-group`) + all assigned groups.
- **Admin sees everything**, manages groups and assignments.
- Do not re-introduce per-org ACL tiers.



## Common task traps

- Any edit touching access/roles must match the model above.
- A contact belongs to exactly one organization (`Contact` has no grouping
entity); only `OrganizationGroup` exists for grouping.
- `openspec` CLI 1.8.0 is installed. Specs use the spec-driven format;
`openspec validate <capability> --type spec` works out of the box.
- git repo exists (add `safe.directory` exception if needed).



## Git commit rules

- При создании коммитов указывай автором пользователя через `git config`,
  а себя добавляй только как `Co-authored-by:` в конце сообщения.
- Пример формата коммита:
  ```
  git commit --author="User Name <user@example.com>" -m "message

  Co-authored-by: opencode <opencode@opencode.ai>"
  ```

## OpenSpec workflow

- spec-driven schema: proposal → specs → design → tasks.
- For OpenSpec propose/apply/verify/archive workflows, use the local
`openspec-git-discipline` skill to enforce proposal commits before apply and
merge-before-archive discipline.

