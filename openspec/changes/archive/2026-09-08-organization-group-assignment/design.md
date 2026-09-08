## Context

`GroupAssignment` entity (M2M: `User` ↔ `OrganizationGroup`) and `findForManager()` query already exist. The only gap is admin UI to create/modify `GroupAssignment` rows. No backend or schema changes required.

Two entry points serve two admin workflows:
- **Group-centric**: "I'm managing this group — who should have access?"
- **User-centric**: "I'm managing this manager — which groups should they see?"

Both operate on the same `group_assignment` table.

```
              GroupAssignment
             +--------------+
             | user_id  (FK)|
             | group_id (FK)|
             | assigned_at  |
             +--------------+
                   |
      +------------+------------+
      |                         |
 GROUP SIDE                USER SIDE
 /groups/{id}/assign       /admin/users/{id}/assign
```

## Goals / Non-Goals

**Goals:**
- Admin can assign/unassign managers to/from any group (group-side)
- Admin can assign/unassign groups to/from any manager (user-side)
- Checkbox state reflects current `GroupAssignment` records
- No new entities, migrations, or backend services

**Non-Goals:**
- Manager self-service assignment (admin-only, per decision)
- Bulk/matrix assignment UI (future enhancement if needed)
- Warnings on unassignment (organization-visibility will handle granular access)
- Read-only assignment visibility for managers

## Decisions

### D1: Two symmetric entry points, one relationship

**Decision**: Provide assignment UI from both group list and user list, operating on the same `GroupAssignment` table.

**Rationale**: Admins think in both directions — "who has this group?" and "what groups does this person have?" Two views reduce context-switching.

**Alternatives considered**:
- Group-side only — rejected: forces admin to navigate to group list for every assignment change
- User-side only — rejected: forces admin to navigate to user list for every assignment change
- Matrix view (all groups × all managers on one page) — deferred: complex template, premature optimization

### D2: Diff-based save strategy

**Decision**: On save, compute the diff between current assignments and submitted checkboxes. Remove stale assignments, add new ones.

```
Current:  {1, 2, 3}     (user IDs with GroupAssignment)
Submitted: {2, 4}
Remove:    {1, 3}
Add:       {4}
```

**Rationale**: Matches existing pattern in `GroupController::updateMembers()` (org membership). Idempotent — resubmitting the same state is a no-op.

### D3: Group-side controller lives in GroupController

**Decision**: `GET/POST /groups/{id}/assign` goes in `GroupController` with a per-action `#[IsGranted('ROLE_ADMIN')]` guard (the class-level guard is `ROLE_MANAGER`).

**Rationale**: Route namespace matches the entity being managed. The per-action guard overrides the class default cleanly.

### D4: User-side controller lives in UserController

**Decision**: `GET/POST /admin/users/{id}/assign` goes in `UserController` (already has class-level `ROLE_ADMIN`).

**Rationale**: Existing admin user management namespace. No additional guard needed.

### D5: Only managers shown in group-side assignment

**Decision**: Group-side page lists only users with `role = Manager`. Admin cannot assign groups to admin (admin sees everything regardless).

**Rationale**: Admin has no `GroupAssignment` — admin access is global (ADR-0008). Showing admin in the checkbox list would be misleading.

### D6: Only managers get "Назначить" button in user list

**Decision**: User list shows "Назначить" button only for users with `role = Manager`.

**Rationale**: Assigning groups to admin is meaningless (admin sees all). Keeps UI clean.

## Risks / Trade-offs

- **Risk**: Admin accidentally removes all assignments from a manager, leaving them with no visible groups → **Mitigation**: This is intentional behavior; organization-visibility change will add direct grants as a separate mechanism. No guard needed now.
- **Risk**: Race condition if two admins edit assignments simultaneously → **Mitigation**: Last-write-wins is acceptable for this low-frequency admin operation. No optimistic locking needed.
- **Trade-off**: Two templates instead of one shared component → Acceptable: the two pages have different contexts (group name vs user name header, different checkbox labels) and a shared component would add indirection for minimal deduplication.

## Open Questions

*None — all design decisions are resolved.*
