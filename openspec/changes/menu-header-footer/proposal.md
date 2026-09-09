## Why

The header currently shows 4 individual "Создать" buttons that take up significant horizontal space. Navigation links include "Выйти" as a bare link mixed with page navigation. On mobile, the header just wraps elements awkwardly. The goal is a clean, modern header with proper UX: a consolidated create dropdown, a user dropdown replacing "Выйти", and a hamburger menu for mobile with a slide-in sidebar.

## What Changes

- **Header layout**: Logo left, navigation center-right, three action buttons far right: «Создать ▾», «⚙ Админ ▾» (admin only), user name dropdown. "Выйти" moves inside user dropdown.
- **Admin dropdown**: "⚙ Админ ▾" — visible to `ROLE_ADMIN` only. Contains: Пользователи, Скрытые организации. Keeps admin pages out of main navigation. Positioned after «Создать».
- **Create dropdown**: Single "Создать ▾" button replaces 4 individual buttons. Items: Организацию, Контакт, Звонок, Рассылку (all users), Группу (ROLE_MANAGER+), Пользователя (ROLE_ADMIN only).
- **User dropdown**: Shows `app.user.name` with a caret. Contains "Выйти" link. Replaces the bare "Выйти" nav link.
- **Mobile hamburger**: On screens ≤768px, navigation collapses into a ☰ hamburger button. Opens a slide-in sidebar from the left with all nav items, create dropdown, and user dropdown stacked vertically.
- **New JS**: Click-toggle dropdowns (create + user), close-on-outside-click, hamburger toggle, sidebar overlay.
- **Template refactor**: `base.html.twig` menu building separated into nav items, create visibility, and user info.

## Capabilities

### New Capabilities
*None*

### Modified Capabilities
- `web-interface`: Requirement "Шапка и подвал" updated — header restructured with create dropdown, user dropdown, hamburger menu, and mobile slide-in sidebar. New scenarios for user dropdown, hamburger toggle, sidebar behavior, and role-based create items.

## Impact

**UI**: Full header restructure — layout, dropdowns, mobile behavior. Footer simplified to copyright only. No API, database, or service changes.

**Files**:
- `templates/base.html.twig` — refactor menu building
- `templates/components/header.html.twig` — restructure header
- `templates/components/footer.html.twig` — remove columns, keep copyright only
- `assets/scss/components/header.scss` — dropdowns, hamburger, sidebar styles
- `assets/scss/components/footer.scss` — remove unused styles
- `assets/js/header-create-dropdown.js` (new) — all interactive behavior
- `assets/app.js` — import new JS
