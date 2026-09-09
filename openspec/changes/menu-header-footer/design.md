## Context

The header component (`templates/components/header.html.twig`) currently renders 4 individual "Создать" buttons in a flex container (`header__create`). The buttons link to organization, contact, call, and campaign creation pages. The header SCSS (`assets/scss/components/header.scss`) styles this container. The project uses Webpack Encore with JS modules imported in `assets/app.js`. Existing dropdown-like patterns exist in `org-combobox.js` (toggle + menu + outside-click-close). `base.html.twig` builds a flat `menu` array that mixes navigation links with "Выйти".

## Goals / Non-Goals

**Goals:**
- Replace 4 individual buttons with a single "Создать ▾" dropdown button
- Add "Группу" (managers+) and "Пользователя" (admin) to the create dropdown
- Add user dropdown showing `app.user.name` with "Выйти" inside
- Mobile hamburger menu (≤768px) with slide-in sidebar from the left
- Minimal JS: click toggle + close-on-outside-click, no framework
- Consistent with existing design tokens and patterns

**Non-Goals:**
- Keyboard navigation (arrow keys, Escape) — can be added later
- Animation/transitions beyond simple slide-in — keep it simple
- User profile/settings page — just the dropdown shell

## Decisions

### 1. Template structure: data-attribute driven JS

**Decision:** Use `data-header-create`, `data-header-create-toggle`, `data-header-create-menu` attributes on elements. JS discovers the component via `document.querySelector('[data-header-create]')`.

**Why:** Follows the existing pattern in `org-combobox.js` and `date-picker.js` where JS behavior is attached via data attributes. Keeps Twig templates clean and JS decoupled.

**Alternatives considered:**
- Class-based selection (`.header-create`) — less semantic, could collide with styling classes
- Inline `onclick` — rejected, mixes concerns

### 2. JS approach: single document click listener

**Decision:** One `document.addEventListener('click')` that handles both toggle and outside-click. Checks if click target is inside the toggle button (toggle menu) or outside (close menu).

**Why:** Follows the exact pattern in `org-combobox.js` (line 145: `document.addEventListener('click', ...)`). Single listener is efficient and prevents multiple dropdowns from conflicting.

**Alternatives considered:**
- Separate click listeners on toggle and document — more complex, potential conflicts
- `focusout` event — doesn't work well with click interactions

### 3. CSS approach: absolute positioning

**Decision:** Dropdown menu positioned absolutely below the button, right-aligned (`right: 0`). Uses existing design tokens (`$color-bg-band-blue` for border, `$radius-modal` for radius, `$font-heading` for font).

**Why:** Standard dropdown pattern. Right-aligned because the button is in the header's right section.

**Alternatives considered:**
- Fixed positioning — unnecessary complexity
- Popover API — not widely supported yet

### 4. Role-based visibility in Twig

**Decision:** Use `is_granted('ROLE_MANAGER')` and `is_granted('ROLE_ADMIN')` Twig conditionals to conditionally render dropdown items.

**Why:** Standard Symfony security integration. Server-side rendering means hidden items are never sent to the client (no client-side access control bypass).

**Alternatives considered:**
- CSS `display:none` with data attributes — sends unnecessary HTML to client
- JS-based role check — requires passing role info to client, unnecessary complexity

### 5. Caret: pure CSS triangle

**Decision:** Use border trick to create a downward-pointing triangle (▼) next to "Создать" text. No icon font or SVG needed.

**Why:** Zero additional dependencies, works at any font size, matches the minimal aesthetic.

**Alternatives considered:**
- Unicode ▾ character — less control over sizing
- SVG icon — adds dependency for a trivial element

### 6. User dropdown: Twig-rendered, same JS pattern

**Decision:** Render a `<button>` with `data-header-user` attribute containing `app.user.name` + caret. Dropdown menu with "Выйти" link. JS uses the same click-toggle + outside-click pattern as the create dropdown.

**Why:** Reuses the same JS pattern. Server-side rendering of user name avoids passing data to client. Twig's `app.user` is always available for authenticated users.

**Alternatives considered:**
- Pass user name via data attribute — unnecessary indirection
- CSS-only dropdown (`:hover`) — unreliable on touch devices

### 7. Mobile breakpoint: 768px with hamburger

**Decision:** At `max-width: 768px`, hide `.header__nav` and `.header__actions` (all three dropdowns), show `.header__hamburger` button. Clicking hamburger opens a `.header__sidebar` that slides in from the left.

**Why:** 768px is the project's `bp('md')` breakpoint, already used elsewhere. Left-side sidebar is natural for reading direction (LTR).

**Alternatives considered:**
- Right-side sidebar — less natural for primary navigation
- Fullscreen overlay — too heavy for this scope
- Dropdown under hamburger — doesn't fit all items on small screens

### 8. Sidebar: fixed position with overlay

**Decision:** Sidebar uses `position: fixed; left: 0; top: 0; bottom: 0; width: 280px; transform: translateX(-100%)` when closed, `translateX(0)` when open. A semi-transparent overlay (`position: fixed; inset: 0; background: rgba(0,0,0,0.4)`) covers the page content. Clicking overlay closes sidebar.

**Why:** Standard mobile navigation pattern. Fixed positioning prevents scroll issues. Transform-based animation is GPU-accelerated.

**Alternatives considered:**
- `position: absolute` — can escape viewport on scroll
- No overlay — user could interact with background content

### 9. Admin dropdown: "⚙ Админ ▾"

**Decision:** For `ROLE_ADMIN` users, a separate "⚙ Админ ▾" dropdown appears to the right of "Создать ▾" and to the left of the user dropdown. Contains navigation links: Пользователи, Скрытые организации. Uses the same click-toggle + outside-click JS pattern as other dropdowns.

**Why:** "Создать" is the primary action (used by all roles) and comes first. "Админ" is secondary (admin-only) and follows. This ordering keeps the most-used action closest to the navigation links.

**Alternatives considered:**
- Putting admin links in user dropdown — mixes navigation with account actions
- "Ещё ▾" generic dropdown — less clear purpose
- Keeping in main nav — too many links, cramped on smaller screens

### 10. Footer: copyright only

**Decision:** Remove all three columns (Company, Menu, Contacts) from the footer. Keep only the centered copyright line «© YYYY B2B Call CRM» on the green gradient. Remove `menu` parameter from `footer.html.twig`.

**Why:** Footer was providing no real value — contacts are internal/test data, menu links duplicate the header. A minimal footer is cleaner and consistent with modern CRM tools.

**Alternatives considered:**
- Keep just company description — still redundant
- Keep menu links — duplicates header, not useful in footer context

## Risks / Trade-offs

- [Risk] Click on dropdown item navigates away before menu closes → **Mitigation:** Menu closes on any click (including items) because the document click listener fires. Navigation happens naturally via `<a>` tag.
- [Risk] Multiple dropdowns on page → **Mitigation:** Create and user dropdowns share the same document click listener. Only one can be open at a time (opening one closes the other).
- [Risk] Mobile header layout might clip dropdown → **Mitigation:** Dropdowns have `min-width: 180px` and `z-index: 50`. On mobile, dropdowns open inside the sidebar instead.
- [Risk] Sidebar pushes page content → **Mitigation:** Sidebar is fixed-position with overlay, does not affect page layout.
- [Risk] Hamburger icon not visible on some backgrounds → **Mitigation:** Hamburger uses the same `$color-info-blue` as other header elements, consistent contrast.

## Migration Plan

No migration needed. Pure UI change — no database, API, or service modifications.

**Rollback:** Revert the 4 files changed. No data to restore.
