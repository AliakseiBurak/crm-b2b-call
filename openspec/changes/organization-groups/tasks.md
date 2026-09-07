## 1. Database & Entity Layer

- [ ] 1.1 Create Doctrine migration to add `description` (TEXT NULL), `color` (VARCHAR(7) NULL), `created_by` (INT NULL, FK → users) columns to `organization_groups` table
- [ ] 1.2 Remove personal groups (`user-<id>-group`): delete matching rows and cascade remove `OrganizationGroupMembership` and `GroupAssignment` rows
- [ ] 1.3 Add `description`, `color`, `createdBy` fields to `OrganizationGroup` entity with getters/setters
- [ ] 1.4 Update `OrganizationGroupRepository` with ownership queries (created_by = self OR assigned)

## 2. Manager Group CRUD

- [ ] 2.1 Create `GroupController` with list, create, edit, delete actions (ROLE_MANAGER)
- [ ] 2.2 List page: show groups where created_by = self OR assigned, with name/description/color
- [ ] 2.3 Create page: form with name, description, color (hex picker). Auto-set created_by to current manager
- [ ] 2.4 Edit page: form with name, description, color. Only accessible for created_by = self
- [ ] 2.5 Delete action: remove group, cascade membership and assignments. Only accessible for created_by = self
- [ ] 2.6 Group membership page: add/remove orgs from group (checkboxes or multi-select)
- [ ] 2.7 Add "Мои группы" menu item for ROLE_MANAGER
- [ ] 2.8 Functional tests: manager CRUD on own groups, 403 on other manager's groups

## 3. Admin Group Management Updates

- [ ] 3.1 Update admin group list to show `created_by` column (who created the group)
- [ ] 3.2 Update admin group form to include description and color fields
- [ ] 3.3 Update admin group assignment to work with manager-owned groups
- [ ] 3.4 Functional tests: admin manages all groups including manager-owned groups

## 4. Org Edit Group Checkboxes

- [ ] 4.1 Add group checkboxes to org edit page (manager: created + assigned groups; admin: all groups)
- [ ] 4.2 On org save, update `OrganizationGroupMembership` based on checked groups
- [ ] 4.3 Functional tests: org group assignment via edit page

## 5. Manager Deletion Flow

- [ ] 5.1 Update manager delete confirmation page to show list of groups created by the manager
- [ ] 5.2 Add per-group reassign/delete choice (radio buttons)
- [ ] 5.3 Reassign: change `created_by` to admin, keep group and assignments
- [ ] 5.4 Delete: remove group, cascade membership and assignments
- [ ] 5.5 Personal group always removed without choice (if any remain during migration)
- [ ] 5.6 Functional tests: manager deletion with groups, reassign flow, delete flow

## 6. Campaign Recipients - Group-Based Bulk Add

- [ ] 6.1 Add `bulkAddByGroup(int $campaignId, string $groupId)` method to `CampaignRecipientService`
- [ ] 6.2 Implement access check: verify manager has access to group (created or assigned)
- [ ] 6.3 Implement query: organizations in group via `OrganizationGroupMembership`
- [ ] 6.4 Create `CampaignRecipient` entries for each organization, skip existing (catch UniqueConstraintViolationException or check first)
- [ ] 6.5 Create `POST /campaigns/{id}/recipients/bulk-by-group` endpoint accepting `{ "groupId": "..." }`, returning `{ added: N, skipped: M }`
- [ ] 6.6 Add "Добавить по группе" button and group select dropdown to campaign recipients Twig template (populated from group API)
- [ ] 6.7 Write functional tests: bulk add by group, 403 for unauthorized group, skip existing, empty group

## 7. Integration & Regression Tests

- [ ] 7.1 Test full flow: manager creates group -> adds orgs to group -> creates campaign -> adds recipients by group -> mailing sends
- [ ] 7.2 Test manager deletion: admin deletes manager -> reassigns groups -> groups still accessible
- [ ] 7.3 Test manager deletion: admin deletes manager -> deletes groups -> groups removed
- [ ] 7.4 Test org creation: manager creates org with group checkboxes -> org in selected groups
- [ ] 7.5 Run existing test suite to ensure no regressions in organization-groups, campaigns

## 8. Documentation

- [ ] 8.1 Update `openspec/specs/organization-groups/spec.md` with ADDED/MODIFIED requirements from delta (after archive)
- [ ] 8.2 Update `openspec/specs/campaigns/spec.md` with ADDED/MODIFIED requirements from delta (after archive)
