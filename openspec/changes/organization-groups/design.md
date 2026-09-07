## Context

Current state (see proposal.md for motivation):
- Existing `organization-groups` capability with personal groups (`user-<id>-group`), custom groups, many-to-many membership (`OrganizationGroupMembership`), and group assignments (`GroupAssignment`).
- Existing `campaigns` capability with recipient management, bulk "add all accessible organizations", and per-letter status tracking.
- ADR-0005: Manager personal groups auto-created, orgs land there.
- ADR-0006: Org<->group many-to-many via `OrganizationGroupMembership`.
- ADR-0007: Manager access = own group + assigned custom groups.
- ADR-0008: Admin sees everything, no personal group, groups not checked.

Stack: Symfony 7.x, PHP 8.5, Doctrine ORM 3.x, MySQL, Twig.

## Goals / Non-Goals

**Goals:**
- Remove personal groups (`user-<id>-group`) entirely.
- Add `created_by` field to `OrganizationGroup` so managers own their custom groups.
- Managers get full CRUD on own groups (create, edit, delete, manage membership).
- Admin retains full control over all groups and manages group assignments.
- Org creation page gains group checkboxes (manager's available groups; admin sees all).
- Campaign recipient bulk-add-by-group endpoint and UI.
- Manager deletion flow shows per-group reassign/delete choice.

**Non-Goals:**
- Per-organization ACL tiers (explicitly prohibited by ADR-0005-0008).
- Direct organization-to-user visibility mappings (handled in separate `organization-visibility` change).
- Automatic expiration cleanup job (not applicable).
- Notification system for group changes.
- Changing "Add all" button behavior in campaigns (keep existing logic with custom groups).

## Decisions

### 1. Personal Groups Removed
**Decision**: Remove all personal groups (`user-<id>-group`). Orgs previously in personal groups become ungrouped. Personal group removal is mentioned in manager deletion confirmation. No new `type` column is introduced — the migration identifies personal groups by the existing `type = 'user'` value in the legacy schema and drops the column afterwards.

**Rationale**:
- Personal groups are an implementation detail that leaks into the UI.
- Org visibility for managers is handled by the separate `organization-visibility` change.
- Simplifies the group model: groups are either admin-created or manager-created custom groups.

**Alternatives considered**:
- Hide personal groups from managers only -> Rejected: still maintenance overhead, confusing for admin.
- Add a new `type` column to distinguish -> Rejected: the legacy `type` column already marks them, so no new field is needed.

### 2. Group Ownership via `created_by`
**Decision**: Add `created_by` (INT NULL, FK → users) to `organization_groups`. Manager sees groups WHERE `created_by = self` OR assigned via `GroupAssignment`. Manager can edit/delete only groups where `created_by = self`. Admin sees all, manages all.

**Rationale**:
- Clean ownership model: creator controls the group.
- Reuses existing `GroupAssignment` table for admin-to-manager sharing.
- No new flags or columns needed.

**Alternatives considered**:
- Separate `is_shared` flag -> Rejected: `GroupAssignment` already handles visibility.
- Manager sees only own groups (no assignment) -> Rejected: admin needs to share groups to new managers.

### 3. Manager Group CRUD
**Decision**: Managers can create, edit, delete custom groups, and manage group membership (add/remove orgs). New menu item "Мои группы" for group management. Org edit page gains group checkboxes.

**Rationale**:
- Groups become a first-class tool for managers to organize their orgs.
- Checkbox model allows org to belong to multiple groups (many-to-many).
- Same visibility rules apply everywhere: created_by = self OR assigned.

### 4. Manager Deletion Flow
**Decision**: When admin deletes a manager, the system shows a warning listing all groups created by that manager. Admin MUST choose per-group: "Reassign to Admin" (changes `created_by` to admin, keeps group and assignments) or "Delete group" (removes group, membership, and assignments). Personal group (`user-<id>-group`) is always removed without choice.

**Rationale**:
- Prevents accidental data loss (groups with active assignments).
- Per-group choice gives admin full control.
- Personal group removal is automatic (it's being eliminated).

### 5. Org Creation Group Checkboxes
**Decision**: When creating an org, manager sees checkboxes for available groups (created + assigned). Admin sees all groups. Org is added to checked groups. If none checked, org exists ungrouped.

**Rationale**:
- Aligns with many-to-many model (org can be in multiple groups).
- Allows immediate organization during creation.
- No new schema needed — uses existing `OrganizationGroupMembership`.

### 6. Campaign Recipient Group-Based Bulk Add
**Decision**: New endpoint `POST /api/campaigns/{id}/recipients/bulk-by-group` accepting `{ groupId }`. Service:
1. Verify manager has access to group (created or assigned).
2. Query organizations in group via `OrganizationGroupMembership`.
3. Create `CampaignRecipient` entries for each, skipping existing (unique constraint).
4. Return count of added/skipped.

**Rationale**:
- Dedicated endpoint keeps controller clean.
- Reuses existing `CampaignRecipientService` logic.
- Access check at group level, not per-org (performance).

### 7. Group Metadata Fields
**Decision**: Add `description` (text, nullable) and `color` (hex string, VARCHAR(7) nullable, e.g. `#ff0000`) to `OrganizationGroup` entity. Admin-managed via existing group CRUD. Exposed in manager group listing API.

**Rationale**:
- Minimal schema change.
- Supports UI differentiation in group selection dropdowns.
- No migration complexity (nullable columns).

**Alternatives considered**:
- Separate metadata table -> Rejected: overkill for two simple fields.
- JSON column -> Rejected: less queryable, no schema validation.

## Risks / Trade-offs

[Risk] Group metadata (color) needs validation (hex format).
-> Mitigation: Validate on entity (regex `^#[0-9a-fA-F]{6}$`), default to null.

[Risk] Performance of bulk add for large groups.
-> Mitigation: Process in batches, return progress, or use async job for very large groups.

[Risk] Removing personal groups may break existing org-to-group mappings.
-> Mitigation: Migration removes personal groups; orgs become ungrouped. Admin/manager can re-add to custom groups.

[Risk] Manager deletion reassign flow may cause confusion if admin doesn't understand the choice.
-> Mitigation: Clear UI with group details (name, org count, assigned managers) per-group.

## Migration Plan

1. **Database migration**:
   - Add `description` (TEXT NULL), `color` (VARCHAR(7) NULL), `created_by` (INT NULL, FK → users) to `organization_groups`.
   - Remove personal groups (`user-<id>-group`): delete rows where name matches `user-%-group` pattern. Remove associated `OrganizationGroupMembership` and `GroupAssignment` rows.

2. **Entity/Repository**: Add `description`, `color`, `createdBy` fields to `OrganizationGroup` entity. Update `OrganizationGroupRepository` with ownership queries.

3. **Manager Group CRUD**: New controller/pages for group management (list, create, edit, delete). Org edit page gains group checkboxes.

4. **Manager Deletion**: Update delete confirmation to show per-group reassign/delete choice.

5. **Campaign Recipients**:
   - Add bulk-by-group endpoint.
   - Add "Add by group" dropdown to recipients Twig template.

6. **Tests**: Unit tests for repository; functional tests for endpoints; integration tests for manager deletion flow.

**Rollback**: Remove new columns, restore personal groups from backup, remove new endpoints/pages.
