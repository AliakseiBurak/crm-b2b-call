## 1. Group-Side Assignment (GroupController)

- [ ] 1.1 Add `GET /groups/{id}/assign` action to GroupController — load group, all managers, current assignment state; guard with `#[IsGranted('ROLE_ADMIN')]`; verify GET returns 200 for admin and 403 for manager
- [ ] 1.2 Create `templates/group/assign.html.twig` — form with manager checkboxes (pre-filled from current assignments), CSRF token, save button; verify template renders correct checkbox state
- [ ] 1.3 Add `POST /groups/{id}/assign` action to GroupController — diff-based save: remove stale `GroupAssignment` rows, add new ones; verify POST persists correct assignments

## 2. User-Side Assignment (UserController)

- [ ] 2.1 Add `GET /admin/users/{id}/assign` action to UserController — load user (must be manager), all groups, current assignment state; verify GET returns 200 for admin and 403 for manager
- [ ] 2.2 Create `templates/user/assign.html.twig` — form with group checkboxes (pre-filled from current assignments), CSRF token, save button; verify template renders correct checkbox state
- [ ] 2.3 Add `POST /admin/users/{id}/assign` action to UserController — diff-based save: remove stale `GroupAssignment` rows, add new ones; verify POST persists correct assignments

## 3. List Page Buttons

- [ ] 3.1 Add "Назначить" button to `group/list.html.twig` actions column — visible to admin only, links to `/groups/{id}/assign`; verify button appears for admin and hidden for manager
- [ ] 3.2 Add "Назначить" button to `user/list.html.twig` actions column — visible for manager users only, links to `/admin/users/{id}/assign`; verify button appears for managers and hidden for admins

## 4. Tests

- [ ] 4.1 Add group-side assignment tests to GroupControllerTest — admin sees all managers with correct checkboxes, admin can assign/unassign, manager gets 403; verify all tests pass
- [ ] 4.2 Add user-side assignment tests to UserControllerTest — admin sees all groups with correct checkboxes, admin can assign/unassign, manager gets 403; verify all tests pass
