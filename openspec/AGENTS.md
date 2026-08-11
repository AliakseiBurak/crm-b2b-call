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

Do not re-introduce per-org ACL tiers. See `adr/0001–0004`:

- ADR-0001: own group auto-created per manager; manager's orgs land there.
- ADR-0002: org ↔ group many-to-many (`OrganizationGroupMembership`); one group
  assignable to many managers (`GroupAssignment`).
- ADR-0003: manager gets full access to own + assigned groups.
- ADR-0004: admin sees everything, manages groups and assignments.

## Terminology

- `ContactGroup` (singular) — contacts grouping.
- `OrganizationGroup` — organizations grouping.
Do not rename inconsistently.

## Source of truth

- OpenSpec — единственный источник истины (ADR-0006). Исходный PRD удалён.
- `openspec/specs/` — спецификации возможностей; `adr/` — архитектурные решения.
- Пробелы, сознательно отложенные (управление пользователями, NFR, CSRF), —
  в реестре ADR-0006, реализуются будущими изменениями.

## Tooling caveats

- `openspec` CLI 1.8.0 is installed. Specs use the spec-driven format;
  `openspec validate <capability> --type spec` works out of the box.
- git repo exists (add `safe.directory` exception if needed).

## Workflow

spec-driven schema: proposal → specs → design → tasks. Skill gates in
`openspec/config.yaml` must be honored.