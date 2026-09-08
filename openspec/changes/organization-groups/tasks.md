## 1. Database & Entity Layer

- [x] 1.1 Create Doctrine migration to add `description` (TEXT NULL), `color` (VARCHAR(7) NULL), `created_by` (INT NULL, FK → users) columns to `organization_group` table
- [x] 1.2 Remove personal groups (`user-<id>-group`): delete matching rows and cascade remove `OrgGroupMembership` and `GroupAssignment` rows
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
- [x] 3.3 Admin group management works with manager-owned groups (admin sees/edits all groups incl. `created_by` of managers; assignment UI deferred to `organization-group-assignment` change)
- [x] 3.4 Functional tests: admin manages all groups including manager-owned groups

## 4. Org Edit Group Checkboxes

- [x] 4.1 Add group checkboxes to org edit page (manager: created + assigned groups; admin: all groups)
- [x] 4.2 On org save, update `OrgGroupMembership` based on checked groups
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
- [x] 6.3 Implement query: organizations in group via `OrgGroupMembership`
- [x] 6.4 Create `CampaignRecipient` entries for each organization, skip existing (catch UniqueConstraintViolationException or check first)
- [x] 6.5 Create `POST /campaigns/{id}/recipients/bulk-by-group` endpoint accepting `group_id` form field, returning `{ added: N, skipped: M }` for AJAX requests (redirect + flash otherwise)
- [x] 6.6 Add "Добавить по группе" buttons (one per available group: name label, color style, description tooltip) to campaign recipients Twig template
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

## 9. Verification Follow-ups

- [x] 9.1 `bulkAddByGroup` skips organizations without deliverable e-mail (campaigns e-mail rule applies to every creation path)
- [x] 9.2 Result flash reports `пропущено: M (в том числе нет e-mail: K)` only when K > 0 (both bulk paths); AJAX returns `{ added, skipped, no_email }`
- [x] 9.3 Empty group bulk add shows a dedicated notice: «В группе «…» нет организаций — добавлять нечего»
- [x] 9.4 Assigned (not created) groups are read-only for managers: no "Редактировать" link, members page without edit form, membership POST returns 403
- [x] 9.5 Manager delete confirmation shows organization count and assigned managers per group; spacing between group choices and action buttons fixed
- [x] 9.6 Group delete confirmation clarifies that organizations are not deleted, only memberships and assignments
- [x] 9.7 Sync delta specs, main specs, proposal and design with the above

Отложено до архивации (fix while archiving):
- обновить `openspec/specs/access-control/spec.md` — убрать `user-<id>-group` из
  формулировок области доступа менеджера (ADR-0011);
- убрать устаревшие комментарии о «личной группе» в `src/Controller/UserController.php`,
  `src/Controller/OrganizationController.php`, `src/Controller/ContactController.php`,
  `src/Repository/OrganizationRepository.php`, `src/Controller/CampaignController.php`.
