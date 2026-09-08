## Context

Current state (see proposal.md for motivation):
- Existing `organization-groups` capability with personal groups (`user-<id>-group`), custom groups, many-to-many membership (`OrgGroupMembership`), and group assignments (`GroupAssignment`).
- Existing `campaigns` capability with recipient management, bulk "add all accessible organizations", and per-letter status tracking.
- ADR-0005: Manager personal groups auto-created, orgs land there.
- ADR-0006: Org<->group many-to-many via `OrgGroupMembership`.
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
**Decision**: Add `created_by` (INT NULL, FK → users) to `organization_group`. Manager sees groups WHERE `created_by = self` OR assigned via `GroupAssignment`. Manager can edit/delete only groups where `created_by = self`. Admin sees all, manages all.

**Rationale**:
- Clean ownership model: creator controls the group.
- Reuses existing `GroupAssignment` table for admin-to-manager sharing.
- No new flags or columns needed.

**Alternatives considered**:
- Separate `is_shared` flag -> Rejected: `GroupAssignment` already handles visibility.
- Manager sees only own groups (no assignment) -> Rejected: admin needs to share groups to new managers.

### 3. Manager Group CRUD
**Decision**: Managers can create, edit, delete custom groups, and manage group membership (add/remove orgs). New menu item "Мои группы" for group management. Org edit page gains group checkboxes. Groups assigned to a manager (not created by them) are read-only: the list shows no "Редактировать" link (label "только просмотр"), the members page shows the composition without the edit form, and the membership POST returns 403.

**Rationale**:
- Groups become a first-class tool for managers to organize their orgs.
- Checkbox model allows org to belong to multiple groups (many-to-many).
- Visibility rules: created_by = self OR assigned; write rules: created_by = self (or admin).
- Assigned groups must be inspectable so a manager understands the scope they got.

### 4. Manager Deletion Flow
**Decision**: When admin deletes a manager, the system shows a warning listing all groups created by that manager together with the organization count and the managers the group is assigned to. Admin MUST choose per-group: "Reassign to Admin" (changes `created_by` to admin, keeps group and assignments) or "Delete group" (removes group, membership, and assignments; organizations themselves are never deleted). Personal groups are already eliminated by the migration, so there is nothing to remove silently.

**Rationale**:
- Prevents accidental data loss (groups with active assignments).
- Per-group choice gives admin full control.
- Personal group removal is automatic (it's being eliminated).

### 5. Org Creation Group Checkboxes
**Decision**: When creating an org, manager sees checkboxes for available groups (created + assigned). Admin sees all groups. Org is added to checked groups. If none checked, org exists ungrouped.

**Rationale**:
- Aligns with many-to-many model (org can be in multiple groups).
- Allows immediate organization during creation.
- No new schema needed — uses existing `OrgGroupMembership`.

### 6. Campaign Recipient Group-Based Bulk Add
**Decision**: New web endpoint `POST /campaigns/{id}/recipients/bulk-by-group` accepting the `group_id` form field (with CSRF token). AJAX requests (`X-Requested-With: XMLHttpRequest`) receive JSON `{ added, skipped, no_email }`; regular form posts get a redirect + flash message. Unauthorized group access results in a 403 response. Service:
1. Verify manager has access to group (created or assigned).
2. Query organizations in group via `OrgGroupMembership`.
3. Create `CampaignRecipient` entries for each, skipping existing recipients (unique constraint)
   and organizations without a deliverable e-mail among their contacts (campaigns rule
   "Организация без e-mail не может стать адресатом" applies to every creation path).
4. Return counts of added/skipped plus the `no_email` breakdown.

The flash message is `Добавлено: N, пропущено: M`, extended with `(в том числе нет e-mail: K)`
only when `K > 0`; a group without organizations produces a dedicated notice instead.

**Rationale**:
- Dedicated endpoint keeps controller clean.
- Reuses existing `CampaignRecipientService` logic.
- Access check at group level, not per-org (performance).
- The same e-mail rule everywhere prevents recipients that cannot receive a letter.

### 7. Group Metadata Fields
**Decision**: Add `description` (text, nullable) and `color` (hex string, VARCHAR(7) nullable, e.g. `#ff0000`) to `OrganizationGroup` entity. Managed via group CRUD (both admin and manager pages use the same `GroupController` form). Surfaced in the manager group list UI and as group buttons on the campaign recipients page (name as label, color as button style, description as `title` tooltip).

**Rationale**:
- Minimal schema change.
- Supports UI differentiation in group selection dropdowns.
- No migration complexity (nullable columns).

**Alternatives considered**:
- Separate metadata table -> Rejected: overkill for two simple fields.
- JSON column -> Rejected: less queryable, no schema validation.

## Risks / Trade-offs

[Risk] Group metadata (color) needs validation (hex format).
-> Mitigation: Validate with `#[Assert\Regex]` on the entity (`^#[0-9a-fA-F]{6}$`), enforced via the validator in group CRUD, default to null.

[Risk] Performance of bulk add for large groups.
-> Mitigation: Process in batches, return progress, or use async job for very large groups.

[Risk] Removing personal groups may break existing org-to-group mappings.
-> Mitigation: Migration removes personal groups; orgs become ungrouped. Admin/manager can re-add to custom groups.

[Risk] Manager deletion reassign flow may cause confusion if admin doesn't understand the choice.
-> Mitigation: UI shows group name, organization count, assigned managers, and the note that
   deleting a group removes memberships only, never organizations
   (`templates/user/delete.html.twig`, same clarification on the group delete page).

## Migration Plan

1. **Database migration**:
   - Add `description` (TEXT NULL), `color` (VARCHAR(7) NULL), `created_by` (INT NULL, FK → users) to `organization_group`.
   - Remove personal groups: delete rows with legacy `type = 'user'` (the `user-<id>-group` rows). Their `OrgGroupMembership` and `GroupAssignment` rows go away via FK `ON DELETE CASCADE`.

2. **Entity/Repository**: Add `description`, `color`, `createdBy` fields to `OrganizationGroup` entity. Update `OrganizationGroupRepository` with ownership queries.

3. **Manager Group CRUD**: New controller/pages for group management (list, create, edit, delete). Org edit page gains group checkboxes.

4. **Manager Deletion**: Update delete confirmation to show per-group reassign/delete choice.

5. **Campaign Recipients**:
   - Add bulk-by-group web endpoint.
   - Add per-group "Добавить по группе" buttons to the campaign recipients Twig template (group name label, color as button style, description tooltip).

6. **Tests**: Unit tests for repository; functional tests for endpoints; integration tests for manager deletion flow.

**Rollback**: Remove new columns, restore personal groups from backup, remove new endpoints/pages.
