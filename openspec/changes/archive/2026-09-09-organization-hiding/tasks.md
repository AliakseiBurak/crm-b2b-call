## 1. Database & Entity Layer

- [x] 1.1 Create Doctrine migration for `organization_hide` (id UUID, organization_id, manager_id, hidden_at, UNIQUE(organization_id, manager_id), indexes on manager_id and organization_id, FK ON DELETE CASCADE on both sides); verify `php bin/console doctrine:migrations:migrate` runs clean
- [x] 1.2 Create `OrganizationHide` entity (UUID id, ManyToOne Organization, ManyToOne User manager, DateTimeImmutable hiddenAt, unique constraint) and verify `php bin/console doctrine:schema:validate` passes
- [x] 1.3 Create `OrganizationHideRepository` with `findForOrganization(Organization): array`, `findForManager(User): array`, `findOneByOrganizationAndManager(Organization, User): ?OrganizationHide` and verify repository unit tests pass

## 2. Access Gateway Rewrite

- [x] 2.1 Rewrite `OrganizationRepository::findAccessibleIds()` to exclude organizations with hide rows for the manager (NOT IN subquery) and verify admin still gets null (unlimited)
- [x] 2.2 Write unit tests for the new gateway: admin bypasses hides, manager sees all by default, hidden org excluded, hide does not affect other managers
- [x] 2.3 Run existing organization/contact/call/dashboard test suites and verify no regressions from removing group-based access

## 3. Hide Service

- [x] 3.1 Create `OrganizationHideService` with `hide(Organization, array $managers): int` skipping existing pairs and verify service unit tests cover idempotency
- [x] 3.2 Add `hideFromAllManagers(Organization): int` creating one row per current manager (role=manager) and verify count excludes already-hidden pairs
- [x] 3.3 Add `unhide(Organization, User): void` and verify rows are removed

## 4. Admin Hide Management UI

- [x] 4.1 Create `OrganizationHideController` (ROLE_ADMIN) with routes: GET /admin/hides (list), POST /admin/hides (create, org select + manager select with «скрыть от всех менеджеров» option), POST /admin/hides/{id}/delete (unhide) and verify manager access returns 403 via functional test
- [x] 4.2 Create hide form (organization select, manager multi-select with role=manager only, «Все менеджеры» checkbox) and verify validation rejects non-manager users and duplicate pairs with conflict message
- [x] 4.3 Create Twig templates: registry list (organization, manager, hidden at, actions) and hide form; verify pages render for admin and 403 for manager
- [x] 4.4 Add admin navigation entry «Скрытые организации» and verify it appears only for admins
- [x] 4.5 Admin UI: registry list with inline form (org select + manager select + «Добавить»), delete per row; org edit page shows hides section inside main form with «Показать» buttons

## 5. Read-Path Filtering

- [x] 5.1 Filter `GroupController::members()` displayed organizations by `findAccessibleIds()` for managers in BOTH branches (editable and read-only assigned view) and verify admin still sees all members
- [x] 5.2 Filter `CampaignRecipientService::bulkAddByGroup()` to skip organizations outside the acting user's accessible ids and verify hidden orgs are not added as recipients
- [x] 5.3 Hide recipient rows of hidden organizations in campaign recipient list rendering for managers and verify admin still sees all rows
- [x] 5.4 Verify call lists and pseudo-groups («Звонки на сегодня», «Будущие звонки») exclude hidden organizations; fix any query path that does not filter by `findAccessibleIds()`
- [x] 5.5 Write functional test: hiding an organization removes it from manager's dashboard stats, contact list, call lists and pseudo-groups

## 6. Cascades & Data Integrity

- [x] 6.1 Verify deleting a manager removes their hide rows via FK cascade (functional test on user delete flow)
- [x] 6.2 Verify deleting an organization removes its hide rows via FK cascade (functional test on organization delete flow)

## 7. Integration & Regression

- [x] 7.1 Test full flow: admin hides org from manager → org disappears from all manager views; admin unhides → org reappears
- [x] 7.2 Test «скрыть от всех» → no current manager sees the org; new manager created afterwards sees it
- [x] 7.3 Test «показать» per-row → hide row removed and org visible to that manager again
- [x] 7.4 Run full existing test suite and verify no regressions in organization-groups, access-control, campaigns, calls

## 8. Documentation & ADR

- [x] 8.1 Create `adr/0012-organization-hiding-default-open-deny-list.md` documenting the model (default-open deny-list, per-manager rows, groups become categorization only, supersedes access-scope formula of ADR-0007/0011)
- [x] 8.2 Add `organization-hiding` capability to `project.md` capabilities table
- [x] 8.3 Verify `openspec validate organization-hiding --type change` passes with zero errors
