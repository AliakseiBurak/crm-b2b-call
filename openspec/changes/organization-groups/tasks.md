## 1. Database & Entity Layer

- [x] 1.1 Create Doctrine migration to add `description` (TEXT NULL), `color` (VARCHAR(7) NULL), `created_by` (INT NULL, FK → users) columns to `organization_groups` table
- [x] 1.2 Remove personal groups (`user-<id>-group`): delete matching rows and cascade remove `OrganizationGroupMembership` and `GroupAssignment` rows
- [x] 1.3 Add `description`, `color`, `createdBy` fields to `OrganizationGroup` entity with getters/setters
- [x] 1.4 Update `OrganizationGroupRepository` with ownership queries (created_by = self OR assigned)

## 2. Manager Group CRUD

- [x] 2.1 Create `GroupController` with list, create, edit, delete actions (ROLE_MANAGER)
- [x] 2.2 List page: show groups where created_by = self OR assigned, with name/description/color
- [x] 2.3 Create page: form with name, description, color (hex picker). Auto-set created_by to current manager
- [x] 2.4 Edit page: form with name, description, color. Only accessible for created_by = self
- [x] 2.5 Delete action: remove group, cascade membership and assignments. Only accessible for created_by = self
- [x] 2.6 Group membership page: add/remove orgs from group (checkboxes or multi-select)
- [x] 2.7 Add "Мои группы" menu item for ROLE_MANAGER
- [x] 2.8 Functional tests: manager CRUD on own groups, 403 on other manager's groups

## 3. Admin Group Management Updates

- [x] 3.1 Update admin group list to show `created_by` column (who created the group)
- [x] 3.2 Update admin group form to include description and color fields
- [x] 3.3 Update admin group assignment to work with manager-owned groups
- [x] 3.4 Functional tests: admin manages all groups including manager-owned groups

## 4. Org Edit Group Checkboxes

- [x] 4.1 Add group checkboxes to org edit page (manager: created + assigned groups; admin: all groups)
- [x] 4.2 On org save, update `OrganizationGroupMembership` based on checked groups
- [x] 4.3 Functional tests: org group assignment via edit page

## 5. Manager Deletion Flow

- [x] 5.1 Update manager delete confirmation page to show list of groups created by the manager
- [x] 5.2 Add per-group reassign/delete choice (radio buttons)
- [x] 5.3 Reassign: change `created_by` to admin, keep group and assignments
- [x] 5.4 Delete: remove group, cascade membership and assignments
- [x] 5.5 Personal group always removed without choice (if any remain during migration)
- [x] 5.6 Functional tests: manager deletion with groups, reassign flow, delete flow

## 6. Campaign Recipients - Group-Based Bulk Add

- [x] 6.1 Add `bulkAddByGroup(int $campaignId, string $groupId)` method to `CampaignRecipientService`
- [x] 6.2 Implement access check: verify manager has access to group (created or assigned)
- [x] 6.3 Implement query: organizations in group via `OrganizationGroupMembership`
- [x] 6.4 Create `CampaignRecipient` entries for each organization, skip existing (catch UniqueConstraintViolationException or check first)
- [x] 6.5 Create `POST /campaigns/{id}/recipients/bulk-by-group` endpoint accepting `{ "groupId": "..." }`, returning `{ added: N, skipped: M }`
- [x] 6.6 Add "Добавить по группе" button and group select dropdown to campaign recipients Twig template (populated from group API)
- [x] 6.7 Write functional tests: bulk add by group, 403 for unauthorized group, skip existing, empty group

## 7. Integration & Regression Tests

- [x] 7.1 Test full flow: manager creates group -> adds orgs to group -> creates campaign -> adds recipients by group -> mailing sends
- [x] 7.2 Test manager deletion: admin deletes manager -> reassigns groups -> groups still accessible
- [x] 7.3 Test manager deletion: admin deletes manager -> deletes groups -> groups removed
- [x] 7.4 Test org creation: manager creates org with group checkboxes -> org in selected groups
- [x] 7.5 Run existing test suite to ensure no regressions in organization-groups, campaigns

## 8. Documentation

- [x] 8.1 Update `openspec/specs/organization-groups/spec.md` with ADDED/MODIFIED requirements from delta (after archive)
- [x] 8.2 Update `openspec/specs/campaigns/spec.md` with ADDED/MODIFIED requirements from delta (after archive)
