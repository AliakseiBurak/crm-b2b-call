# OpenSpec Project Agent Guidelines

Project-specific conventions for agents working in this OpenSpec workspace.

## Language

- Spec bodies and Gherkin step text: **Russian**.
- Gherkin keywords: **English** (`Feature`, `Rule`, `Scenario`, `Given/When/Then`).
- Normative verbs in Russian: «ДОЛЖНА», «ДОЛЖЕН», «МОЖЕТ» (not English SHALL/MUST).
- If scenarios become executable acceptance tests, step definitions must match
  the Russian step text (Russian regexes or the `# language: ru` dialect).

## Access model (hard constraints)

Do not re-introduce per-org ACL tiers. See `adr/0001–0004`:

- ADR-0001: own group auto-created per manager; manager's orgs land there.
- ADR-0002: org ↔ group many-to-many (`OrganizationGroupMembership`); one group
  assignable to many managers (`GroupAssignment`).
- ADR-0003: manager gets full access to own + assigned groups; operator only
  through assigned groups.
- ADR-0004: admin sees everything, manages groups and assignments.

## Terminology

- `ContactGroup` (singular) — contacts grouping.
- `OrganizationGroup` — organizations grouping.
Do not rename inconsistently.

## Source of truth

- `docs/source/PRD.md` — исходный продукт-документ (русский), источник
  требований §1–§9. Preserve Russian when quoting.
- `docs/source/PRD_WORKFLOW.md` — generic PRD-writing guide, **not** product
  requirements. Do not treat as authoritative.

## Tooling caveats

- `openspec` CLI is **not installed** in this environment. Structural validation
  deferred until plugin/CLI available.
- No git repo: commits/branches fail; file operations only.

## Workflow

intent-driven schema: proposal → specs → design → adr → tasks. Skill gates in
`openspec/config.yaml` must be honored.