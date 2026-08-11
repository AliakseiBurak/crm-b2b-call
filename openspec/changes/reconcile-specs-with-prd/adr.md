# ADR Review Manifest

- Status: completed
- Review date: 2026-08-11

## Review Summary

ADR review completed for this change.

## In-Force ADRs Reviewed

- ADR-0001: Собственная группа менеджера (own group) — in force
- ADR-0002: Many-to-many членство организации в группах — in force
- ADR-0003: Область доступа менеджера и оператора — in force
- ADR-0004: Глобальная область доступа администратора — in force

## New Durable ADRs Created

- `adr/0005-roles-fixed-enum.md` — фиксированный набор ролей (admin, manager,
  operator), эндпоинты Roles CRUD из PRD §4.5 не реализуются. Разрешает
  внутреннее противоречие PRD; не supersedes ни один из in-force ADR.
