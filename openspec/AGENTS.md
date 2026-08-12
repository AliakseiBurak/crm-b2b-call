# OpenSpec Project Agent Guidelines

Project-specific conventions for agents working in this OpenSpec workspace.

## Language

- Spec-driven format: `## Purpose`, `## Requirements` with `### Requirement` and
  `#### Scenario` blocks.
- Scenario names and step text (`- **WHEN**`/`- **THEN**`/`- **AND**`): **Russian**.
- Normative verbs in requirement text: **English** `SHALL`, `MUST`, `MAY`
  (required by `openspec validate`; Russian «ДОЛЖНА» is not recognized).
- If scenarios become executable acceptance tests, step definitions must match
  the Russian step text (Russian regexes or the `# language: ru` dialect).

## Access model (hard constraints)

Do not re-introduce per-org ACL tiers. See `adr/0005–0008`:

- ADR-0005: `user-<id>-group` auto-created per manager; manager's orgs land there.
- ADR-0006: org ↔ group many-to-many (`OrganizationGroupMembership`); one group
  assignable to many managers (`GroupAssignment`).
- ADR-0007: manager gets full access to own (`user-<id>-group`) + assigned groups.
- ADR-0008: admin sees everything, manages groups and assignments; admin has no
  personal group, groups are not checked for admin.

## Terminology

- `Contact` — contact entity bound to an organization.
- `OrganizationGroup` — organizations grouping.
Do not rename inconsistently.

## Source of truth

- OpenSpec — единственный источник истины. Исходный PRD удалён.
- `openspec/specs/` — спецификации возможностей; `adr/0000–0010` —
  архитектурные решения (инфраструктура, организация, контакты, модель
  взаимодействия/обзвон, группы `user-<id>-group`/custom, M2M членство,
  область доступа, фиксированные роли, e-mail/рассылки).

## Tooling caveats

- `openspec` CLI 1.8.0 is installed. Specs use the spec-driven format;
  `openspec validate <capability> --type spec` works out of the box.
- git repo exists (add `safe.directory` exception if needed).

## Workflow

spec-driven schema: proposal → specs → design → tasks. Skill gates in
`openspec/config.yaml` must be honored.