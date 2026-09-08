## Why

The `GroupAssignment` entity exists (many-to-many between `User` and `OrganizationGroup`) but has no UI. Admins need to assign groups to managers so managers can access organizations in those groups. Without this, group assignments must be done directly in the database.

## What Changes

- **New admin UI**: Page to assign groups to managers. Admin sees all groups, can toggle which managers have access to each group.
- **No backend changes**: `findForManager()` query already uses `GroupAssignment` — only the UI is missing.

## Capabilities

### New Capabilities
*None*

### Modified Capabilities
- `organization-groups`: Admin group management extended with assignment UI — assign managers to groups.

## Impact

**Database**: No changes — `group_assignment` table and `GroupAssignment` entity already exist.

**Files**: New templates `group/assign.html.twig` and `user/assign.html.twig`, new controller actions in `GroupController` and `UserController`.
