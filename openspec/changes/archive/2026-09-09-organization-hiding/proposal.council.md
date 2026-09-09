# Council Notes: proposal

## Author Summary
Author (adversarial-author) produced a proposal inverting the access model to
default-open with a deny-list: managers see all organizations except rows in
`organization_hide`; groups become categorization only; admin-only hide
management (registry + org-card action); «Все менеджеры» = one row per current
manager; hiding respected in all read paths; BREAKING supersession of the
ADR-0007/0011 access-scope formula; spec rewording of other capabilities
deferred to a follow-up change.

## Reviewer Challenges
- Review round NOT executed: the `adversarial-reviewer` subagent is
  misconfigured (invalid API key) and fails on every invocation. Per the
  user's decision, the author draft was accepted as-is without a review round.

## Resolutions
- Accepted: none (no review feedback available).
- Fixed during transcription: typo "follow-up reward" → "follow-up reword".

## Remaining Risks
- No independent review of the proposal; the following were identified by the
  primary agent as typical reviewer concerns but not formally challenged:
  - Implementation details (table columns, FK, SQL approach) are somewhat
    specific for a proposal — acceptable, they anchor the specs/design.
  - The co-existence of `GroupAssignment` with a categorization-only groups
    model deserves explicit wording in the follow-up rewording change.
  - Unhide semantics and unique-pair conflict handling are covered in the
    capability spec, not in the proposal body.
