## Why

The current organization-groups capability uses personal groups (`user-<id>-group`) to distribute organizations between managers. This model has limitations: managers cannot organize groups on their own, personal groups are an implementation detail that leaks into the UI, and groups serve only as a passive access mechanism. The new model makes groups a first-class tool for managers: they can create, edit, and delete their own custom groups, use groups to organize orgs during creation, and select groups when building campaign recipients. Personal groups are removed — org visibility is handled by the separate `organization-visibility` change.

## What Changes

- **Restructured `organization-groups` capability**: Remove personal groups entirely. Add `created_by` field so managers own their custom groups. Managers get full CRUD on own groups (create, edit, delete, manage membership). Admin retains full control over all groups and manages group assignments. Org creation page gains group checkboxes. Manager deletion flow requires per-group reassign/delete choice.
- **Modified `campaigns` capability**: Add group-based bulk recipient selection (select all organizations in a specific group). "Add all" button retains existing behavior (all orgs in manager's custom groups).

## Capabilities

### New Capabilities
*None*

### Modified Capabilities
- `organization-groups`: Remove personal groups, add `created_by`, manager CRUD on own groups, group management UI, org edit group checkboxes, manager deletion flow.
- `campaigns`: Add group-based bulk recipient selection.

## Impact

**Database**: Add `description` (TEXT NULL), `color` (VARCHAR(7) NULL), `created_by` (INT NULL, FK → users) to `organization_groups`. Remove personal groups (`user-<id>-group` pattern).

**Routes**: Manager group management is implemented as web routes (`/groups`, `/groups/new`, `/groups/{id}/edit`, `/groups/{id}/delete`, `/groups/{id}/members`) rather than a REST API. New web endpoint `POST /campaigns/{id}/recipients/bulk-by-group`, accepting `group_id` and returning `{ added, skipped }` for AJAX requests (redirect + flash otherwise).

**Services**: `CampaignRecipientService` extended with group-based bulk add. Group repository updated with ownership queries. Manager deletion service updated with reassign/delete flow.

**UI**: New menu item "Мои группы" for managers. Group management pages (list with creator column, create, edit, members). Org create/edit pages gain group checkboxes. Campaign recipients page gains "Add by group" dropdown. Manager delete confirmation shows group reassign/delete per-group choice.

**ADRs**: No new ADRs needed (extends existing organization-groups model).
