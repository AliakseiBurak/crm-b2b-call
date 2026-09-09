# Organization Hiding

## Why

Groups currently double as both categorization and an access-control mechanism, forcing admins to fragment organizations across groups just to hide one from a manager. We need simple, per-organization hiding that lets an admin restrict a specific manager's view without creating or reshuffling groups.

## What Changes

- **Invert the access model to default-open with a deny-list**: every manager sees all organizations **except** those hidden from them. No organization seeding or backfill is required; a newly created manager immediately sees everything.
- **New `organization_hide` table (Entity `OrganizationHide`)**: id UUID, `organization` (ManyToOne, NOT NULL, `ON DELETE CASCADE`), `manager` (ManyToOne → User, NOT NULL, `ON DELETE CASCADE`), `hiddenAt` (DateTimeImmutable, NOT NULL), `UNIQUE(organization_id, manager_id)`, indexes on `manager_id` and `organization_id`. No `grantedBy`, no `expiresAt`.
- **Groups become categorization only**: group membership and `GroupAssignment` no longer grant or restrict access to organizations. Group UI visibility rules (manager sees created + assigned groups) stay unchanged.
- **Hide wins over everything for a manager**, including the organization's creator: hiding from the old manager repoints work to the new one.
- **Admin-only hide management** (403 for managers): a dedicated admin registry «Скрытые организации» (list of hide records with unhide action + hide form with organization select, manager multi-select, and «Все менеджеры» checkbox) plus a «Скрыть от менеджеров» action on the organization card that opens the form pre-selected.
- **«Все менеджеры» = one hide row per current manager**; future managers are unaffected (default open). «Показать всем» = delete all rows for that organization. Per-organization hiding only; no group-based bulk hide.
- **Hiding respected everywhere**: organization lists/search, dashboard stats, contacts, calls (incl. call lists and pseudo-groups «Звонки на сегодня», «Будущие звонки»), campaign recipient lists, campaign bulk-add-by-group, and group member views (own and assigned).
- **BREAKING**: supersedes the manager access-scope formula of ADR-0007/ADR-0011 (a new ADR-0012 documents the hiding model). Old group-based access spec text in other capabilities is reworded in a follow-up change after this one.

## Capabilities

### New Capabilities
- `organization-hiding`: per-organization hide records restricting a manager's visibility of organizations, with admin-only management.

### Modified Capabilities
- `access-control`: replaces the manager group-access scope with a default-open, deny-list model — removes the requirement «Менеджер имеет полный доступ к организациям своих групп» and adds requirements «Менеджер видит все организации, кроме скрытых» and «Скрытие не ограничивает администратора».

## Impact

- **Data**: new `organization_hide` DB table; FK `ON DELETE CASCADE` on both `organization` and `manager` (deleting either removes its hide rows); no backfill/seeding migration.
- **Repository/Core**: rewrite `OrganizationRepository::findAccessibleIds()` to return all orgs **except** those in `organization_hide` for the current manager (`NOT IN` hide rows); admin bypasses hide entirely.
- **Admin UI**: new hide-management registry with list/unhide/hide form; hide action on the organization card; manager 403 on any hide management.
- **Read-path filtering**: `GroupController` members view, `CampaignRecipientService` (bulk-add-by-group and recipient lists), call lists and pseudo-groups «Звонки на сегодня»/«Будущие звонки», dashboard stats — all skip hidden orgs for the manager.
- **Docs**: new `adr/0012` documenting the hiding model; follow-up reword of group-based access phrasing in other capability specs.
- **Breaking**: ADR-0007/ADR-0011 access-scope formula superseded.
