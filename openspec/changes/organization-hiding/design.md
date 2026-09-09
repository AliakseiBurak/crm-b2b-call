## Context

Current access implementation (see proposal.md — Why):

- `OrganizationRepository::findAccessibleIds(?User): ?array` is the single
  access gateway: `null` user or admin → `null` (unlimited); manager → org ids
  from created groups (`g.createdBy = user`) ∪ assigned groups
  (`GroupAssignment`). Every controller (Organization, Contact, Call,
  Campaign) enforces access through this method. There are no Symfony voters.
- Groups are managed by `GroupController`; assigned groups are shown to
  managers read-only; `CampaignRecipientService::bulkAddByGroup()` adds ALL
  organizations of a group as campaign recipients without org-level check.
- Stack: Symfony 7.x, PHP 8.5, Doctrine ORM 3.x, MySQL, Twig.

This design replaces group-based access with a default-open deny-list.

## Goals / Non-Goals

**Goals:**
- Add `OrganizationHide` entity/table and admin-only hide management.
- Rewrite the access gateway to exclude hidden organizations for managers.
- Make hiding effective in every read path (lists, dashboard, contacts,
  calls, pseudo-groups, campaign recipients, group member views).
- Keep the change data-migration-free (default-open needs no backfill).

**Non-Goals:**
- Rewording group-based access phrasing in other capability specs
  (`organization-groups`, `contacts`, `dashboard`, …) — follow-up change.
- Hide history / audit log, expiration, or per-org ACL tiers.
- Any manager-facing hide UI (managers only feel the effect).
- Group-level bulk hide operations.

## Decisions

### 1. Deny-list table `organization_hide`

**Decision**: new entity `OrganizationHide`:

- `id` (UUID)
- `organization` (ManyToOne → Organization, NOT NULL, ON DELETE CASCADE)
- `manager` (ManyToOne → User, NOT NULL, ON DELETE CASCADE)
- `hiddenAt` (DateTimeImmutable, NOT NULL)
- `UNIQUE (organization_id, manager_id)`, indexes on `manager_id`,
  `organization_id`

No `grantedBy`, no `expiresAt` (deliberately removed for simplicity).

**Rationale**: default-open inverts the previous allow-list; a hide record
denies one pair. Per-manager rows keep the query uniform (Option C from the
exploration) and «скрыть от всех» is simply N rows for current managers.

**Alternatives considered**:
- Allow-list `organization_visibility` (previous plan) → rejected by product:
  onboarding a manager would require seeding grants.
- Org-level boolean `hidden_from_managers` → rejected: no per-manager
  granularity; splits the model into two places.
- Nullable `manager_id` for "all managers" → rejected: MySQL unique index
  permits duplicate NULLs; user chose uniform non-null rows.

### 2. Access gateway: NOT IN subquery

**Decision**: rewrite `OrganizationRepository::findAccessibleIds()`:

```php
if (null === $user || UserRole::Admin === $user->role) {
    return null; // unlimited
}

return $this->createQueryBuilder('o')
    ->select('o.id')
    ->where('o NOT IN (
        SELECT h.organization FROM App\Entity\OrganizationHide h
        WHERE h.manager = :user
    )')
    ->setParameter('user', $user)
    ->getQuery()
    ->getScalarResult();
```

The `null`-for-admin contract is preserved, so all existing controller checks
keep working unchanged. Group joins are removed from the gateway.

**Alternatives considered**:
- Symfony voter → rejected: no voters exist; would touch every controller.
- Dedicated access service with caching → rejected: single indexed subquery
  is cheap; cache invalidation complexity unnecessary.

### 3. Hide service

**Decision**: `OrganizationHideService` with idempotent operations and shared validation:

- `validateHideTargets(array $managerIds): string[]` — checks that all IDs are
  managers; returns invalid IDs for error reporting. Reused by both the
  registry controller and any future hide endpoint.
- `findDuplicateTargets(Organization $org, array $managers): User[]` — returns
  managers already hidden from the org (for conflict reporting). Reused by
  both controllers.
- `hide(Organization $org, array $managers): int` — create missing rows,
  skip existing pairs, return created count.
- `hideFromAllManagers(Organization $org): int` — one row per current
  manager (role=manager), skip existing.
- `unhide(Organization $org, User $manager): void` — delete the row.

The UNIQUE constraint is the final guard; the service pre-checks to avoid
constraint-violation exceptions. Validation and duplicate-check logic live in
the service, not in individual controllers, to keep behavior consistent across
the registry and org-card entry points.

### 4. Admin UI

**Decision**: two entry points, both ROLE_ADMIN:

- Registry section «Скрытые организации» (`/admin/hides`): list of hide
  records (organization, manager, hidden at) with «Показать» per row
  (unhide one manager); inline hide form with organization select and
  manager select (single-select: specific manager or "— скрыть от всех
  менеджеров —" default option).
- Organization card edit page (`/organizations/{id}/edit`): admin-only
  «Скрыто от менеджеров» section with existing hides and unhide buttons;
  hide functionality lives in the registry, not the card.

Routes under `/admin/hides` (list, new, delete). Navigation entry «Скрытые
организации» in the admin section. The unhide action within the org edit
form is protected by `isGranted('ROLE_ADMIN')` check inside `update()`.

### 5. Read-path filtering

- `GroupController::members()`: both branches (editable and read-only
  assigned-group view) filter displayed organizations by
  `findAccessibleIds()` for managers; admin sees everything.
- `CampaignRecipientService::bulkAddByGroup()`: skip organizations absent
  from `findAccessibleIds()` for the acting user.
- Campaign recipient list rendering: hide recipient rows of hidden
  organizations for managers.
- Call lists and pseudo-groups («Звонки на сегодня», «Будущие звонки»):
  verify queries already restrict by `findAccessibleIds()`; fix any path
  that does not.
- Dashboard stats: already filtered via `findForDashboard()` →
  `findAccessibleIds()`; no change expected, covered by tests.

### 6. Migration

Single Doctrine migration creating `organization_hide` with FKs
`ON DELETE CASCADE` on both sides, UNIQUE(organization_id, manager_id),
indexes on `manager_id` and `organization_id`. No data backfill
(default-open). Rollback: drop table + revert repository method.

## C4 (component view, lightweight)

```
+---------------------------------------------------------------+
|                        B2B Call CRM                           |
|  +---------------------+        +--------------------------+ |
|  | Admin UI            |        | Manager UI               | |
|  | «Скрытые org»       |        | lists, dashboard, calls, | |
|  |  registry + org     |        | campaigns, groups        | |
|  |  card action        |        +--------------------------+ |
|  +----------+----------+                   |                  |
|             | ROLE_ADMIN                   | ROLE_MANAGER     |
|             v                              v                  |
|  +-----------------------------------------------+           |
|  | OrganizationHideService (validate/       |           |
|  |  hide/hideAll/unhide)                    |           |
|  +--------------------+--------------------------+           |
|                       |                                      |
|             +---------+---------+                            |
|             v                   v                            |
|  +--------------------+  +-----------------------------+     |
|  | OrganizationRepo.  |  | Group/Campaign/Call paths   |     |
|  | findAccessibleIds()|  | (bulk-add, members, lists,  |     |
|  | NOT IN hide rows   |  |  pseudo-groups)             |     |
|  +---------+----------+  +--------------+--------------+     |
|            |                           |                     |
|            v                           v                     |
|  +-----------------------------------------------+           |
|  |  MySQL: organization_hide (UNIQUE org+manager) |           |
|  |  FK CASCADE -> organization, user              |           |
|  +-----------------------------------------------+           |
+---------------------------------------------------------------+
```

- Boundary: hide management is admin-only; managers only consume the filtered
  data through the existing access gateway.
- Assumption: all manager read paths funnel through
  `findAccessibleIds()`; paths that don't are fixed in this change.
- Open question: none — call-path verification is a task, not a design unknown.

## Risks / Trade-offs

[Risk] Hide rows become a "shadow blacklist" bypassing group discipline.
→ Mitigation: admin-only management, visible registry, ADR-0012 documents
  intent (exceptions, repointing work), no per-org ACL tiers introduced.

[Risk] «Скрыть от всех» creates N rows that can go stale.
→ Mitigation: rows cascade on manager deletion; per-record unhide via the
  registry; new managers are intentionally unaffected (product decision).

[Risk] NOT IN subquery cost grows with many organizations.
→ Mitigation: index on `manager_id`; per-manager row sets are small in
  practice; revisit only if profiling shows regressions.

[Risk] Race between two admins hiding the same pair.
→ Mitigation: DB UNIQUE constraint; service pre-check keeps the common case
  clean, constraint violation maps to a conflict error.

## Migration Plan

1. Migration `organization_hide` (no data changes).
2. Entity + repository + service + unit tests.
3. Rewrite `findAccessibleIds()`; regression-test admin/manager flows.
4. Admin UI (registry + org-card action) + functional tests.
5. Read-path filtering fixes + tests (group members, campaign bulk-add,
   recipient list, call pseudo-groups).
6. ADR-0012 + `project.md` capability entry.

**Rollback**: drop `organization_hide`, revert `findAccessibleIds()` to the
group-based query.

## Open Questions

None — all decisions were resolved during exploration with the product owner.
