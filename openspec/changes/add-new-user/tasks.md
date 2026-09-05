## 1. Database & Entity

- [ ] 1.1 Add nullable `name` and `surname` columns to `User` entity via
  Doctrine migration
- [ ] 1.2 Update `User` entity PHP class: add `?string $name = null`,
  `?string $surname = null` properties with getters/setters
- [ ] 1.3 Migration to change `OrganizationGroup.ownerUser` FK from
  `onDelete: 'SET NULL'` to `onDelete: 'CASCADE'`

## 2. New User — Controller with Direct EM

- [ ] 2.1 Create `CreateUserRequest` DTO with fields `email`, `name`,
  `surname`, `role` and Symfony Validator constraints (`email` required,
  `role` required, in {admin, manager})
- [ ] 2.2 Implement `UserController::new` (`GET /admin/users/new`)
  renders creation form
- [ ] 2.3 Implement `UserController::create` (`POST /admin/users/new`)
  with `#[IsGranted('ROLE_ADMIN')]`, DTO binding, validator, email
  uniqueness check, and direct `EntityManagerInterface` usage; persists
  `User` and, for `manager` role, creates `OrganizationGroup`
  (`slug=user-<id>-group`, `type=GroupType::User`) in one transaction
- [ ] 2.4 Create Twig form template `user/form.html.twig` for user
  creation (fields: email, name, surname, role select)

## 3. Delete User — Controller with Direct EM

- [ ] 3.1 Implement `UserController::delete` (`GET /admin/users/{id}/delete`)
  renders confirmation page
- [ ] 3.2 Implement `UserController::remove` (`POST /admin/users/{id}/delete`)
  with `#[IsGranted('ROLE_ADMIN')]` and self-deletion guard
  (`id != current_user_id`); removes `User` directly via
  `EntityManagerInterface`; DB CASCADE handles `OrganizationGroup`
  deletion for `manager`
- [ ] 3.3 Create Twig confirmation template `user/delete.html.twig`

## 4. User List Page

- [ ] 4.1 Implement `UserController::list` (`GET /admin/users`) with
  `#[IsGranted('ROLE_ADMIN')]`, loads all users via `UserRepository`
- [ ] 4.2 Create Twig list template `user/list.html.twig` with table
  (email, name, surname, role, delete button excluding current user)

## 5. Security & Access

- [ ] 5.1 Ensure all `UserController` routes are protected by `ROLE_ADMIN`
  via `#[IsGranted('ROLE_ADMIN')]` attributes
- [ ] 5.2 Add access-control test scenarios: manager rejection,
  anonymous rejection, self-deletion rejection

## 6. Tests & Validation

- [ ] 6.1 Write PHPUnit tests for `UserController::create`: create with
  email/name/surname, create without name/surname, create manager (group
  created), create admin (no group), missing email rejection, duplicate
  email rejection, missing role rejection, invalid role rejection
- [ ] 6.2 Write PHPUnit tests for `UserController::remove`: delete manager
  (group deleted via CASCADE), delete admin (no group), self-deletion
  rejection, transaction rollback on error
- [ ] 6.3 Write PHPUnit tests for `UserController::list`: admin sees all
  users, manager is rejected
- [ ] 6.4 Run `openspec validate add-new-user --type change --strict`
  and fix any validation issues
