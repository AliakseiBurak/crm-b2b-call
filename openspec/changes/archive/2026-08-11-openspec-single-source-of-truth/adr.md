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
- ADR-0005: Фиксированный набор ролей (без CRUD ролей) — in force

## New Durable ADRs Created

- `adr/0006-openspec-single-source-of-truth.md` — OpenSpec как единственный
  источник истины; удаление PRD; реестр отложенных пробелов (управление
  пользователями, NFR доступность/бэкап/аудит, CSRF); переписывание контекста
  ADR-0001–0004 как одобренное пользователем отклонение от неизменяемости ADR.
  Не supersedes ни один in-force ADR.
